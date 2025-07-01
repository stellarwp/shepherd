# Advanced Usage

This guide covers the advanced features of Pigeon for more complex use cases.

## Automatic Retries

Pigeon can automatically retry failed tasks. A task is considered failed when it throws any exception during the `process()` method.

### Configuring Retries

Override the `get_max_retries()` method on your task class. The default is `0` (no retries).

**Important**: This method returns the number of _additional_ attempts, not the total attempts. A task with 2 retries will execute up to 3 times total.

```php
<?php

namespace My\App\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;

class My_Retryable_Task extends Task_Abstract {
    public function get_max_retries(): int {
        return 2; // Will retry 2 times (3 total attempts)
    }

    public function process(): void {
        $result = some_flaky_operation();

        if ( ! $result ) {
            throw new \Exception( 'The operation failed.' );
        }
    }

    public function get_task_prefix(): string {
        return 'my_retry_';
    }
}
```

### Retry Delays

By default, Pigeon uses exponential backoff for retries. You can customize this by overriding `get_retry_delay()`:

```php
public function get_retry_delay(): int {
    // Default implementation (exponential backoff)
    return 30 * ( 2 ** ( $this->get_current_try() - 1 ) );

    // Or use a fixed delay
    // return 60; // Always wait 60 seconds between retries
}

// Access the current attempt number
public function process(): void {
    $attempt = $this->get_current_try(); // 1, 2, 3, etc.
    error_log( "Attempt #{$attempt}" );
}
```

## Task Priorities

Tasks can be assigned priorities to control their execution order. Override the `get_priority()` method:

```php
public function get_priority(): int {
    return 5; // Default is 10, lower numbers = higher priority
}
```

Priority values must be between 0 and 255 (Action Scheduler limitation).

## Task Groups

Organize related tasks into groups by overriding the `get_group()` method:

```php
public function get_group(): string {
    return 'my_custom_group'; // Default: 'pigeon_{prefix}_queue_default'
}
```

Groups help with:

- Organizing tasks in the Action Scheduler
- Bulk operations on related tasks
- Performance optimization

## Unique Tasks

Pigeon prevents duplicate tasks from being scheduled. A task is considered a duplicate if it has the same class and arguments as an existing scheduled task.

When you try to dispatch a duplicate task, Pigeon will:

- Check if an identical task already exists (same class + arguments)
- If it exists, silently ignore the dispatch request. You can listen to an action to be notified when this happens. See [API Reference](api-reference.md) for more information.
- If it doesn't exist, schedule the task normally

This behavior prevents accidental task duplication and is enabled by default for all tasks.

## Logging

Pigeon includes comprehensive logging that tracks the complete lifecycle of each task.

### Built-in Logging

By default, logs are stored in Action Scheduler's `actionscheduler_logs` table using the `ActionScheduler_DB_Logger`. This reduces database overhead by reusing existing infrastructure. The following events are automatically logged:

- `created`: Task scheduled (triggers `pigeon_{prefix}_task_created` action)
- `started`: Task execution begins (triggers `pigeon_{prefix}_task_started` action)
- `finished`: Task completed successfully (triggers `pigeon_{prefix}_task_finished` action)
- `failed`: Task failed (all retries exhausted, triggers `pigeon_{prefix}_task_failed` action)
- `rescheduled`: Task rescheduled (triggers `pigeon_{prefix}_task_rescheduled` action)
- `retrying`: Retry attempt starting
- `cancelled`: Task cancelled

Note: Tasks that fail without retry (e.g., HTTP 4xx errors) trigger `pigeon_{prefix}_task_failed_without_retry` instead of being rescheduled.

### Retrieving Logs

```php
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Provider;

// Get the logger instance
$logger = Provider::get_container()->get( Logger::class );

// Retrieve logs for a specific task
$logs = $logger->retrieve_logs( $task_id );

// Each log entry contains:
// - id: Log entry ID
// - task_id: Related task ID
// - date: Timestamp
// - level: PSR-3 log level
// - type: Event type (created, started, etc.)
// - entry: JSON-encoded log data
```

### Custom Logger Implementation

You can implement a custom logger by implementing the `Logger` interface:

```php
use StellarWP\Pigeon\Contracts\Logger;
use Psr\Log\AbstractLogger;

class My_Custom_Logger extends AbstractLogger implements Logger {
    public function log( $level, $message, array $context = [] ): void {
        // Your custom logging logic
        // $context must include 'task_id', 'type', and 'action_id'
    }

    public function retrieve_logs( int $task_id ): array {
        // Return logs for the given task ID
        return [];
    }
}

// Set your custom logger before registering Pigeon
Config::set_logger( new My_Custom_Logger() );
```

### Switching Between Loggers

Pigeon provides multiple logger implementations:

