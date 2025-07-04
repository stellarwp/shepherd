# Built-in Tasks

Pigeon comes with pre-packaged tasks to handle common background operations. Each task is designed to be reliable, well-tested, and includes automatic retry logic.

## Available Tasks

### [Email Task](./tasks/email.md)

Sends emails asynchronously using WordPress's `wp_mail()` function.

**Key Features:**

- Automatic retries (up to 4 additional attempts)
- Support for HTML content and attachments
- Comprehensive error handling
- WordPress action hooks for tracking

**Quick Example:**

```php
use StellarWP\Pigeon\Tasks\Email;

$email = new Email(
    'user@example.com',
    'Welcome!',
    'Thanks for signing up!',
    ['Content-Type: text/html; charset=UTF-8']
);

pigeon()->dispatch( $email );
```

## Creating Your Own Tasks

To create custom tasks, extend the `Task_Abstract` class:

```php
<?php

namespace My\App\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;

class My_Custom_Task extends Task_Abstract {
    public function __construct( string $data ) {
        parent::__construct( $data );
    }

    public function process(): void {
        // Your task logic here
        $data = $this->get_args()[0];

        // Process the data
        if ( ! $this->process_data( $data ) ) {
            throw new \Exception( 'Processing failed' );
        }
    }

    public function get_task_prefix(): string {
        return 'my_custom_';
    }

    private function process_data( string $data ): bool {
        // Implementation details
        return true;
    }
}
```

## Task Design Principles

When creating tasks, follow these principles:

### 1. Idempotent Operations

A Task's `process()` method should be safe to run multiple times with different arguments:

### 2. Clear Error Handling

Throw exceptions for failures:

```php
public function process(): void {
    $result = $this->external_api_call();

    if ( ! $result ) {
        throw new \Exception( 'API call failed' );
    }
}
```

### 3. Minimal Dependencies

Keep tasks lightweight and focused:

```php
// Good: Simple, focused task
class Send_Welcome_Email extends Task_Abstract {
    public function process(): void {
        wp_mail( $this->get_args()[0], 'Welcome!', 'Thanks for joining!' );
    }
}

// Avoid: Complex, multi-purpose tasks
class Process_Everything extends Task_Abstract {
    public function process(): void {
        $this->send_email();
        $this->update_database();
        $this->call_external_api();
        $this->generate_report();
    }
}
```

Instead of doing everything in one task, you can create multiple tasks that are more focused and easier to test. On each task's `process()` method, you can fire an `action` than next tasks can listen to, or directly schedule the next task to be processed via `pigeon()->dispatch()`.

### 4. Proper Argument Validation

Validate inputs by:

1. Calling the parent constructor.
2. Overriding the `validate_args()` method.

```php
public function __construct( string $email, int $user_id ) {
    parent::__construct( $email, $user_id );
}

protected function validate_args(): void {
    if ( ! is_email( $this->get_args()[0] ) ) {
        throw new \InvalidArgumentException( 'Invalid email address' );
    }

    if ( $this->get_args()[1] <= 0 ) {
        throw new \InvalidArgumentException( 'Invalid user ID' );
    }
}
```

## Contributing Tasks

If you've created a useful task that could benefit others, consider contributing it to the Pigeon library:

1. Ensure your task follows WordPress coding standards
2. Include comprehensive PHPDoc comments
3. Add integration tests for your task in the `tests/integration/Tasks/` directory
4. Update documentation in the `docs/` directory
5. Submit a pull request

## Future Built-in Tasks

Planned tasks for future releases:

- **HTTP Request Task**: Make HTTP requests with retry logic
- **File Processing Task**: Process uploaded files asynchronously
- **Database Cleanup Task**: Periodic database maintenance
- **Cache Warming Task**: Pre-populate caches

Have ideas for built-in tasks? [Open an issue](https://github.com/stellarwp/pigeon/issues) to discuss your suggestions.
