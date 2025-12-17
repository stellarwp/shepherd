# Advanced Usage

Advanced Shepherd features for complex use cases.

## Automatic Retries

Shepherd automatically retries failed tasks (tasks that throw exceptions during `process()`).

### Configuring Retries

Override `get_max_retries()` in your task class. Default is `0` (no retries).

**Important**: Returns additional attempts, not total. A task with 2 retries executes up to 3 times total.

```php
<?php

namespace My\App\Tasks;

use StellarWP\Shepherd\Abstracts\Task_Abstract;

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

Default uses exponential backoff. Customize by overriding `get_retry_delay()`:

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
    return 'my_custom_group'; // Default: 'shepherd_{prefix}_queue_default'
}
```

Groups help with:

- Organizing tasks in the Action Scheduler
- Bulk operations on related tasks
- Performance optimization

## Unique Tasks

Shepherd prevents duplicate tasks (same class and arguments) from being scheduled.

When dispatching a duplicate task:

- Identical task exists: Silently ignored (listen to action for notification - see [API Reference](api-reference.md))
- No identical task: Scheduled normally

Prevents accidental duplication and is enabled by default.

## Task Dispatching Requirements (Since 0.0.7)

When dispatching tasks, Shepherd performs several checks:

1. **Table Registration**: Verifies that Shepherd's database tables are registered
2. **Action Scheduler**: Ensures Action Scheduler is initialized

### Synchronous Fallback When Tables Not Registered

If Shepherd's database tables are not yet registered when you dispatch a task, the behavior depends on whether a delay is specified:

- **No delay (default)**: Task will be **processed immediately in a synchronous manner**
- **With delay**: Task will be **skipped** by default (can be changed via filter)

This ensures tasks can still execute even during early initialization phases while respecting delay requirements.

```php
// If tables are not registered:
shepherd()->dispatch( new My_Task() );        // Runs immediately (synchronous)
shepherd()->dispatch( new My_Task(), 300 );   // Skipped by default
```

You can monitor when this synchronous processing occurs:

```php
$prefix = Config::get_hook_prefix();

add_action( "shepherd_{$prefix}_dispatched_sync", function( $task ) {
    error_log( 'Task processed synchronously: ' . get_class( $task ) );
});
```

#### Customizing Synchronous Fallback (Since 0.0.8)

The filter now receives the default behavior based on the delay:

```php
add_filter( "shepherd_{$prefix}_should_dispatch_sync_on_tables_unavailable", function( $should_dispatch, Task $task ) {
    // Default behavior (since 0.0.8):
    // - Returns true when delay is 0 (immediate execution)
    // - Returns false when delay > 0 (skip execution)

    // Override examples:
    // Always process synchronously regardless of delay
    return true;

    // Never process synchronously
    // return false;

    // Custom logic based on task type
    // return $task instanceof Critical_Task;
}, 10, 2 );
```

### Action Scheduler Initialization

If Action Scheduler is not yet initialized when you dispatch a task, Shepherd will automatically queue it and dispatch once Action Scheduler is ready via the `action_scheduler_init` hook.

### Handling Table Registration Errors (Since 0.0.7)

Your application should handle cases where Shepherd's tables fail to register by listening to the `shepherd_{prefix}_tables_error` action:

```php
$prefix = Config::get_hook_prefix();

add_action( "shepherd_{$prefix}_tables_error", function( $error ) {
    // Log the error
    error_log( 'Shepherd tables failed to register: ' . $error->getMessage() );

    // Notify administrators
    add_action( 'admin_notices', function() use ( $error ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Background processing is unavailable. Please contact support.', 'stellarwp-shepherd' ) . '</p></div>';
    } );
});
```

If this action is not handled, Shepherd will trigger a `_doing_it_wrong` notice to alert developers during development.

## Logging

Comprehensive logging tracks the complete task lifecycle.

### Built-in Logging

Default logs are stored in Action Scheduler's `actionscheduler_logs` table using `ActionScheduler_DB_Logger`. Reduces database overhead by reusing existing infrastructure. Automatically logged events:

- `created`: Task scheduled (triggers `shepherd_{prefix}_task_created` action)
- `started`: Task execution begins (triggers `shepherd_{prefix}_task_started` action)
- `finished`: Task completed successfully (triggers `shepherd_{prefix}_task_finished` action)
- `failed`: Task failed (all retries exhausted, triggers `shepherd_{prefix}_task_failed` action)
- `rescheduled`: Task rescheduled (triggers `shepherd_{prefix}_task_rescheduled` action)
- `retrying`: Retry attempt starting
- `cancelled`: Task cancelled

Note: Tasks that fail without retry (e.g., HTTP 4xx errors) trigger `shepherd_{prefix}_task_failed_without_retry` instead of being rescheduled.

### Retrieving Logs

```php
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Provider;

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
use StellarWP\Shepherd\Contracts\Logger;
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

// Set your custom logger before registering Shepherd
Config::set_logger( new My_Custom_Logger() );
```