```php
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Loggers\ActionScheduler_DB_Logger;
use StellarWP\Pigeon\Loggers\DB_Logger;
use StellarWP\Pigeon\Loggers\Null_Logger;

// Use Action Scheduler's logs table (default)
Config::set_logger( new ActionScheduler_DB_Logger() );

// Use Pigeon's dedicated logs table
Config::set_logger( new DB_Logger() );

// Disable logging entirely
Config::set_logger( new Null_Logger() );
```

## Working with Task Data

### Accessing Task Information

Tasks provide several methods to access their data:

```php
class My_Task extends Task_Abstract {
    public function process(): void {
        // Get all arguments as an array
        $args = $this->get_args();

        // Get the task's unique identifier
        $task_id = $this->get_id();

        // Get the Action Scheduler action ID
        $action_id = $this->get_action_id();

        // Get the current retry attempt
        $attempt = $this->get_current_try();

        // Get task hash (class + args)
        $hash = $this->get_args_hash();
    }
}
```

### Task Validation

Override the `validate_args()` method to add custom validation:

```php
protected function validate_args( ...$args ): void {
    parent::validate_args( ...$args ); // Keep parent validation

    // Add your custom validation
    if ( empty( $args[0] ) ) {
        throw new \InvalidArgumentException( 'First argument cannot be empty' );
    }
}
```

## Performance Considerations

### Database Optimization

The task tables include indexes on:

- `action_id`: For Action Scheduler integration
- `args_hash`: For duplicate detection
- `class_hash`: For task type queries
- `task_id`: For log retrieval

## Advanced Integration

### WordPress Hooks

Pigeon fires several WordPress actions during task lifecycle:

```php
$prefix = Config::get_hook_prefix();

// Task starts processing (fired by Regulator)
add_action( "pigeon_{$prefix}_task_started", function( $task, $action_id ) {
    // Log, monitor, or prepare for task execution
}, 10, 2 );

// Task finished processing successfully (fired by Regulator)
add_action( "pigeon_{$prefix}_task_finished", function( $task, $action_id ) {
    // Cleanup, notify, or trigger dependent tasks
}, 10, 2 );

// Task failed with retries exhausted (fired by Regulator)
add_action( "pigeon_{$prefix}_task_failed", function( $task, $exception ) {
    // Handle permanent task failure
}, 10, 2 );

// Task failed without retry (fired by Regulator)
add_action( "pigeon_{$prefix}_task_failed_without_retry", function( $task, $exception ) {
    // Handle non-retryable failures (e.g., 4xx errors)
}, 10, 2 );

// Email sent (fired by Email task)
add_action( "pigeon_{$prefix}_email_processed", function( $task ) {
    // Do something after the email is processed
}, 10, 1 );

// HTTP request completed (fired by HTTP_Request task)
add_action( "pigeon_{$prefix}_http_request_processed", function( $task, $response ) {
    // Handle successful HTTP response
}, 10, 2 );
```

## Admin UI Configuration

Pigeon includes an optional admin interface for monitoring and managing tasks. The admin UI is enabled by default but can be customized or disabled.

### Enabling/Disabling Admin UI

```php
use StellarWP\Pigeon\Config;

// Disable admin UI entirely
Config::set_render_admin_ui( false );

// Re-enable admin UI
Config::set_render_admin_ui( true );
```

### Customizing Admin Page Access

Control who can access the admin page by setting the required capability:

```php
// Default capability is 'manage_options'
Config::set_admin_page_capability( 'manage_options' );

// Allow editors to access the admin page
Config::set_admin_page_capability( 'edit_posts' );

// Restrict to administrators only
Config::set_admin_page_capability( 'administrator' );
```

### Customizing Admin Page Titles

You can customize the titles shown in the admin interface:

```php
use StellarWP\Pigeon\Config;

// Custom page title (shown in browser tab and admin page list)
Config::set_admin_page_title_callback( function() {
    return __( 'My Task Manager', 'domain' );
} );

// Custom menu title (shown in WordPress admin sidebar under Tools)
Config::set_admin_menu_title_callback( function() {
    return __( 'Tasks', 'domain' );
} );

// Custom in-page title (shown as H1 on the admin page itself)
Config::set_admin_page_in_page_title_callback( function() {
    return __( 'Background Task Dashboard', 'domain' );
} );
```

### Default Titles

If you don't set custom callbacks, Pigeon uses these default patterns:

- **Page Title**: `Pigeon ({hook_prefix})`
- **Menu Title**: `Pigeon ({hook_prefix})`
- **In-Page Title**: `Pigeon Task Manager (via {hook_prefix})`

This allows multiple Pigeon instances (with different hook prefixes) to coexist in the same WordPress installation.

### Admin UI Location

The admin page is automatically added under **Tools** in the WordPress admin menu. The page renders a `<div id="pigeon-app"></div>` container that can be used to mount JavaScript-based interfaces.
