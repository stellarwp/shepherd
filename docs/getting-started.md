# Getting Started with Pigeon

This guide will walk you through the basics of installing and using Pigeon to run your first background task.

## Installation

Pigeon is a Composer package that provides a robust background task processing system for WordPress applications. To install it, you'll need to have Composer in your project. If you don't have it already, you can follow the instructions on the [Composer website](https://getcomposer.org/).

Once you have Composer set up, you can add Pigeon to your project by running the following command in your project's root directory:

```bash
composer require stellarwp/pigeon
```

After installing Pigeon, you need to make sure you're including the Composer autoloader in your plugin or theme. This is typically done by adding the following line to your main plugin file or `functions.php`:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

## Configuration and Registration

Pigeon requires a DI container that implements `StellarWP\ContainerContract\ContainerInterface`. You need to configure and register Pigeon before using it. This is typically done in your plugin's main file:

```php
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Provider;

// Example function to get your container instance
function get_my_container(): \StellarWP\ContainerContract\ContainerInterface {
    // Return your container instance
    return $container;
}

// Register Pigeon (at the latest on plugins_loaded)
add_action( 'plugins_loaded', function() {
    // IMPORTANT: Set the hook prefix first (required)
    Config::set_hook_prefix( 'my_app' ); // Use a unique prefix for your application

    // Get your container instance
    $container = get_my_container();

    // Register Pigeon as a singleton
    $container->singleton( Provider::class );

    // Initialize Pigeon
    $container->get( Provider::class )->register();
} );
```

### Configuration Options

Before registering Pigeon, you can configure it using the `Config` class:

```php
// Set a custom logger (optional - defaults to DB_Logger)
Config::set_logger( new My_Custom_Logger() );

// Get the configured hook prefix
$prefix = Config::get_hook_prefix();
```

## Creating Your First Task

Tasks in Pigeon are classes that extend the `StellarWP\Pigeon\Abstracts\Task_Abstract` class. At a minimum, you need to implement the `process()` method and `get_task_prefix()` method.

Let's create a simple task that logs a message:

```php
<?php

namespace My\App\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;

class Log_Message_Task extends Task_Abstract {
    /**
     * Overwriting the Abstract's constructor is optional but recommended.
     * It helps with type hinting and IDE autocompletion.
     *
     * IMPORTANT: You should always call parent::__construct() with all arguments.
     */
    public function __construct( string $message, int $code = 200 ) {
        parent::__construct( $message, $code );
    }

    /**
     * The main task logic that runs in the background.
     * Throw an exception to indicate task failure.
     */
    public function process(): void {
        // Access arguments via $this->get_args()
        $message = $this->get_args()[0];
        $code = $this->get_args()[1];

        // Your task logic here
        error_log( 'Pigeon Task: ' . $message . ' with code ' . $code );

        // If something goes wrong, throw an exception
        if ( $code >= 400 ) {
            throw new \Exception( 'Error code indicates failure' );
        }
    }

    /**
     * Return a unique prefix for this task type.
     * Maximum 15 characters.
     */
    public function get_task_prefix(): string {
         return 'log_msg_';
    }
}
```

### Important Notes About Tasks

- **Arguments**: Cannot contain callables. Objects must implement `JsonSerializable`.
- **Task Prefix**: Must be unique and max 15 characters.
- **Exceptions**: Throwing any exception in `process()` marks the task as failed.
- **Return Type**: The `process()` method returns `void` (no return value needed).

## Dispatching Your Task

Once you've created your task, you can dispatch it using the `pigeon()` helper function:

```php
use My\App\Tasks\Log_Message_Task;
use function StellarWP\Pigeon\pigeon;

// Create a task instance
$my_task = new Log_Message_Task( 'Hello, World!', 200 );

// Dispatch immediately
pigeon()->dispatch( $my_task );

// Or dispatch with a delay (in seconds)
pigeon()->dispatch( $my_task, 5 * MINUTE_IN_SECONDS ); // Execute after 5 minutes
```

### What Happens Next?

1. Pigeon schedules your task with Action Scheduler
2. WordPress cron (or another Action Scheduler's runner, like CLI) picks up the task
3. Your task's `process()` method is executed
4. The task lifecycle is logged in the database
5. If the task fails, it may be retried based on your configuration

Check your `debug.log` file, and you should see the message "Pigeon Task: Hello, World! with code 200".

## Verifying Task Execution

You can check if your task was scheduled successfully:

```php
// Get the last scheduled task ID
$task_id = pigeon()->get_last_scheduled_task_id();

// Retrieve task logs
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Provider;

$logger = Provider::get_container()->get( Logger::class );
$logs = $logger->retrieve_logs( $task_id );
```

## Next Steps

- Learn about [Advanced Usage](./advanced-usage.md) including retries, debouncing, and custom configuration
- Explore the [Built-in Tasks](./tasks.md) that come with Pigeon
- Read the [API Reference](./api-reference.md) for detailed information about all classes and methods

## Troubleshooting

If your tasks aren't running:

1. **Check Action Scheduler**: Visit Tools → Scheduled Actions in WordPress admin
2. **Verify WP-Cron**: Ensure WordPress cron is running or set up a real cron job
3. **Check Logs**: Look for errors in your WordPress debug log
4. **Database Tables**: Ensure Pigeon's tables were created during registration
