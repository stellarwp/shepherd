# Built-in Tasks

Shepherd includes reliable, well-tested tasks for common background operations with automatic retry logic.

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
use StellarWP\Shepherd\Tasks\Email;

$email = new Email(
    'user@example.com',
    'Welcome!',
    'Thanks for signing up!',
    ['Content-Type: text/html; charset=UTF-8']
);

shepherd()->dispatch( $email );
```

### [HTTP Request Task](./tasks/http-request.md)

Makes asynchronous HTTP requests using WordPress's `wp_remote_request()`.

**Key Features:**

- Support for all HTTP methods (GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS)
- Smart retry logic: 5xx errors retry (up to 10 attempts), 4xx errors fail immediately
- Automatic security headers and authentication support
- Built-in compression, redirect handling, and URL validation
- WordPress action hooks for successful requests and failures
- Task ID header automatically added to requests

**Quick Example:**

```php
use StellarWP\Shepherd\Tasks\HTTP_Request;

// Simple GET request (uses default 3s timeout)
$request = new HTTP_Request( 'https://api.example.com/status' );
shepherd()->dispatch( $request );

// POST request with JSON data and custom timeout
$webhook = new HTTP_Request(
    'https://webhook.example.com/notify',
    [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => wp_json_encode(['event' => 'user_registered']),
        'timeout' => 60,
    ],
    'POST'
);
shepherd()->dispatch( $webhook );

// Custom authentication (extend the class)
class Authenticated_HTTP_Request extends HTTP_Request {
    public function get_auth_headers(): array {
        return [
            'Authorization' => 'Bearer ' . get_option( 'api_token' ),
        ];
    }
}
```

### [Herding Task](./tasks/herding.md)

Automatically cleans up orphaned task data to maintain database integrity.

**Key Features:**

- Automatic scheduling (every 6 hours)
- Removes orphaned task records and logs
- Safe database operations with prepared statements
- Completion hooks for extensibility
- No-op when no cleanup needed

**Automatic Usage:**

```php
// Runs automatically every 6 hours - no manual intervention needed
// Attached to WordPress 'init' hook with priority 20
```

**Manual Usage:**

```php
use StellarWP\Shepherd\Tasks\Herding;

// Dispatch immediately for manual cleanup
shepherd()->dispatch( new Herding() );

// Or schedule for later
shepherd()->dispatch( new Herding(), HOUR_IN_SECONDS );
```

## Creating Custom Tasks

Extend `Task_Abstract` for custom tasks:

```php
<?php

namespace My\App\Tasks;

use StellarWP\Shepherd\Abstracts\Task_Abstract;

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

Follow these principles when creating tasks:

### 1. Idempotent Operations

Tasks should be safe to run multiple times:

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

Create multiple focused tasks instead. Chain tasks by firing actions or directly scheduling via `shepherd()->dispatch()`.

### 4. Proper Argument Validation

Validate inputs by calling the parent constructor and overriding `validate_args()`:

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

To contribute useful tasks to Shepherd:

1. Follow WordPress coding standards
2. Include comprehensive PHPDoc comments
3. Add integration tests in `tests/integration/Tasks/`
4. Update documentation in `docs/`
5. Submit a pull request

## Future Built-in Tasks

Planned tasks for future releases:

- **File Processing Task**: Process uploaded files asynchronously
- **Database Cleanup Task**: Periodic database maintenance
- **Cache Warming Task**: Pre-populate caches
- **Bulk Operations Task**: Handle large data sets in chunks

Have ideas? [Open an issue](https://github.com/stellarwp/shepherd/issues) to discuss suggestions.
