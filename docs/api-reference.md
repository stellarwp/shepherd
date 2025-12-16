# API Reference

This document provides a comprehensive reference for all public classes, interfaces, and methods in the Shepherd library.

## Table of Contents

- [Core Classes](#core-classes)
- [Interfaces](#interfaces)
- [Abstract Classes](#abstract-classes)
- [Exceptions](#exceptions)
- [Helper Functions](#helper-functions)

## Core Classes

### `Regulator`

The main orchestrator for task scheduling and processing.

#### Methods

##### `dispatch( Task $task, int $delay = 0 ): self`

Schedules a task for execution.

- **Parameters:**
  - `$task` - The task instance to schedule
  - `$delay` - Delay in seconds before execution (default: 0)
- **Returns:** The Regulator instance for method chaining
- **Throws:** and **Catches:** `ShepherdTaskAlreadyExistsException` if duplicate task exists
- **Throws:** and **Catches:** `RuntimeException` if task fails to be scheduled or inserted into the database.
- **Since 0.0.7 - Synchronous Fallback:** When Shepherd tables are not registered:
  - Tasks are processed immediately in a synchronous manner by default
  - Fires `shepherd_{prefix}_dispatched_sync` action when processing synchronously
  - Can be disabled via `shepherd_{prefix}_should_dispatch_sync_on_tables_unavailable` filter
- **Hook Integration:** As of version 0.0.7, uses `action_scheduler_init` hook instead of `init` to ensure Action Scheduler is ready.
- You can listen for those errors above, by listening to the following actions:
  - `shepherd_{prefix}_task_scheduling_failed`
  - `shepherd_{prefix}_task_already_exists`

##### `run( array $tasks, array $callables = [] ): void`

Runs a set of tasks synchronously with optional lifecycle callbacks.

- **Parameters:**
  - `$tasks` - Array of Task instances to run
  - `$callables` - Optional array of lifecycle callbacks:
    - `'before'` - `function( Task $task ): void` - Called before each task runs
    - `'after'` - `function( Task $task ): void` - Called after each task completes
    - `'always'` - `function( array $tasks ): void` - Called after all tasks complete (even on error)
- **Since:** 0.1.0
- **Behavior:**
  - When tables are registered: Dispatches tasks if not already scheduled, then processes them immediately using Action Scheduler's queue runner
  - When tables are NOT registered: Processes tasks immediately in a synchronous manner (fallback)
  - Tasks already scheduled (via `dispatch()`) will be executed without re-dispatching
- **Actions Fired:**
  - `shepherd_{prefix}_task_run_sync` - When tables are not registered and task runs synchronously
  - `shepherd_{prefix}_task_before_run` - Before each task is processed
  - `shepherd_{prefix}_task_after_run` - After each task completes successfully
  - `shepherd_{prefix}_tasks_run_failed` - When a task fails during the run
  - `shepherd_{prefix}_tasks_finished` - After all tasks have been processed
- **Use Cases:**
  - CLI commands that need immediate task execution
  - REST API endpoints that need synchronous task processing
  - Testing scenarios requiring controlled task execution

##### `get_last_scheduled_task_id(): ?int`

Returns the ID of the most recently scheduled task.

##### `get_hook(): string`

Returns the Action Scheduler hook name for task processing.

##### `bust_runtime_cached_tasks(): void`

Clears the runtime cache of task data.

---

### `Config`

Static configuration management for Shepherd.

#### Methods

##### `set_hook_prefix( string $prefix ): void`

Sets the hook prefix for your application (required).

- **Parameters:**
  - `$prefix` - Unique prefix for your application

##### `get_hook_prefix(): string`

Returns the configured hook prefix.

##### `get_safe_hook_prefix(): string`

Returns the hook prefix trimmed to a safe length to ensure table names don't exceed MySQL's 64-character limit.

##### `get_max_hook_prefix_length(): int`

Returns the maximum safe length for a hook prefix based on:

- WordPress table prefix length
- The longest Shepherd table name

##### `set_logger( ?Logger $logger ): void`

Sets a custom logger implementation.

- **Parameters:**
  - `$logger` - Logger instance or null for default

##### `get_logger(): Logger`

Returns the configured logger (defaults to DB_Logger).

##### `reset(): void`

Resets all configuration to defaults.

- **Warning:** This should only be used in testing scenarios, not in production code.

---

### `Provider`

Service provider for dependency injection and initialization.

#### Constants

- `VERSION` - Shepherd's current version

#### Methods

##### `register(): void`

Initializes Shepherd and registers all components.

- **Since version 0.0.7:** The Regulator is only registered after tables are successfully created/updated via the `shepherd_{prefix}_tables_registered` action.

##### `set_container( ContainerInterface $container ): void`

Sets the dependency injection container.

##### `get_container(): ContainerInterface`

Returns the container instance.

##### `is_registered(): bool`

Checks if Shepherd has been registered.

##### `register_regulator(): void`

Registers the Regulator component to start processing tasks.

- **Since:** 0.0.7
- **Visibility:** Public
- **Purpose:** Separated from the main registration flow to allow for conditional registration
- **Behavior:**
  - Retrieves the Regulator instance from the DI container
  - Calls the Regulator's `register()` method to initialize task processing
- **Hook:** Automatically called on `shepherd_{prefix}_tables_registered` action
- **Usage:** Can be manually removed from the action hook if custom registration timing is needed

##### `delete_tasks_on_action_deletion( int $action_id ): void`

Automatically removes task data when Action Scheduler deletes an action.

- **Parameters:**
  - `$action_id` - The Action Scheduler action ID being deleted
- **Behavior:**
  - Queries for tasks associated with the action ID
  - Removes corresponding logs from `shepherd_task_logs`
  - Removes task records from `shepherd_tasks`
  - No-op if no tasks are associated with the action ID
- **Hook:** Automatically called on `action_scheduler_deleted_action`

---

### `Action_Scheduler_Methods`

Wrapper class for Action Scheduler integration (since 0.0.1).

#### Methods

##### `has_scheduled_action( string $hook, array $args = [], string $group = '' ): bool`

Checks if an action is scheduled.

- **Parameters:**
  - `$hook` - The hook of the action
  - `$args` - The arguments of the action
  - `$group` - The group of the action
- **Returns:** Whether the action is scheduled

##### `schedule_single_action( int $timestamp, string $hook, array $args = [], string $group = '', bool $unique = false, int $priority = 10 ): int`

Schedules a single action.

- **Parameters:**
  - `$timestamp` - The timestamp when the action should run
  - `$hook` - The hook of the action
  - `$args` - The arguments of the action
  - `$group` - The group of the action
  - `$unique` - Whether the action should be unique
  - `$priority` - The priority of the action (0-255)
- **Returns:** The action ID, or 0 if scheduling failed (since 0.0.7)

##### `get_action_by_id( int $action_id ): ActionScheduler_Action`

Gets an action by its ID.

- **Parameters:**
  - `$action_id` - The action ID
- **Returns:** The action object
- **Throws:** `RuntimeException` if the action is not found

##### `get_actions_by_ids( array $action_ids ): array`

Gets multiple actions by their IDs.

- **Parameters:**
  - `$action_ids` - Array of action IDs
- **Returns:** Array of ActionScheduler_Action objects keyed by ID
- **Throws:** `RuntimeException` if any action is not found

##### `get_pending_actions_by_ids( array $action_ids ): array`

Gets pending actions by their IDs, excluding finished and null actions.

- **Parameters:**
  - `$action_ids` - Array of action IDs
- **Returns:** Array of pending ActionScheduler_Action objects
- **Since 0.0.7:** Also excludes `ActionScheduler_NullAction` instances

---

### `Email` Task

Built-in task for sending emails asynchronously.

#### Constructor

```php
public function __construct(
    string $to_email,
    string $subject,
    string $body,
    array $headers = [],
    array $attachments = []
)
```

#### Configuration

- **Task Prefix:** `shepherd_email_`
- **Max Retries:** 4
- **Retry Delay:** 30 seconds (exponential backoff: 30s then 60s then 120s then 240s and so on)
- **Group:** `shepherd_{prefix}_queue_default`
- **Priority:** 10

#### WordPress Hooks

- `shepherd_{prefix}_email_processed` - Fired after a successful call to `wp_mail()`

---

### `HTTP_Request` Task

Built-in task for making HTTP requests asynchronously.

#### Constructor

```php
public function __construct(
    string $url,
    array $args = [],
    string $method = 'GET'
)
```

#### Configuration

- **Task Prefix:** `shepherd_http_`
- **Max Retries:** 10
- **Retry Delay:** Exponential backoff
- **Default Timeout:** 3 seconds
- **Default Args:** Compression enabled, 5 redirects, reject unsafe URLs
- **Group:** `shepherd_{prefix}_queue_default`
- **Priority:** 10

#### Supported Methods

- `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`

#### Additional Methods

```php
public function get_url(): string
public function get_method(): string
public function get_request_args(): array
public function get_auth_headers(): array
```

#### Error Handling

- **WP_Error responses**: Fail immediately without retry (throws `ShepherdTaskFailWithoutRetryException`)
- **4xx HTTP errors**: Fail immediately without retry (throws `ShepherdTaskFailWithoutRetryException`)
- **5xx HTTP errors**: Retry with exponential backoff (throws `ShepherdTaskException`)
- **Other non-2xx**: Retry with exponential backoff (throws `ShepherdTaskException`)

#### WordPress Hooks

- `shepherd_{prefix}_http_request_processed` - Fired after successful HTTP request
- `shepherd_{prefix}_task_failed_without_retry` - Fired when task fails without retry (4xx errors, WP_Error)

#### Special Features

- **Authentication Headers**: Override `get_auth_headers()` to add auth without storing credentials in database
- **Task ID Header**: Automatically adds `X-Shepherd-Task-ID` header with task ID
- **Security Defaults**: URL validation, compression, and redirect limits enabled by default

---

## Interfaces

### `Task`

Main interface for all tasks.

```php
interface Task extends Task_Model {
    public function process(): void;
    public function get_group(): string;
    public function get_priority(): int;
    public function get_max_retries(): int;
    public function get_retry_delay(): int;
}
```

### `Task_Model`

Model interface for task persistence.

```php
interface Task_Model extends Model {
    public function get_args(): array;
    public function set_args( array $args ): void;
    public function get_task_prefix(): string;
    public function get_args_hash(): string;
    public function get_class_hash(): string;
    public function get_action_id(): ?int;
    public function set_action_id( ?int $action_id ): void;
    public function get_current_try(): int;
    public function set_current_try( int $current_try ): void;
}
```

### `Model`

Base model interface for all entities.

```php
interface Model {
    public function get_id(): ?int;
    public function set_id( ?int $id ): void;
    public function save(): bool;
    public function delete(): bool;
    public function to_array(): array;
}
```

### `Logger`

Logging interface extending PSR-3.

```php
interface Logger extends LoggerInterface {
    public function retrieve_logs( int $task_id ): array;
}
```

---

## Abstract Classes

### `Task_Abstract`

Base implementation for custom tasks.

#### Methods

##### `__construct( ...$args )`

Constructor that validates and stores task arguments.

- **Validation:**
  - No callables allowed
  - Objects must implement `JsonSerializable`

##### `process(): void`

Abstract method - implement your task logic here.

##### `get_task_prefix(): string`

Abstract method - return a unique prefix (max 15 characters).

##### `get_group(): string`

Returns the task group (default: `shepherd_{prefix}_queue_default`).

##### `get_priority(): int`

Returns the task priority 0-255 (default: 10).

##### `get_max_retries(): int`

Returns max retry attempts (default: 0).

##### `get_retry_delay(): int`

Returns delay between retries in seconds (default: exponential backoff).

##### `validate_args( ...$args ): void`

Protected method for custom argument validation.

---

## Exceptions

### `ShepherdTaskException`

General exception for task-related errors.

```php
throw new ShepherdTaskException( 'Task processing failed' );
```

### `ShepherdTaskAlreadyExistsException`

Thrown and caught when attempting to schedule a duplicate task.

### `ShepherdTaskFailWithoutRetryException`

Thrown when a task encounters an error that should not be retried (e.g., 4xx HTTP errors, WP_Error responses).

---

## Helper Functions

### `shepherd(): Regulator`

Global helper function to access the Regulator instance.

```php
// Dispatch a task
shepherd()->dispatch( new My_Task() );

// Get last task ID
$task_id = shepherd()->get_last_scheduled_task_id();
```

---

## Logger Implementations

### `DB_Logger`

Default logger that stores logs in the database.

- Table: `shepherd_{prefix}_task_logs`
- Implements PSR-3 log levels
- Stores logs as JSON

### `Null_Logger`

No-op logger for disabling logging.

```php
Config::set_logger( new Null_Logger() );
```

---

## Database Tables

### Tasks Table

Table name: `shepherd_{prefix}_tasks`

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `action_id` | BIGINT | Action Scheduler ID |
| `class_hash` | VARCHAR(191) | Hash of task class |
| `args_hash` | VARCHAR(191) | Hash of class + arguments |
| `data` | LONGTEXT | JSON encoded task data |
| `current_try` | BIGINT | Current retry attempt |

### Task Logs Table

Table name: `shepherd_{prefix}_task_logs`

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `task_id` | BIGINT | Related task ID |
| `date` | TIMESTAMP | Log timestamp |
| `level` | VARCHAR(191) | PSR-3 log level |
| `type` | VARCHAR(191) | Log type |
| `entry` | LONGTEXT | JSON log data |

---

## WordPress Integration

### Actions

- `shepherd_{prefix}_tables_registered` - Fired when Shepherd tables are successfully registered (since 0.0.7)
  - No parameters
  - Used internally to ensure safe initialization of the Regulator

- `shepherd_{prefix}_tables_error` - Fired when database table creation/update fails (since 0.0.7)
  - Parameters: `$exception` (DatabaseQueryException)

- `shepherd_{prefix}_task_scheduling_failed` - Fired when a task fails to be scheduled
  - Parameters: `$task`, `$exception`

- `shepherd_{prefix}_task_already_scheduled` - Fired when a task already exists
  - Parameters: `$task`

- `shepherd_{prefix}_task_started` - Fired when a task starts being processed
  - Parameters: `$task`, `$action_id` (int)

- `shepherd_{prefix}_task_finished` - Fired when a task finishes processing successfully
  - Parameters: `$task`, `$action_id` (int)

- `shepherd_{prefix}_task_failed` - Fired when a task fails
  - Parameters: `$task`, `$exception`

- `shepherd_{prefix}_task_failed_without_retry` - Fired when a task fails without retry
  - Parameters: `$task`, `$exception` (ShepherdTaskFailWithoutRetryException)

- `shepherd_{prefix}_email_processed` - Fired after a successful call to `wp_mail()`
  - Parameters: `$task` (Email instance)

- `shepherd_{prefix}_http_request_processed` - Fired after successful HTTP request
  - Parameters: `$task` (HTTP_Request instance), `$response` (wp_remote_request response array)

- `shepherd_{prefix}_task_run_sync` - Fired when a task is run synchronously via `run()` when tables are not registered (since 0.1.0)
  - Parameters: `$task` (Task instance)

- `shepherd_{prefix}_task_before_run` - Fired before a task is processed via `run()` (since 0.1.0)
  - Parameters: `$task` (Task instance)

- `shepherd_{prefix}_task_after_run` - Fired after a task completes via `run()` (since 0.1.0)
  - Parameters: `$task` (Task instance)

- `shepherd_{prefix}_tasks_run_failed` - Fired when a task fails during `run()` (since 0.1.0)
  - Parameters: `$task` (Task instance or null), `$exception` (Exception)

- `shepherd_{prefix}_tasks_finished` - Fired after all tasks have been processed via `run()` (since 0.1.0)
  - Parameters: `$tasks` (array of Task instances)

### Filters

- `shepherd_{prefix}_should_log` - Filter to control whether logging should occur (since 0.0.5)
  - Parameters: `$should_log` (bool, default: true)
  - Return false to disable logging
