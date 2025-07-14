# Getting Started with Shepherd

Install and use Shepherd to run your first background task.

## Installation

Require Shepherd as a production dependency via Composer:

```bash
composer require stellarwp/shepherd
```

Include the Composer autoloader in your plugin or theme:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

## Configuration and Registration

Shepherd requires a DI container implementing `StellarWP\ContainerContract\ContainerInterface`. Register Shepherd in your plugin:

```php
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Provider;

// Example function to get your container instance
function get_my_container(): \StellarWP\ContainerContract\ContainerInterface {
    // Return your container instance
    return $container;
}

// Register Shepherd (at the latest on plugins_loaded)
add_action( 'plugins_loaded', function() {
    // IMPORTANT: Set the hook prefix first (required)
    Config::set_hook_prefix( 'my_app' ); // Use a unique prefix for your application

    // Get your container instance
    $container = get_my_container();

    // Register Shepherd as a singleton
    $container->singleton( Provider::class );

    // Set the container for Shepherd.
    Config::set_container( $container );

    // Initialize Shepherd
    $container->get( Provider::class )->register();
} );
```

### Configuration Options

Configure Shepherd before registration:

```php
// Set a custom logger (optional - defaults to DB_Logger)
Config::set_logger( new My_Custom_Logger() );

// Get the configured hook prefix
$prefix = Config::get_hook_prefix();
```

## Creating Your First Task

Tasks extend `Task_Abstract` and implement `process()` and `get_task_prefix()` methods:

```php
<?php

namespace My\App\Tasks;

use StellarWP\Shepherd\Abstracts\Task_Abstract;

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
        error_log( 'Shepherd Task: ' . $message . ' with code ' . $code );

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

Dispatch tasks using the `shepherd()` helper:

```php
use My\App\Tasks\Log_Message_Task;
use function StellarWP\Shepherd\shepherd;

// Create a task instance
$my_task = new Log_Message_Task( 'Hello, World!', 200 );

// Dispatch immediately
shepherd()->dispatch( $my_task );

// Or dispatch with a delay (in seconds)
shepherd()->dispatch( $my_task, 5 * MINUTE_IN_SECONDS ); // Execute after 5 minutes
```

### What Happens Next?

1. Shepherd schedules your task with Action Scheduler
2. WordPress cron picks up the task
3. Your task's `process()` method executes
4. The lifecycle is logged in the database
5. Failed tasks may be retried based on configuration

Check `debug.log` for the message "Shepherd Task: Hello, World! with code 200".

## Verifying Task Execution

Check if your task was scheduled successfully:

```php
// Get the last scheduled task ID
$task_id = shepherd()->get_last_scheduled_task_id();

// Retrieve task logs
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Provider;

$logger = Provider::get_container()->get( Logger::class );
$logs = $logger->retrieve_logs( $task_id );
```

## Next Steps

- [Advanced Usage](./advanced-usage.md) - retries, debouncing, custom configuration
- [Built-in Tasks](./tasks.md) - tasks included with Shepherd
- [API Reference](./api-reference.md) - detailed class and method documentation

## Troubleshooting

If your tasks aren't running:

1. **Check Action Scheduler**: Visit Tools → Scheduled Actions in WordPress admin
2. **Verify WP-Cron**: Ensure WordPress cron is running or set up a real cron job
3. **Check Logs**: Look for errors in your WordPress debug log
4. **Database Tables**: Ensure Shepherd's tables were created during registration