### Switching Between Loggers

Shepherd provides multiple logger implementations:

```php
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Loggers\ActionScheduler_DB_Logger;
use StellarWP\Shepherd\Loggers\DB_Logger;
use StellarWP\Shepherd\Loggers\Null_Logger;

// Use Action Scheduler's logs table (default)
Config::set_logger( new ActionScheduler_DB_Logger() );

// Use Shepherd's dedicated logs table
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

## Database Cleanup

Shepherd includes automatic database cleanup to maintain data integrity and prevent orphaned records.

### Automatic Cleanup on Action Deletion

When Action Scheduler deletes actions (through cleanup, manual deletion, or other processes), Shepherd automatically removes the corresponding task data to prevent orphaned records.

**How it works:**

1. **Hook Registration**: The `action_scheduler_deleted_action` hook is registered during Shepherd initialization
2. **Automatic Cleanup**: When an action is deleted, Shepherd queries for associated tasks
3. **Cascade Deletion**: Both task records and their logs are removed from Shepherd's tables
4. **Data Integrity**: Prevents accumulation of orphaned data

**Example behavior:**

```php
// When Action Scheduler deletes an action with ID 123
do_action( 'action_scheduler_deleted_action', 123 );

// Shepherd automatically:
// 1. Finds tasks with action_id = 123
// 2. Deletes associated logs from shepherd_task_logs
// 3. Deletes task records from shepherd_tasks
// No manual intervention required
```

### Periodic Cleanup with Herding Task

The [Herding task](tasks/herding.md) runs every 6 hours to clean up any orphaned data that might exist due to:

- Database corruption
- External modifications to Action Scheduler tables
- Race conditions during cleanup

**Combined Strategy:**

- **Immediate cleanup**: Action deletion hook removes data when actions are deleted
- **Periodic cleanup**: Herding task catches any missed orphaned data
- **Database integrity**: Ensures consistent state between Shepherd and Action Scheduler

### Manual Cleanup

If you need to manually clean up orphaned data:

```php
// Run the Herding task immediately
shepherd()->dispatch( new \StellarWP\Shepherd\Tasks\Herding() );

// Or trigger Action Scheduler cleanup
as_unschedule_all_actions( 'shepherd_task_prefix' );
```

## Performance Considerations

### Database Optimization

The task tables include indexes on:

- `action_id`: For Action Scheduler integration and cleanup operations
- `args_hash`: For duplicate detection
- `class_hash`: For task type queries
- `task_id`: For log retrieval

### Cleanup Performance

- **Batch Operations**: Cleanup operations use batch deletions for efficiency
- **Indexed Queries**: All cleanup queries use indexed columns for optimal performance
- **Minimal Overhead**: Action deletion hooks add minimal overhead to Action Scheduler operations

## Synchronous Task Execution (Since 0.1.0)

The `run()` method allows you to execute tasks synchronously with full control over the execution lifecycle. This is useful for CLI commands, REST API endpoints, or any scenario where you need tasks to execute immediately.

### Basic Usage

```php
use function StellarWP\Shepherd\shepherd;

// Run a single task immediately
shepherd()->run( [ new My_Task() ] );

// Run multiple tasks in sequence
$tasks = [
    new Process_Image_Task( $image_id ),
    new Generate_Thumbnail_Task( $image_id ),
    new Update_Metadata_Task( $image_id ),
];

shepherd()->run( $tasks );
```

### Lifecycle Callbacks

The `run()` method accepts an optional array of callbacks for fine-grained control:

```php
shepherd()->run( $tasks, [
    // Called before each task runs
    'before' => function( Task $task ): void {
        error_log( 'Starting task: ' . get_class( $task ) );
    },

    // Called after each task completes successfully
    'after' => function( Task $task ): void {
        error_log( 'Completed task: ' . get_class( $task ) );
    },

    // Called after all tasks complete (even on error)
    'always' => function( array $tasks ): void {
        error_log( 'Finished processing ' . count( $tasks ) . ' tasks' );
    },
] );
```

### CLI Command Example

```php
use WP_CLI;
use function StellarWP\Shepherd\shepherd;

WP_CLI::add_command( 'myapp process-images', function( $args, $assoc_args ) {
    $image_ids = get_unprocessed_image_ids();
    $tasks = array_map(
        fn( $id ) => new Process_Image_Task( $id ),
        $image_ids
    );

    $processed = 0;
    $failed = 0;

    shepherd()->run( $tasks, [
        'before' => function( Task $task ) {
            WP_CLI::log( 'Processing image...' );
        },
        'after' => function( Task $task ) use ( &$processed ) {
            $processed++;
            WP_CLI::success( 'Image processed!' );
        },
        'always' => function( array $tasks ) use ( &$processed, &$failed ) {
            WP_CLI::line( "Processed: {$processed}, Failed: {$failed}" );
        },
    ] );
} );
```

### REST API Example

```php
use function StellarWP\Shepherd\shepherd;

