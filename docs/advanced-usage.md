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

By default, logs are stored in the `stellarwp_pigeon_{prefix}_task_logs` database table. The following events are automatically logged:

- `created`: Task scheduled
- `started`: Task execution begins
- `finished`: Task completed successfully
- `failed`: Task failed (all retries exhausted)
- `rescheduled`: Task rescheduled
- `retrying`: Retry attempt starting
- `cancelled`: Task cancelled

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
        // $context must include 'task_id' and 'type'
    }

    public function retrieve_logs( int $task_id ): array {
        // Return logs for the given task ID
        return [];
    }
}

// Set your custom logger before registering Pigeon
Config::set_logger( new My_Custom_Logger() );
```

### Null Logger

To disable logging entirely, use the built-in `Null_Logger`:

```php
use StellarWP\Pigeon\Loggers\Null_Logger;

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

// Task failed (fired by Regulator)
add_action( "pigeon_{$prefix}_task_failed", function( $task, $exception ) {
    // Handle task failure
}, 10, 2 );

// Email sent (fired by Email task)
add_action( "pigeon_{$prefix}_email_processed", function( $task ) {
    // Do something else after the email is processed, like scheduling a dependent new task.
}, 10, 1 );
```
