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
use StellarWP\Shepherd\Config;

// Get the logger instance
$logger = Config::get_container()->get( Logger::class );

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

## Admin UI Configuration

Pigeon includes a React-based admin interface for monitoring and managing tasks with real-time AJAX updates. The admin UI is disabled by default and must be explicitly enabled.

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

The admin page can be added under **Tools** in the WordPress admin menu, by setting the `render_admin_ui` config to `true`. The page includes a fully-featured React-based task management interface.

### Admin UI Features

Pigeon includes a built-in React-powered admin interface that provides:

1. **Task List View**: A comprehensive table showing all background tasks with:
   - Task ID and Action ID
   - Task type (class name)
   - Arguments passed to the task
   - Current retry attempt number
   - Task status (Pending, Running, Success, Failed, Cancelled)
   - Scheduled execution time
   - Sortable columns and pagination

2. **Task Actions**: Interactive controls for managing tasks:
   - **View**: See detailed task logs (available for tasks with log entries)
   - **Reschedule**: Reschedule a task to a new date and time
   - **Edit**: Modify task properties (bulk action supported)
   - **Delete**: Remove tasks with confirmation dialog (bulk action supported)

3. **Real-time Status Display**:
   - Tasks show their current status with appropriate labels
   - Scheduled times are displayed in human-readable format (e.g., "2 hours ago")
   - Recent tasks show relative time, older tasks show absolute dates

4. **Responsive Design**: The interface uses WordPress DataViews component for consistent admin experience

### Technical Implementation

The admin UI is built with:

- **React**: For component architecture
- **WordPress DataViews**: For the table interface
- **WordPress i18n**: For internationalization support
- **TypeScript**: For type safety

The data is provided server-side through PHP and includes:

- Task information from the Pigeon tasks table
- Action details from Action Scheduler
- Comprehensive log entries for each task
- Pagination metadata

### Admin UI Development

The admin UI source code is located in the `app/` directory:

- `app/index.tsx` - Main entry point
- `app/components/ShepherdTable.tsx` - Task table component
- `app/data.tsx` - Data processing and field definitions
- `app/types.ts` - TypeScript type definitions

To modify the admin UI:

1. Install the appropriate Node.js version: `nvm use`
2. Install Node.js dependencies: `npm ci`
3. Run development build: `npm run dev`
4. Make your changes to the React components
5. Build for production: `npm run build`

The built files are output to the `build/` directory and automatically enqueued by the PHP admin provider.

## Exception Handling

### Specialized Exceptions

Pigeon provides specific exception types for different failure scenarios:

```php
use StellarWP\Pigeon\Exceptions\PigeonTaskException;
use StellarWP\Pigeon\Exceptions\PigeonTaskAlreadyExistsException;
use StellarWP\Pigeon\Exceptions\PigeonTaskFailWithoutRetryException;

class My_Task extends Task_Abstract {
    public function process(): void {
        // General task failure (will retry based on configuration)
        if ( $some_condition ) {
            throw new PigeonTaskException( 'Task failed due to temporary issue' );
        }

        // Task should fail immediately without retry (e.g., invalid data)
        if ( $invalid_data ) {
            throw new PigeonTaskFailWithoutRetryException( 'Invalid task arguments' );
        }

        // Note: PigeonTaskAlreadyExistsException is thrown automatically
        // when attempting to schedule duplicate tasks
    }
}
```

### Exception Handling Strategies

- **PigeonTaskException**: Use for temporary failures that might succeed on retry
- **PigeonTaskFailWithoutRetryException**: Use for permanent failures (bad data, 4xx HTTP errors)
- **Standard Exceptions**: Any other exception will be treated as a temporary failure

## Database Utilities

### Safe Table Naming

The `Safe_Dynamic_Prefix` utility prevents MySQL table name length violations:

```php
use StellarWP\Pigeon\Tables\Utility\Safe_Dynamic_Prefix;

// Automatically trims long prefixes to fit MySQL's 64-character limit
$safe_prefix = Safe_Dynamic_Prefix::get( 'very_long_application_prefix_name' );

// The utility considers the longest table name in Pigeon when calculating safe length
echo $safe_prefix; // Will be trimmed to ensure table names don't exceed 64 chars
```