register_rest_route( 'myapp/v1', '/process', [
    'methods' => 'POST',
    'callback' => function( WP_REST_Request $request ) {
        $items = $request->get_param( 'items' );
        $tasks = array_map(
            fn( $item ) => new Process_Item_Task( $item ),
            $items
        );

        $results = [
            'processed' => [],
            'failed' => [],
        ];

        shepherd()->run( $tasks, [
            'after' => function( Task $task ) use ( &$results ) {
                $results['processed'][] = $task->get_args()[0];
            },
        ] );

        return new WP_REST_Response( $results, 200 );
    },
    'permission_callback' => fn() => current_user_can( 'manage_options' ),
] );
```

### Behavior Notes

- **Already scheduled tasks**: If a task was previously dispatched via `dispatch()`, `run()` will execute it without re-dispatching
- **Fallback mode**: When Shepherd's database tables are not registered, tasks execute immediately via `process()` without Action Scheduler
- **Context detection**: Shepherd automatically detects CLI and REST contexts for proper logging
- **Exception handling**: Exceptions or Throwables thrown inside callables (`before`, `after`, `always`) are caught and trigger the `tasks_run_failed` action

### WordPress Hooks

Monitor synchronous task execution using WordPress actions:

```php
$prefix = Config::get_hook_prefix();

// Fired before each task runs
add_action( "shepherd_{$prefix}_task_before_run", function( Task $task ) {
    // Prepare for task execution
}, 10, 1 );

// Fired after each task completes
add_action( "shepherd_{$prefix}_task_after_run", function( Task $task ) {
    // Post-task cleanup or notifications
}, 10, 1 );

// Fired when any task or callable fails (catches Exception and Throwable)
add_action( "shepherd_{$prefix}_tasks_run_failed", function( array $tasks, Throwable $e ) {
    // Handle batch failure - receives all tasks and the exception/error
    error_log( 'Tasks failed: ' . $e->getMessage() );
}, 10, 2 );

// Fired after all tasks complete successfully
add_action( "shepherd_{$prefix}_tasks_finished", function( array $tasks ) {
    // Batch completion handling
}, 10, 1 );

// Fired when tables aren't registered (fallback mode)
add_action( "shepherd_{$prefix}_task_run_sync", function( Task $task ) {
    // Track fallback executions
}, 10, 1 );
```

## Custom Dispatch Handlers

**Since 0.0.9**, you can completely override Shepherd's default dispatch behavior by providing a custom handler via a filter. This is useful for advanced scenarios where you need full control over how tasks are dispatched.

### Basic Usage

```php
$prefix = Config::get_hook_prefix();

add_filter( "shepherd_{$prefix}_dispatch_handler", function( $handler, $task, $delay ) {
    // Return a callable that will handle the dispatch
    return function( $task, $delay ) {
        // Your custom dispatch logic
        my_custom_task_queue()->add( $task, $delay );
    };
}, 10, 3 );
```

### Important Notes

- **Return null to use default handler**: If you return `null` or a non-callable value, Shepherd will use its default dispatch logic
- **Handler signature**: Your custom handler must accept two parameters: `$task` (Task instance) and `$delay` (integer)
- **Complete override**: When you provide a custom handler, Shepherd's default dispatch logic (including Action Scheduler integration) is completely bypassed
- **Responsibility**: Your custom handler is responsible for all aspects of task execution, including scheduling, retries, and logging

## Advanced Integration

### WordPress Hooks

Shepherd fires several WordPress actions during task lifecycle:

```php
$prefix = Config::get_hook_prefix();

// Task starts processing (fired by Regulator)
add_action( "shepherd_{$prefix}_task_started", function( $task, $action_id ) {
    // Log, monitor, or prepare for task execution
}, 10, 2 );

// Task finished processing successfully (fired by Regulator)
add_action( "shepherd_{$prefix}_task_finished", function( $task, $action_id ) {
    // Cleanup, notify, or trigger dependent tasks
}, 10, 2 );

// Task failed with retries exhausted (fired by Regulator)
add_action( "shepherd_{$prefix}_task_failed", function( $task, $exception ) {
    // Handle permanent task failure
}, 10, 2 );

// Task failed without retry (fired by Regulator)
add_action( "shepherd_{$prefix}_task_failed_without_retry", function( $task, $exception ) {
    // Handle non-retryable failures (e.g., 4xx errors)
}, 10, 2 );

// Email sent (fired by Email task)
add_action( "shepherd_{$prefix}_email_processed", function( $task ) {
    // Do something after the email is processed
}, 10, 1 );

// HTTP request completed (fired by HTTP_Request task)
add_action( "shepherd_{$prefix}_http_request_processed", function( $task, $response ) {
    // Handle successful HTTP response
}, 10, 2 );
```
