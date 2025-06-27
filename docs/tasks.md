# Built-in Tasks

Pigeon comes with a set of pre-packaged tasks to handle common background operations.

## Email Task

The `Email` task provides a simple and reliable way to send emails asynchronously. It leverages WordPress's `wp_mail()` function and includes automatic retries if the email fails to send.

### Usage

To use the `Email` task, you instantiate it with the necessary arguments for `wp_mail()` and then dispatch it using the `pigeon()` helper function.

The constructor signature is as follows:
`new Email( string $to_email, string $subject, string $body, array $headers = [], array $attachments = [] )`

### Example

Here is a basic example of how to send an email with an attachment:

```php
use StellarWP\Pigeon\Tasks\Email;
use function StellarWP\Pigeon\pigeon;

// Define the email details
$to          = 'recipient@example.com';
$subject     = 'Your Weekly Report is Ready';
$body        = '<h1>Weekly Report</h1><p>Please find your weekly report attached.</p>';
$headers     = [ 'Content-Type: text/html; charset=UTF-8', 'Reply-To: no-reply@example.com' ];
$attachments = [ WP_CONTENT_DIR . '/uploads/reports/report.pdf' ];

// Create and dispatch the email task
$email_task = new Email( $to, $subject, $body, $headers, $attachments );

pigeon()->dispatch( $email_task );
```

### Retries

The `Email` task is configured to retry up to **5 times** if it fails. It uses a 30-second delay between each retry attempt. This ensures that transient email sending issues do not result in a permanently failed task.

- Emails
- PDFs
- WP Remote Requests aka delivering webhooks.
- Following a cron schedule.
