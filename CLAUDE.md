# Pigeon Project Knowledge Base

This document provides a comprehensive overview of the Pigeon project, its architecture, and its core concepts. It is intended to be used as a reference for developers and AI assistants working on the project.

## 1. Project Overview

Pigeon is a lightweight and powerful background processing library for WordPress. It is built on top of the robust **Action Scheduler** library.

- **Purpose**: To provide a simple, fluent, and developer-friendly API for defining and dispatching asynchronous (background) tasks.
- **Core Technologies**: PHP, WordPress, Action Scheduler.
- **Key Features**: Fluent API, automatic retries, debouncing, detailed logging, and a set of pre-packaged, common-use tasks.

## 2. Core Concepts

### Tasks

The fundamental unit of work in Pigeon is a "Task".

- **Implementation**: All tasks are PHP classes that **must** extend `StellarWP\Pigeon\Abstracts\Task_Abstract`.
- **Arguments**: A task's arguments are passed to its constructor and **must** be forwarded to `parent::__construct(...)`. This allows Pigeon to serialize and store them.
- **Execution Logic**: The main logic of a task is placed within the `process()` method.
- **Failure Handling**: To signal a retryable failure, a task should throw a `StellarWP\Pigeon\Exceptions\PigeonTaskException`.

### The Regulator

The `Regulator` is the central class in Pigeon. It acts as the main API for interacting with the library.

- **Accessing**: It is accessed via the `StellarWP\Pigeon\pigeon()` helper function.
- **Dispatching**: The primary method is `dispatch( Task $task, int $delay = 0 )`, which schedules a task for execution.

### Logging

Pigeon features a detailed logging system that records the entire lifecycle of a task to the database.

- **Logger**: The default logger is `StellarWP\Pigeon\Loggers\DB_Logger`. Custom loggers can be created by implementing the `StellarWP\Pigeon\Contracts\Logger` interface.
- **Log Events**: Key events logged include `created`, `started`, `finished`, `failed`, `rescheduled`, and `retrying`.
- **Accessing Logs**: Logs for a task can be retrieved via `$logger->retrieve_logs( $task_id )`.

### Advanced Task Features

- **Retries**: A task can be made retryable by overriding the `get_max_retries()` method to return the number of desired retries.
- **Debouncing**: A task can be debounced by overriding `is_debouncable()` to return `true` and specifying a delay in `get_debounce_delay()`.
- **Uniqueness**: By default, tasks are unique based on their class and arguments. Dispatching an identical task that's already scheduled will be ignored. This can be changed by overriding `is_unique()`.

## 3. Testing Philosophy

The project maintains a high standard of testing, primarily through integration tests.

- **Framework**: Tests are built using `lucatume\WPBrowser\TestCase\WPTestCase`.
- **Mocking and Spies**:
    - WordPress functions (`wp_mail`, `do_action`, etc.) are mocked using the `With_Uopz` trait and its `set_fn_return()` method.
    - A `$spy = []` array is used within the mock closure to capture call arguments for later assertion. This is the preferred way to verify interactions.
- **Custom Test Traits**:
    - `With_AS_Assertions`: Provides helpers like `assertTaskHasActionPending()` and `assertTaskExecutesWithoutErrors()` to interact with Action Scheduler.
    - `With_Clock_Mock`: Used to freeze time (`freeze_time()`) for consistent testing of scheduled events.
    - `With_Log_Snapshot`: Provides `assertMatchesLogSnapshot()` to easily create and verify the entire log output for a task.

## 4. Documentation Structure

Project documentation is crucial and is maintained in Markdown files.

- **`README.md`**: The main entry point, providing a high-level overview and links to other docs.
- **`docs/getting-started.md`**: Covers installation and basic usage.
- **`docs/advanced-usage.md`**: Details advanced features like retries and debouncing.
- **`docs/tasks.md`**: An index file for pre-packaged tasks.
- **`docs/tasks/`**: A directory containing individual documentation for each pre-packaged task (e.g., `email.md`).
