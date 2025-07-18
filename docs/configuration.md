# Configuration Guide

This guide covers all configuration options available in Shepherd.

## Required Configuration

### Hook Prefix

The hook prefix is **required** and must be set before registering Shepherd:

```php
use StellarWP\Shepherd\Config;

// Set a unique prefix for your application
Config::set_hook_prefix( 'my_app' );
```

The hook prefix is used to:

- Create unique database table names
- Generate WordPress action/filter names

**Best Practices:**

- Use a short, unique identifier for your plugin/theme
- Avoid special characters (use only letters, numbers, underscores)
- Keep it consistent across your application

**Automatic Length Protection:**

Shepherd automatically ensures table names don't exceed MySQL's 64-character limit. If your hook prefix is too long, it will be automatically trimmed to a safe length based on:

- Your WordPress table prefix length
- The longest Shepherd table name

You can check the maximum safe length using:

```php
$max_length = Config::get_max_hook_prefix_length();
```

## Optional Configuration

### Custom Logger

By default, Shepherd uses `ActionScheduler_DB_Logger` to store logs in Action Scheduler's existing logs table. This reduces database overhead by reusing Action Scheduler's infrastructure.

Available loggers:

- **`ActionScheduler_DB_Logger`** (default): Stores logs in Action Scheduler's `actionscheduler_logs` table
- **`DB_Logger`**: Stores logs in Shepherd's dedicated `task_logs` table
- **`Null_Logger`**: Disables logging entirely

```php
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Loggers\ActionScheduler_DB_Logger;
use StellarWP\Shepherd\Loggers\DB_Logger;
use StellarWP\Shepherd\Loggers\Null_Logger;

// Use Action Scheduler's logs table (default)
Config::set_logger( new ActionScheduler_DB_Logger() );

// Use Shepherd's dedicated logs table
Config::set_logger( new DB_Logger() );

// Disable logging
Config::set_logger( new Null_Logger() );

// Or use a custom logger
Config::set_logger( new My_Custom_Logger() );
```

**Important:** Set the logger before calling `Provider::register()`.

## Container Configuration

Shepherd requires a dependency injection container that implements `StellarWP\ContainerContract\ContainerInterface`.

### Basic Setup

```php
use StellarWP\Shepherd\Provider;
use StellarWP\Shepherd\Config;

// Get your container instance
$container = get_my_container();

// Register Shepherd as a singleton
$container->singleton( Provider::class );

Config::set_container( $container );
Config::set_hook_prefix( 'my_app' ); // Needs to be set before the provider is initialized.

// Initialize Shepherd
$container->get( Provider::class )->register();
```

## Database Configuration

Shepherd automatically creates database tables during registration:

1. **Tasks Table**: `{prefix}_shepherd_{hook_prefix}_tasks`
2. **Logs Table** (optional): `{prefix}_shepherd_{hook_prefix}_task_logs`

Where:

- `{prefix}` is your WordPress table prefix (e.g., `wp_`)
- `{hook_prefix}` is your configured hook prefix (automatically trimmed if needed)

**Note:** If your hook prefix is too long, Shepherd will automatically trim it to ensure table names don't exceed MySQL's 64-character limit. The trimming is calculated based on the longest table name.

### Table Creation

The tasks table is created automatically when you call `Provider::register()`.

The logs table is only created if you're using the `DB_Logger`. When using the default `ActionScheduler_DB_Logger`, logs are stored in Action Scheduler's existing `actionscheduler_logs` table.

## Action Scheduler Configuration

Shepherd uses Action Scheduler for task scheduling. You can configure Action Scheduler settings separately:

### Custom Action Scheduler Tables

Action Scheduler uses its own tables. If you need custom table names, configure Action Scheduler before loading Shepherd.

### Concurrent Execution

By default, Action Scheduler processes one task at a time. To increase concurrency you can explore the [Action Scheduler documentation](https://actionscheduler.org/api/).

## Complete Configuration Example

Here's a complete example of configuring Shepherd:

```php
<?php
/**
 * Plugin Name: My Plugin
 */

use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Provider;

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Configure Shepherd
Config::set_hook_prefix( 'my_plugin' );

// Get container (example using a simple container)
$container = new My_Container();

// Register Shepherd
$container->singleton( Provider::class );
$container->get( Provider::class )->register();

```

## Configuration Best Practices

1. **Set Configuration Early**: Configure Shepherd before any code tries to use it
2. **Use Consistent Prefixes**: Keep your hook prefix consistent across your application
3. **Container Singleton**: Always register Provider as a singleton
4. **Check Registration**: If you are not sure whether Shepherd is registered, you can check it using `Provider::is_registered()` before accessing Shepherd

```php
if ( ! Provider::is_registered() ) {
    // Handle not registered case
    return;
}
```

## Resetting Configuration

To reset all configuration to defaults:

```php
Config::reset();
```

**Warning:** This should only be used in testing scenarios, not in production code.
