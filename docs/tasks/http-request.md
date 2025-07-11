# HTTP Request Task

The `HTTP_Request` task provides a robust way to make HTTP requests asynchronously using WordPress's `wp_remote_request()` function. It includes smart retry logic, comprehensive security defaults, and automatic authentication header support.

## Constructor

```php
public function __construct(
    string $url,
    array $args = [],
    string $method = 'GET'
)
```

### Parameters

- **`$url`** (string, required): The URL to send the request to
- **`$args`** (array, optional): Request arguments (headers, body, timeout, etc.)
- **`$method`** (string, optional): HTTP method. Default 'GET'

### Supported HTTP Methods

- `GET` - Retrieve data from the server
- `POST` - Send data to the server
- `PUT` - Update/replace data on the server
- `PATCH` - Partially update data on the server
- `DELETE` - Remove data from the server
- `HEAD` - Retrieve headers only
- `OPTIONS` - Get allowed methods/capabilities

## Configuration

- **Task Prefix**: `shepherd_http_`
- **Max Retries**: 10 additional attempts (11 total attempts)
- **Retry Delay**: Exponential backoff using base class defaults
- **Default Timeout**: 3 seconds
- **Priority**: 10 (default)
- **Group**: `shepherd_{prefix}_queue_default`

## Default Request Arguments

The task automatically applies these secure defaults:

```php
[
    'timeout'            => 3,                // Fast timeout for background tasks
    'reject_unsafe_urls' => true,            // Validate URLs through wp_http_validate_url()
    'compress'           => true,            // Enable compression
    'decompress'         => true,            // Enable decompression
    'redirection'        => 5,               // Follow up to 5 redirects
]
```

## Error Handling & Retry Logic

The HTTP_Request task uses intelligent error handling:

### Immediate Failure (No Retry)

- **WP_Error responses**: Network failures, DNS issues, etc.
- **4xx HTTP errors**: Client errors (400, 401, 403, 404, etc.)

These throw `ShepherdTaskFailWithoutRetryException` and do not retry.

### Retryable Failures

- **5xx HTTP errors**: Server errors (500, 502, 503, etc.)
- **Other non-2xx responses**: Redirects that exceed limits, etc.

These throw `ShepherdTaskException` and retry up to 10 times with exponential backoff.

## Usage Examples

### Basic GET Request

```php
use StellarWP\Shepherd\Tasks\HTTP_Request;
use function StellarWP\Shepherd\shepherd;

// Simple GET request with 3-second timeout
$request = new HTTP_Request( 'https://api.example.com/status' );
shepherd()->dispatch( $request );
```

### POST Request with JSON Body

```php
// POST request with custom headers and timeout
$webhook = new HTTP_Request(
    'https://webhook.example.com/notify',
    [
        'headers' => [
            'Content-Type' => 'application/json',
            'User-Agent'   => 'MyApp/1.0',
        ],
        'body'    => wp_json_encode([
            'event'     => 'user_registered',
            'user_id'   => 123,
            'timestamp' => time(),
        ]),
        'timeout' => 10, // Override default 3s timeout
    ],
    'POST'
);
shepherd()->dispatch( $webhook );
```

### Custom Authentication

Instead of storing credentials in the database, extend the class to provide authentication:

```php
class Authenticated_API_Request extends HTTP_Request {
    public function get_auth_headers(): array {
        return [
            'Authorization' => 'Bearer ' . get_option('api_token'),
            'X-API-Key'     => get_option('api_secret'),
        ];
    }
}

// Use the authenticated version
$request = new Authenticated_API_Request(
    'https://api.example.com/protected',
    ['timeout' => 30]
);
shepherd()->dispatch( $request );
```

## Special Features

### Task ID Header

Every request automatically includes an `X-Shepherd-Task-ID` header containing the task's database ID. This helps with debugging and request tracking.

### Security Defaults

- URLs are validated through `wp_http_validate_url()`
- Unsafe URLs are automatically rejected
- Compression is enabled by default for efficiency
- Redirect limits prevent infinite loops

## WordPress Hooks

### Success Hook

When a request completes successfully (2xx response), this action fires:

```php
/**
 * Fires after successful HTTP request.
 *
 * @param HTTP_Request $task     The task instance
 * @param array        $response Full wp_remote_request response
 */
do_action( 'shepherd_{prefix}_http_request_processed', $task, $response );
```

### Failure Hook

When a request fails without retry (4xx errors, WP_Error):

```php
/**
 * Fires when HTTP request fails without retry.
 *
 * @param HTTP_Request                        $task      The task instance
 * @param ShepherdTaskFailWithoutRetryException $exception The exception
 */
do_action( 'shepherd_{prefix}_task_failed_without_retry', $task, $exception );
```

## Error Examples

### 4xx Client Error (No Retry)

```php
$request = new HTTP_Request( 'https://api.example.com/nonexistent' );
shepherd()->dispatch( $request );

// If API returns 404, task fails immediately:
// ShepherdTaskFailWithoutRetryException:
// HTTP GET request to https://api.example.com/nonexistent returned error 404: `Not Found`
```

### 5xx Server Error (With Retry)

```php
$request = new HTTP_Request( 'https://unstable-api.example.com/data' );
shepherd()->dispatch( $request );

// If API returns 500, task retries up to 10 times:
// ShepherdTaskException:
// HTTP GET request to https://unstable-api.example.com/data returned error 500: `Internal Server Error`
```

### Network Error (No Retry)

```php
$request = new HTTP_Request( 'https://nonexistent-domain.invalid' );
shepherd()->dispatch( $request );

// DNS failure results in immediate failure:
// ShepherdTaskFailWithoutRetryException:
// HTTP GET request to https://nonexistent-domain.invalid failed with code: `http_request_failed` and message: `Could not resolve host`
```

## Best Practices

1. **Use short timeouts** for background tasks (3-10 seconds)
2. **Extend for authentication** rather than storing credentials in args
3. **Handle both success and failure hooks** for complete monitoring
4. **Use appropriate HTTP methods** (GET for retrieval, POST for creation, etc.)
5. **Structure JSON payloads** properly with `wp_json_encode()`
6. **Consider idempotency** for operations that might retry

## Performance Notes

- Default 3-second timeout keeps background processing fast
- Compression reduces bandwidth usage
- Smart retry logic avoids wasting resources on permanent failures
- Task ID headers help with debugging without impacting performance
