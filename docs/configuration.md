# Configuration Guide

This guide covers all configuration options available in Pigeon.

## Required Configuration

### Hook Prefix

The hook prefix is **required** and must be set before registering Pigeon:

```php
use StellarWP\Pigeon\Config;

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

## Optional Configuration

### Custom Logger

By default, Pigeon uses `ActionScheduler_DB_Logger` to store logs in Action Scheduler's existing logs table. This reduces database overhead by reusing Action Scheduler's infrastructure.

Available loggers:

- **`ActionScheduler_DB_Logger`** (default): Stores logs in Action Scheduler's `actionscheduler_logs` table
- **`DB_Logger`**: Stores logs in Pigeon's dedicated `task_logs` table
- **`Null_Logger`**: Disables logging entirely

```php
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Loggers\ActionScheduler_DB_Logger;
use StellarWP\Pigeon\Loggers\DB_Logger;
use StellarWP\Pigeon\Loggers\Null_Logger;

// Use Action Scheduler's logs table (default)
Config::set_logger( new ActionScheduler_DB_Logger() );

// Use Pigeon's dedicated logs table
Config::set_logger( new DB_Logger() );

// Disable logging
Config::set_logger( new Null_Logger() );

// Or use a custom logger
Config::set_logger( new My_Custom_Logger() );
```

**Important:** Set the logger before calling `Provider::register()`.

## Container Configuration

Pigeon requires a dependency injection container that implements `StellarWP\ContainerContract\ContainerInterface`.

### Basic Setup

```php
use StellarWP\Pigeon\Provider;
use StellarWP\Pigeon\Config;

// Get your container instance
$container = get_my_container();

// Register Pigeon as a singleton
$container->singleton( Provider::class );

Config::set_hook_prefix( 'my_app' ); // Needs to be set before the provider is initialized.

// Initialize Pigeon
$container->get( Provider::class )->register();
```

## Database Configuration

Pigeon automatically creates database tables during registration:

1. **Tasks Table**: `{prefix}_pigeon_tasks_{hook_prefix}`
2. **Logs Table** (optional): `{prefix}_pigeon_task_logs_{hook_prefix}`

Where:

- `{prefix}` is your WordPress table prefix (e.g., `wp_`)
- `{hook_prefix}` is your configured hook prefix

**Note:** Table names are automatically trimmed to ensure they don't exceed MySQL's 64-character limit.

### Table Creation

The tasks table is created automatically when you call `Provider::register()`.

The logs table is only created if you're using the `DB_Logger`. When using the default `ActionScheduler_DB_Logger`, logs are stored in Action Scheduler's existing `actionscheduler_logs` table.

## Action Scheduler Configuration

Pigeon uses Action Scheduler for task scheduling. You can configure Action Scheduler settings separately:

### Custom Action Scheduler Tables

Action Scheduler uses its own tables. If you need custom table names, configure Action Scheduler before loading Pigeon.

### Concurrent Execution

By default, Action Scheduler processes one task at a time. To increase concurrency you can explore the [Action Scheduler documentation](https://actionscheduler.org/api/).

## Complete Configuration Example

Here's a complete example of configuring Pigeon:

```php
<?php
/**
 * Plugin Name: My Plugin
 */

use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Provider;

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Configure Pigeon
Config::set_hook_prefix( 'my_plugin' );

// Get container (example using a simple container)
$container = new My_Container();

// Register Pigeon
$container->singleton( Provider::class );
$container->get( Provider::class )->register();

```

## Configuration Best Practices

1. **Set Configuration Early**: Configure Pigeon before any code tries to use it
2. **Use Consistent Prefixes**: Keep your hook prefix consistent across your application
3. **Container Singleton**: Always register Provider as a singleton
4. **Check Registration**: If you are not sure whether Pigeon is registered, you can check it using `Provider::is_registered()` before accessing Pigeon

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
