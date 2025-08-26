# Email Task

The `Email` task provides a simple and reliable way to send emails asynchronously. It leverages WordPress's `wp_mail()` function and includes automatic retries if the email fails to send.

## Constructor

```php
public function __construct(
    string $to_email,
    string $subject,
    string $body,
    array $headers = [],
    array $attachments = []
)
```

### Parameters

- **`$to_email`** (string, required): Recipient's email address(es). Can be a single email or multiple comma-separated emails.
- **`$subject`** (string, required): Email subject line
- **`$body`** (string, required): Email body content (HTML or plain text)
- **`$headers`** (array, optional): Email headers (e.g., content type, reply-to)
- **`$attachments`** (array, optional): File paths to attach to the email

## Configuration

- **Task Prefix**: `shepherd_email_`
- **Max Retries**: 4 additional attempts (5 total attempts)
- **Retry Delay**: 30 seconds between attempts
- **Priority**: 10 (default)
- **Group**: `shepherd_{prefix}_queue_default`

## Usage Examples

### Basic Email

```php
use StellarWP\Shepherd\Tasks\Email;
use function StellarWP\Shepherd\shepherd;

// Simple text email
$email = new Email(
    'user@example.com',
    'Welcome to our service',
    'Thank you for signing up!'
);

shepherd()->dispatch( $email );
```

### HTML Email with Headers

```php
$email = new Email(
    'user@example.com',
    'Your Account Update',
    '<h1>Account Updated</h1><p>Your account information has been successfully updated.</p>',
    [
        'Content-Type: text/html; charset=UTF-8',
        'From: noreply@example.com',
        'Reply-To: support@example.com'
    ]
);

shepherd()->dispatch( $email );
```

### Email to Multiple Recipients

```php
// Send to multiple recipients
$email = new Email(
    'user1@example.com, user2@example.com, admin@example.com',
    'Team Update',
    'Important update for all team members.'
);

shepherd()->dispatch( $email );

// With proper spacing (whitespace is automatically handled)
$email = new Email(
    'user1@example.com,user2@example.com, user3@example.com',
    'Newsletter',
    '<h1>Weekly Newsletter</h1><p>Here are this week\'s updates...</p>',
    [ 'Content-Type: text/html; charset=UTF-8' ]
);

shepherd()->dispatch( $email );
```

### Email with Attachments

```php
$email = new Email(
    'user@example.com',
    'Your Weekly Report',
    '<h1>Weekly Report</h1><p>Please find your report attached.</p>',
    [ 'Content-Type: text/html; charset=UTF-8' ],
    [
        WP_CONTENT_DIR . '/uploads/reports/weekly-report.pdf',
        WP_CONTENT_DIR . '/uploads/reports/summary.xlsx'
    ]
);

shepherd()->dispatch( $email );
```

### Delayed Email

```php
// Send email in 1 hour
$email = new Email(
    'user@example.com',
    'Reminder: Your appointment is tomorrow',
    'This is a friendly reminder about your appointment.'
);

shepherd()->dispatch( $email, HOUR_IN_SECONDS );
```

## Error Handling

The Email task automatically handles failures and retries. Common scenarios:

### Temporary SMTP Issues

- Automatically retries up to 4 times
- Uses 30-second delays between attempts
- Logs each attempt for debugging

### Invalid Email Addresses

- Task fails immediately (no retries for validation errors)
- Error logged for debugging

### Missing Attachments

- Task fails if attachment files don't exist
- Check file paths before dispatching

## WordPress Integration

### Action Hook

The Email task fires a WordPress action after successfully sending:

```php
add_action( 'shepherd_{prefix}_email_processed', function( $task ) {
    // Track successful email sending
    error_log( "Email sent to: {$task->get_args()[0]} with subject: {$task->get_args()[1]}" );
}, 10, 1 );
```

### Filtering wp_mail

Since the Email task uses `wp_mail()`, all WordPress email filters apply:

```php
// Override email settings
add_filter( 'wp_mail_from', function() {
    return 'noreply@mysite.com';
} );

add_filter( 'wp_mail_from_name', function() {
    return 'My Site';
} );
```

## Logging

Email tasks are automatically logged with these events:

- **created**: Email task scheduled
- **started**: Email processing began
- **finished**: Email sent successfully
- **failed**: Email failed to send (after all retries)
- **rescheduled**: Email task rescheduled
- **retrying**: Retry attempt starting

### Retrieving Logs

```php
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Provider;

// Get task ID after dispatching
$task_id = shepherd()->get_last_scheduled_task_id();

// Retrieve logs
$logger = Provider::get_container()->get( Logger::class );
$logs = $logger->retrieve_logs( $task_id );

foreach ( $logs as $log ) {
    echo "Type: {$log['type']}, Level: {$log['level']}, Date: {$log['date']}";
}
```

## Best Practices

### 1. Check File Existence for Attachments

```php
$attachments = [];
$file_path = WP_CONTENT_DIR . '/uploads/report.pdf';

if ( file_exists( $file_path ) ) {
    $attachments[] = $file_path;
}

$email = new Email( $to, $subject, $body, [], $attachments );
```

### 2. Use Proper Headers

```php
$headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . get_option( 'admin_email' ),
    'Reply-To: noreply@' . parse_url( home_url(), PHP_URL_HOST ),
];
```

### 3. Handle Large Attachments

Be mindful of attachment sizes - large files may cause memory issues or timeouts.

### 4. Use the `Email` task as a base class for your own tasks

You can extend the `Email` task to create your own tasks.

## Troubleshooting

### Email Not Sending

1. Check WordPress email configuration
2. Verify SMTP settings if using SMTP plugin
3. Check email logs in Action Scheduler
4. Review Shepherd task logs

### Attachments Not Working

1. Verify file paths are absolute
2. Check file permissions
3. Ensure files exist before dispatching

### Performance Issues

1. Avoid large attachments in high-volume scenarios
2. Consider using external email services for bulk emails
3. Monitor Action Scheduler queue length
