# API Reference

This document provides a comprehensive reference for all public classes, interfaces, and methods in the Pigeon library.

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

##### `dispatch( Task $task, int $delay = 0 ): void`

Schedules a task for execution.

- **Parameters:**
  - `$task` - The task instance to schedule
  - `$delay` - Delay in seconds before execution (default: 0)
- **Throws:** and **Catches:** `PigeonTaskAlreadyExistsException` if duplicate task exists
- **Throws:** and **Catches:** `RuntimeException` if task fails to be scheduled or inserted into the database.
- You can listen for those errors above, by listening to the following actions:
  - `pigeon_{prefix}_task_scheduling_failed`
  - `pigeon_{prefix}_task_already_exists`

##### `get_last_scheduled_task_id(): ?int`

Returns the ID of the most recently scheduled task.

##### `get_hook(): string`

Returns the Action Scheduler hook name for task processing.

##### `bust_runtime_cached_tasks(): void`

Clears the runtime cache of task data.

---

### `Config`

Static configuration management for Pigeon.

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
- The longest Pigeon table name

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

- `VERSION` - Pigeon's current version

#### Methods

##### `register(): void`

Initializes Pigeon and registers all components.

##### `set_container( ContainerInterface $container ): void`

Sets the dependency injection container.

##### `get_container(): ContainerInterface`

Returns the container instance.

##### `is_registered(): bool`

Checks if Pigeon has been registered.

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

- **Task Prefix:** `pigeon_email_`
- **Max Retries:** 4
- **Retry Delay:** 30 seconds (exponential backoff: 30s then 60s then 120s then 240s and so on)
- **Group:** `pigeon_{prefix}_queue_default`
- **Priority:** 10

#### WordPress Hooks

- `pigeon_{prefix}_email_processed` - Fired after a successful call to `wp_mail()`

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

- **Task Prefix:** `pigeon_http_`
- **Max Retries:** 10
- **Retry Delay:** Exponential backoff
- **Default Timeout:** 3 seconds
- **Default Args:** Compression enabled, 5 redirects, reject unsafe URLs
- **Group:** `pigeon_{prefix}_queue_default`
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

- **WP_Error responses**: Fail immediately without retry (throws `PigeonTaskFailWithoutRetryException`)
- **4xx HTTP errors**: Fail immediately without retry (throws `PigeonTaskFailWithoutRetryException`)
- **5xx HTTP errors**: Retry with exponential backoff (throws `PigeonTaskException`)
- **Other non-2xx**: Retry with exponential backoff (throws `PigeonTaskException`)

#### WordPress Hooks

- `pigeon_{prefix}_http_request_processed` - Fired after successful HTTP request
- `pigeon_{prefix}_task_failed_without_retry` - Fired when task fails without retry (4xx errors, WP_Error)

#### Special Features

- **Authentication Headers**: Override `get_auth_headers()` to add auth without storing credentials in database
- **Task ID Header**: Automatically adds `X-Pigeon-Task-ID` header with task ID
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

Returns the task group (default: `pigeon_{prefix}_queue_default`).

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

### `PigeonTaskException`

General exception for task-related errors.

```php
throw new PigeonTaskException( 'Task processing failed' );
```

### `PigeonTaskAlreadyExistsException`

Thrown and caught when attempting to schedule a duplicate task.

### `PigeonTaskFailWithoutRetryException`

Thrown when a task encounters an error that should not be retried (e.g., 4xx HTTP errors, WP_Error responses).

---

## Helper Functions

### `pigeon(): Regulator`

Global helper function to access the Regulator instance.

```php
// Dispatch a task
pigeon()->dispatch( new My_Task() );

// Get last task ID
$task_id = pigeon()->get_last_scheduled_task_id();
```

---

## Logger Implementations

### `DB_Logger`

Default logger that stores logs in the database.

- Table: `pigeon_{prefix}_task_logs`
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

Table name: `pigeon_{prefix}_tasks`

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `action_id` | BIGINT | Action Scheduler ID |
| `class_hash` | VARCHAR(191) | Hash of task class |
| `args_hash` | VARCHAR(191) | Hash of class + arguments |
| `data` | LONGTEXT | JSON encoded task data |
| `current_try` | BIGINT | Current retry attempt |

### Task Logs Table

Table name: `pigeon_{prefix}_task_logs`

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

- `pigeon_{prefix}_task_scheduling_failed` - Fired when a task fails to be scheduled
  - Parameters: `$task`, `$exception`

- `pigeon_{prefix}_task_already_scheduled` - Fired when a task already exists
  - Parameters: `$task`

- `pigeon_{prefix}_task_started` - Fired when a task starts being processed
  - Parameters: `$task`, `$action_id` (int)

- `pigeon_{prefix}_task_finished` - Fired when a task finishes processing successfully
  - Parameters: `$task`, `$action_id` (int)

- `pigeon_{prefix}_task_failed` - Fired when a task fails
  - Parameters: `$task`, `$exception`

- `pigeon_{prefix}_task_failed_without_retry` - Fired when a task fails without retry
  - Parameters: `$task`, `$exception` (PigeonTaskFailWithoutRetryException)

- `pigeon_{prefix}_email_processed` - Fired after a successful call to `wp_mail()`
  - Parameters: `$task` (Email instance)

- `pigeon_{prefix}_http_request_processed` - Fired after successful HTTP request
  - Parameters: `$task` (HTTP_Request instance), `$response` (wp_remote_request response array)

### Filters

Currently, Pigeon does not provide any filters.