### Advanced Query Operations

Use the `Custom_Table_Query_Methods` trait for complex database operations:

```php
use StellarWP\Pigeon\Traits\Custom_Table_Query_Methods;

class My_Custom_Table extends Table_Abstract {
    use Custom_Table_Query_Methods;

    public function get_high_priority_tasks() {
        // Complex query with joins and filtering
        return $this->query()
            ->select( [ 'tasks.*', 'logs.latest_entry' ] )
            ->join( 'task_logs as logs', 'tasks.id', 'logs.task_id' )
            ->where( 'priority', '>', 5 )
            ->order_by( 'created_at', 'DESC' )
            ->limit( 50 )
            ->get_results();
    }

    public function bulk_update_status( array $task_ids, string $status ) {
        // Efficient bulk operations
        return $this->update_many(
            [ 'status' => $status, 'updated_at' => time() ],
            [ 'id' => $task_ids ]
        );
    }

    public function search_tasks( string $search_term ) {
        // Search across multiple columns
        return $this->search(
            $search_term,
            [ 'task_class', 'data', 'status' ]
        );
    }
}
```

### Batch Processing

Process large datasets efficiently with generators:

```php
// Process tasks in batches to avoid memory issues
foreach ( $table->get_in_batches( 100 ) as $batch ) {
    foreach ( $batch as $task ) {
        // Process each task
        process_task( $task );
    }

    // Memory is released after each batch
}
```

## Logger Configuration

### Available Logger Types

Pigeon supports three logger implementations:

```php
use StellarWP\Pigeon\Config;
use StellarWP\Pigeon\Loggers\ActionScheduler_DB_Logger;
use StellarWP\Pigeon\Loggers\DB_Logger;
use StellarWP\Pigeon\Loggers\Null_Logger;

// Default: Use Action Scheduler's existing log table
Config::set_logger( new ActionScheduler_DB_Logger() );

// Alternative: Use dedicated Pigeon log tables
Config::set_logger( new DB_Logger() );

// For testing: Disable logging entirely
Config::set_logger( new Null_Logger() );
```

### ActionScheduler_DB_Logger Format

When using `ActionScheduler_DB_Logger`, logs are stored with a special format in the Action Scheduler logs table:

```
pigeon_{hook_prefix}||{task_id}||{type}||{level}||{json_entry}
```

This allows Pigeon to store its metadata while maintaining compatibility with Action Scheduler's existing structure.

### Custom Logger Implementation

Create custom loggers by implementing the `Logger` interface:

```php
use StellarWP\Pigeon\Contracts\Logger;

class Custom_Logger implements Logger {
    public function log( int $task_id, int $action_id, string $type, string $level, string $entry ): bool {
        // Custom logging logic (e.g., external service, file system)
        return $this->send_to_external_service( $task_id, $type, $level, $entry );
    }

    public function retrieve_logs( int $task_id ): array {
        // Return array of Log objects
        return $this->get_logs_from_external_service( $task_id );
    }
}

// Set custom logger before registration
Config::set_logger( new Custom_Logger() );
```

## Action Scheduler Integration

### Enhanced Action Scheduler Methods

The `Action_Scheduler_Methods` class provides additional functionality:

```php
use StellarWP\Pigeon\Action_Scheduler_Methods;

// Get action with enhanced error handling
$action = Action_Scheduler_Methods::get_action_by_id( $action_id );

// Bulk operations
$action_ids = Action_Scheduler_Methods::get_pending_actions_by_hook( 'my_hook' );
Action_Scheduler_Methods::cancel_actions_by_ids( $action_ids );

// Enhanced querying
$actions = Action_Scheduler_Methods::get_actions_by_status( 'failed', 50 );
```

### Custom Action Scheduler Hooks

Monitor Action Scheduler events related to Pigeon tasks:

```php
// Hook into Action Scheduler events
add_action( 'action_scheduler_stored_action', function( $action_id ) {
    // Action was stored in queue
    error_log( "Action {$action_id} was queued" );
} );

add_action( 'action_scheduler_canceled_action', function( $action_id ) {
    // Action was cancelled
    $task = get_task_by_action_id( $action_id );
    if ( $task ) {
        error_log( "Pigeon task {$task->id} was cancelled" );
    }
} );
```

## Performance Optimization

### Database Indexing

Pigeon tables include optimized indexes:

```sql
-- Tasks table indexes
INDEX `action_id` (action_id)
INDEX `args_hash` (args_hash)  -- For duplicate detection
INDEX `class_hash` (class_hash) -- For task type queries

-- Logs table indexes
INDEX `task_id` (task_id)       -- For log retrieval
INDEX `action_id` (action_id)   -- For Action Scheduler integration
```

### Memory Management

For high-volume task processing:

```php
// Use generators for large result sets
foreach ( Tasks::get_in_batches( 500 ) as $batch ) {
    process_batch( $batch );

    // Clear object caches periodically
    wp_cache_flush();
}

// Disable logging in high-volume scenarios
Config::set_logger( new Null_Logger() );
```

#### Task Deduplication

Pigeon automatically prevents duplicate tasks:

```php
// These will only create one task
pigeon()->dispatch( new Email_Task( 'user@example.com', 'Subject', 'Body' ) );
pigeon()->dispatch( new Email_Task( 'user@example.com', 'Subject', 'Body' ) ); // Ignored

// To force duplicates, vary the arguments
pigeon()->dispatch( new Email_Task( 'user@example.com', 'Subject', 'Body', [], [], time() ) );
```

## Admin UI AJAX Integration

The admin UI provides real-time data updates through AJAX:

### AJAX Endpoint Configuration

```php
// The admin provider registers the AJAX endpoint
add_action( 'wp_ajax_shepherd_get_tasks', [ $provider, 'ajax_get_tasks' ] );

// Endpoint handles:
// - Security with nonce verification
// - Permission checks
// - Dynamic filtering and sorting
// - Pagination
// - Search functionality
```

### Filter Processing

The AJAX endpoint processes filter parameters:

```php
// task_type filters are mapped to class_hash for efficiency
if ( $filter['field'] === 'task_type' ) {
    $args[] = [
        'column' => 'class_hash',
        'value' => md5( $filter['value'] ),
        'operator' => $filter['operator'] === 'isNot' ? '!=' : '='
    ];
}

// Other filters are applied directly
$args[] = [
    'column' => $filter['field'],
    'value' => $filter['value'],
    'operator' => $filter['operator'] === 'isNot' ? '!=' : '='
];
```

### Performance Optimization

- **Hybrid Loading**: Initial page load includes default data, subsequent requests use AJAX
- **Server-side Processing**: All filtering, sorting, and searching on the server
- **Efficient Queries**: JOIN operations with Action Scheduler for status information
- **Minimal Data Transfer**: Only necessary columns selected

## AS_Actions Table Interface

The `AS_Actions` class provides a read-only interface to Action Scheduler's actions table:

```php
use StellarWP\Pigeon\Tables\AS_Actions;

// Get table name
$table_name = AS_Actions::table_name(); // wp_actionscheduler_actions

// Available columns for JOIN operations
$columns = AS_Actions::get_columns();
// Returns: action_id (BIGINT), status (VARCHAR)

// Searchable columns
$searchable = AS_Actions::get_searchable_columns();
// Returns: ['status']
```

### Usage in JOIN Queries

```php
// Enable status filtering without data duplication
$tasks_with_status = Tasks::paginate(
    [
        'orderby' => 'status',
        [
            'column' => 'status',
            'value' => 'complete',
            'operator' => '='
        ]
    ],
    20,
    1,
    AS_Actions::class,     // Join table
    'action_id=action_id', // JOIN condition
    ['status']             // Additional columns
);
```

### Benefits

- **No Data Duplication**: Status stored only in Action Scheduler
- **Real-time Accuracy**: Always current status information
- **Performance**: Optimized JOIN queries
- **Consistency**: Single source of truth for action status
