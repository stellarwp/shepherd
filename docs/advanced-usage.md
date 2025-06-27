# Advanced Usage

This guide covers the more advanced features of Pigeon, for when you need more control over your background tasks.

## Automatic Retries

Pigeon can automatically retry failed tasks. To enable this, you can override the `get_max_retries()` method on your task class.

The default value is `0`, which means the task is not retryable.

If you want to retry the task a TOTAL of 3 times, the `get_max_retries()` method should return `(int) 2`. All the tasks are tried once, so a task that also has
2 retries will be tried a total of 3 times.

```php
<?php

namespace My\App\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;
use StellarWP\Pigeon\Exceptions\PigeonTaskException;

class My_Retryable_Task extends Task_Abstract {
    public function get_max_retries(): int {
        return 3;
    }

    public function process(): void {
        $result = some_flaky_operation();

        if ( ! $result ) {
            throw new PigeonTaskException( 'The operation failed.' );
        }
    }
}
```

In this example, if `some_flaky_operation()` fails and a `PigeonTaskException` is thrown, Pigeon will automatically reschedule the task to be retried later. It will attempt this a total of 3 times before marking the task as permanently failed.

## Debouncing

Debouncing is a practice used to control how often a task is executed. Pigeon supports debouncing out of the box.

### Basic Debouncing

To enable debouncing, you need to implement the `is_debouncable()` method on your task and have it return `true`. You can also specify a debounce delay using `get_debounce_delay()`.

```php
<?php

namespace My\App\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;

class My_Debounced_Task extends Task_Abstract {
    public function is_debouncable(): bool {
        return true;
    }

    public function get_debounce_delay(): int {
        return 5 * MINUTE_IN_SECONDS;
    }

    public function process(): void {
        // ...
    }
}
```

When this task is dispatched, Pigeon will wait 5 minutes before executing it. If the same task is dispatched again within that 5-minute window, the original task will be cancelled and the new one will be scheduled, effectively resetting the timer.

### Debouncing on Failure

You can also specify a different debounce delay for when a task fails, using the `get_debounce_delay_on_failure()` method. This is useful for implementing exponential backoff strategies for retries.

```php
public function get_debounce_delay_on_failure(): int {
    // Retry after 30 seconds.
    return 30;
}
```

## Unique Tasks

By default, Pigeon will prevent the same task (i.e., a task of the same class with the same arguments) from being scheduled more than once. When you dispatch a task that is already scheduled, Pigeon will simply do nothing.

You can customize this behavior by overriding the `is_unique()` method on your task.

```php
public function is_unique(): bool {
    // Allow multiple instances of this task to be scheduled.
    return false;
}
```

## Logging

Pigeon has a built-in logging system that records the lifecycle of each task. Logs are stored in a custom database table (`pigeon_task_logs`).

The following events are logged:

- `created`: When a task is first scheduled.
- `started`: When a task begins processing.
- `finished`: When a task completes successfully.
- `failed`: When a task fails permanently (i.e., all retries have been exhausted).
- `rescheduled`: When a task is rescheduled (e.g., for a retry).
- `retrying`: When a retry attempt is about to be made.
- `cancelled`: When a task is cancelled.

You can retrieve the logs for a specific task using the `DB_Logger`.

```php
use StellarWP\Pigeon\Contracts\Logger;
use StellarWP\Pigeon\Provider;

$logger = Provider::get_container()->get( Logger::class );
$logs = $logger->retrieve_logs( $task_id );
```

You can also implement your own logger by implementing the `Logger` interface.
Then feed your logger to Pigeon using the `Config::set_logger()` method.
This should be done before the `Provider::register()` method is called.
