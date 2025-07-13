# StellarWP Shepherd - AI Assistant Context

## Project Overview

Shepherd is a lightweight background processing library for WordPress built on top of Action Scheduler. It provides a clean, fluent API for defining and dispatching asynchronous tasks with built-in support for retries, debouncing, and logging.

## Key Features

- **Background Task Processing**: Offload time-consuming operations to background processes
- **Automatic Retries**: Configurable retry mechanism with exponential backoff
- **Task Debouncing**: Prevents tasks from running too frequently with customizable delays
- **Unique Task Enforcement**: Prevents duplicate tasks from being scheduled
- **Database Logging**: Comprehensive lifecycle tracking for all tasks
- **Priority System**: Assign priorities (0-255) to control task execution order
- **Group Management**: Organize tasks into logical groups
- **Built-in Tasks**: Includes pre-packaged tasks like Email for common operations

## Architecture

### Core Components

1. **Regulator** (`src/Regulator.php`): Central task management system
2. **Provider** (`src/Provider.php`): Service provider for dependency injection
3. **Task_Abstract** (`src/Abstracts/Task_Abstract.php`): Base class for all tasks
4. **Database Tables**: Custom tables for task data and logging

### Database Schema

- `shepherd_{prefix}_tasks`: Stores task data and retry information
- `shepherd_{prefix}_task_logs`: Tracks task lifecycle events

### Task Lifecycle States

- `created`: Task has been scheduled
- `started`: Task execution has begun
- `finished`: Task completed successfully
- `failed`: Task execution failed
- `rescheduled`: Task has been rescheduled
- `retrying`: Task is being retried after failure
- `cancelled`: Task has been cancelled

## Installation & Setup

### Installation

```bash
composer require stellarwp/shepherd
```

### Registration

Shepherd requires a DI container implementing `StellarWP\ContainerContract\ContainerInterface`. Register it on the `plugins_loaded` action at the LATEST:

```php
\StellarWP\Shepherd\Config::set_hook_prefix( 'my_app' ); // Needs to be set before the container is registered.
$container = get_my_apps_container(); // Your container instance
$container->singleton( \StellarWP\Shepherd\Provider::class );
$container->get( \StellarWP\Shepherd\Provider::class )->register();
```

## Creating Tasks

Tasks are recommended to extend `Task_Abstract`:

```php
class My_Task extends Task_Abstract {
    // Optional: Override constructor for type hinting
    public function __construct( string $message, int $code = 200 ) {
        parent::__construct( $message, $code ); // Should call parent constructor
    }

    public function process(): void {
        // Access arguments via $this->get_args()
        $message = $this->get_args()[0];
        $code = $this->get_args()[1];

        // Task logic here
        if ( ! $result ) {
            throw new ShepherdTaskException( 'Task failed' );
        }
    }

    public function get_task_prefix(): string {
        return 'my_task_'; // Max 15 characters
    }

    // Optional: Configure retries
    public function get_max_retries(): int {
        return 2; // Will retry 2 times (3 total attempts)
    }

    // Optional: Configure retry delay
    public function get_retry_delay(): int {
        return 30; // 30 seconds between retries
    }
}
```

## Usage Examples

```php
// Dispatch a task immediately
shepherd()->dispatch(new My_Task($arg1, $arg2));

// Dispatch with delay (in seconds)
shepherd()->dispatch(new My_Task($arg1, $arg2), 300); // 5 minutes

// Retrieve task logs
use StellarWP\Shepherd\Contracts\Logger;
use StellarWP\Shepherd\Config;

$logger = Config::get_container()->get( Logger::class );
$logs = $logger->retrieve_logs( $task_id );
```

## Built-in Tasks

### Email Task

Sends emails asynchronously with automatic retries (up to 5 attempts):

```php
use StellarWP\Shepherd\Tasks\Email;

$email_task = new Email(
    'recipient@example.com',
    'Subject',
    '<h1>HTML Body</h1>',
    ['Content-Type: text/html; charset=UTF-8'],
    ['/path/to/attachment.pdf']
);

shepherd()->dispatch($email_task);
```

## Logging System

### Default Logger Change (TBD)

Shepherd now uses `ActionScheduler_DB_Logger` as the default logger instead of `DB_Logger`. This change:

- **Reduces database overhead** by reusing Action Scheduler's existing `actionscheduler_logs` table
- **Maintains compatibility** with the existing Logger interface
- **Preserves all functionality** including log retrieval and lifecycle tracking

### Logger Options

```php
use StellarWP\Shepherd\Config;
use StellarWP\Shepherd\Loggers\ActionScheduler_DB_Logger;
use StellarWP\Shepherd\Loggers\DB_Logger;

// Default: Use Action Scheduler's logs table
Config::set_logger( new ActionScheduler_DB_Logger() );

// Alternative: Use Shepherd's dedicated logs table
Config::set_logger( new DB_Logger() );
```

### Log Storage Format

When using `ActionScheduler_DB_Logger`, logs are stored in a special format within the `message` column:

```
shepherd_{hook_prefix}||{task_id}||{type}||{level}||{json_entry}
```

This format allows Shepherd to store its metadata while maintaining compatibility with Action Scheduler's table structure.

## Development Commands

### Testing

```bash
# You need to have slic installed and configured to use shepherd.

# Then you can run each suite like:
slic run wpunit
slic run integration
```

### Code Quality

```bash
# Run static analysis
composer test:analysis

# Check PHP compatibility
composer compatibility

# Run coding standards check
vendor/bin/phpcs
```

### Common Tasks

```bash
# Install PHP dependencies, ignoring uopz extension which is met inside of the slic container.
composer install --ignore-platform-req=ext-uopz

# Switch to the correct Node version (required before running npm commands)
nvm use

# Install JavaScript dependencies
npm ci

# Development build with hot reloading
npm run dev

# Production build
npm run build

# Linting
npm run lint:js
npm run lint:css
```

## Important Files and Locations

- **Main entry point**: `shepherd.php`
- **Core logic**: `src/Regulator.php`
- **Task base class**: `src/Abstracts/Task_Abstract.php`
- **Database schemas**: `src/Tables/`
- **Built-in tasks**: `src/Tasks/`
- **Admin UI PHP**: `src/Admin/Provider.php`
- **Admin UI React**: `app/`
- **Exception classes**: `src/Exceptions/`
- **Logger implementations**: `src/Loggers/`
- **Utility traits**: `src/Traits/`
- **Tests**: `tests/` (PHP) and `tests/js/` (JavaScript)
- **Documentation**: `docs/`

## Testing Approach

- Uses Codeception via [slic](https://github.com/stellarwp/slic) for testing
- Test configuration in `codeception.dist.yml` and `codeception.slic.yml` with environmental variables defined in `.env.testing.slic`
- Integration tests for full workflow testing
- Snapshot testing for complex data structures
- JavaScript tests using Jest and React Testing Library
  - Component tests in `tests/js/app/components/*.spec.tsx`
  - Data layer tests in `tests/js/app/data.spec.tsx`
  - Mock WordPress dependencies for isolated testing

## Coding Standards

- Follows WordPress coding standards and more Specifically StellarWP's coding standards.
- Uses PHPStan for static analysis (level defined in `phpstan.neon.dist`)
- PHP 7.4+ compatibility required
- PSR-4 autoloading under `StellarWP\Shepherd` namespace

## Dependencies

- **stellarwp/db**: Database abstraction layer
- **stellarwp/schema**: Database schema management
- **woocommerce/action-scheduler**: Task queue backend
- **psr/log**: PSR-3 logger interface
- **stellarwp/container-contract**: A DI container that implements [StellarWP's container contract](https://github.com/stellarwp/container-contract)

## Common Development Patterns

### Adding a New Task

1. Create a new class extending `Task_Abstract` in `src/Tasks/`
2. Implement required methods (`process()`, `get_task_prefix()`)
3. Optionally override retry configuration methods
4. If implemented within Pigeon, add tests in `tests/unit/Tasks/`

### Working with the Admin UI

1. **Adding new columns**: Update `getFields()` in `app/data.tsx`
2. **Custom filters**: Add filter definitions with operators in field configuration
3. **Custom actions**: Define actions in `ShepherdTable` component
4. **API modifications**: Update `ajax_get_tasks()` in `src/Admin/Provider.php`
5. **Testing**: Add tests for both React components and PHP endpoints

### Modifying Database Schema

1. Update table's column definitions in `src/Tables/`
2. Update the table's schema version.
3. Update any affected repository classes.

### Adding New Features

1. Follow existing patterns in the codebase
2. Add appropriate logging using the logger trait
3. Include comprehensive tests
4. Update documentation as needed

## Troubleshooting

### Common Issues

1. **uopz extension missing**: Use `--ignore-platform-req=ext-uopz` with composer

### Custom Logger Implementation

You can implement a custom logger by implementing the `Logger` interface:

```php
use StellarWP\Shepherd\Contracts\Logger;

class My_Custom_Logger implements Logger {
    // Implement required methods
}

// Set before Provider::register()
\StellarWP\Shepherd\Config::set_logger( new My_Custom_Logger() );
```

## Task Behavior Details

### Unique Tasks

- Tasks are unique based on class name and arguments
- Dispatching a duplicate task will be ignored (no-op)

### Retry Logic

- Tasks fail when they throw an `Exception` in their `process()` method.
- Retry count is the number of additional attempts (not total attempts)
- Each retry can have a configurable delay
- Failed tasks are logged

## Additional Notes

- This is a WordPress plugin/library, not a standalone application
- Requires WordPress environment for full functionality
- Action Scheduler must be available (included as dependency)
- All database operations use StellarWP's [DB](https://github.com/stellarwp/db) library
- Admin UI requires WordPress 6.1+ for DataViews component
- React components use WordPress's @wordpress/dataviews package

## React Admin UI Architecture

### Overview

Pigeon includes a React-based admin interface for managing background tasks. The UI is built with TypeScript and modern WordPress development practices.

### Architecture Components

#### Frontend Structure (`app/`)

- **`index.tsx`**: Entry point that renders the ShepherdTable component
- **`components/ShepherdTable.tsx`**: Main table component using WordPress DataViews
- **`data.tsx`**: Data processing functions and field definitions
  - `getFields()`: Returns table column definitions with custom rendering
  - `getTasks()`: Transforms server data into Task objects
  - `getPaginationInfo()`: Extracts pagination metadata
- **`types.ts`**: TypeScript type definitions for:
  - `Task`: Main task object with status, logs, and metadata
  - `Log`: Task log entries with level and type
  - `TaskData`: Serialized task class and arguments
  - Various status and type enums

#### Backend Integration (`src/Admin/Provider.php`)

- Registers admin menu under Tools
- Enqueues React build assets
- Provides localized data via `get_localized_data()`:
  - Fetches tasks from database with pagination
  - Retrieves Action Scheduler action details
  - Maps task status based on action state and logs
  - Includes all task logs for each task
- **AJAX API endpoint** (`wp_ajax_shepherd_get_tasks`):
  - Handles dynamic filtering, sorting, and searching
  - Supports complex queries with joins to Action Scheduler tables
  - Returns paginated results with real-time data
  - Maps task_type filters to class_hash for efficient queries
  - Supports multiple filter operators (is, isNot)

#### Key Features

1. **WordPress DataViews Integration**: Uses official WordPress components for consistency
2. **Type Safety**: Full TypeScript support with strict typing
3. **Internationalization**: All strings use WordPress i18n functions
4. **Status Mapping**: Intelligent status detection based on Action Scheduler state
5. **Human-Readable Dates**: Recent dates show relative time, older dates show absolute
6. **Advanced Filtering**: Filter by task type, status, and current try count
7. **Bulk Actions**: Support for bulk edit and delete operations
8. **Unique Value Caching**: Optimized filtering with `getUniqueValuesOfData()` function
9. **Responsive Design**: Adapts to different screen sizes with WordPress admin styling
10. **AJAX-Powered**: Real-time data fetching without page reloads
11. **Optimized Performance**: Initial data loaded with page, subsequent requests via AJAX

### Development Workflow

```bash
# Install dependencies
npm ci

# Development with hot reload
npm run dev

# Production build
npm run build

# Run tests
npm test

# Lint JavaScript/TypeScript
npm run lint:js
```

### Testing

JavaScript tests are located in `tests/js/` and use:

- Jest as the test runner
- React Testing Library for component tests
- WordPress Scripts test configuration
- Mock implementations for WordPress dependencies

Test files:

- `tests/js/app/components/ShepherdTable.spec.tsx`: Table component tests
- `tests/js/app/data.spec.tsx`: Data processing function tests

### Extending the UI

The admin UI can be extended by:

1. Filtering the PHP data before localization
2. Modifying React components directly
3. Adding custom actions to the DataViews table
4. Implementing custom REST API endpoints

## Advanced Features

### Exception System

Pigeon includes specialized exception classes for different failure scenarios:

- **`PigeonTaskException`**: Base exception for general task failures
- **`PigeonTaskAlreadyExistsException`**: Thrown when attempting to schedule duplicate tasks
- **`PigeonTaskFailWithoutRetryException`**: For tasks that should fail immediately without retry

### Database Utilities

#### Safe Dynamic Prefix

The `Safe_Dynamic_Prefix` utility automatically manages table name length limits:

```php
use StellarWP\Pigeon\Tables\Utility\Safe_Dynamic_Prefix;

// Automatically calculates safe prefix based on longest table name
$safe_prefix = Safe_Dynamic_Prefix::get( 'very_long_application_prefix' );
```

#### Advanced Query Methods

The `Custom_Table_Query_Methods` trait provides powerful database operations:

- Batch processing with generators for memory efficiency
- Complex pagination with JOIN support
- Advanced WHERE clause building
- Bulk operations: `insert_many()`, `update_many()`, `delete_many()`
- Upsert operations for conflict resolution
- Search across multiple columns

### Logger Implementations

Pigeon supports multiple logging strategies:

1. **`ActionScheduler_DB_Logger`** (default): Uses Action Scheduler's log table
2. **`DB_Logger`**: Uses dedicated Pigeon log tables
3. **`Null_Logger`**: Disables logging entirely for testing

### Action Scheduler Integration

The `Action_Scheduler_Methods` class provides enhanced Action Scheduler functionality:

- Bulk action operations
- Enhanced action retrieval with filtering
- Pending action management
- Wrapper methods for common operations

## Testing Framework

### Test Structure

Pigeon uses a comprehensive testing approach:

- **46 PHP test files** covering all components
- **Jest-based JavaScript tests** for React components
- **Snapshot testing** for regression prevention
- **Mock task classes** for testing scenarios

### Test Utilities

Key testing features include:

- **Custom snapshot assertions** for log verification
- **Clock mocking** for time-sensitive tests
- **WordPress integration** via Codeception
- **Container management** for dependency injection testing
- **Test helper functions** for common operations

### Running Tests

```bash
# PHP Tests with slic
slic run wpunit         # Unit tests
slic run integration    # Integration tests

# JavaScript Tests
npm test               # Run all JS tests
npm run test:watch     # Watch mode

# Code Quality
composer test:analysis  # PHPStan analysis
composer compatibility  # PHP version compatibility
vendor/bin/phpcs       # Coding standards
```

## Contributing Guidelines

**IMPORTANT**: Before making any commits or opening PRs, always check:

- `.github/CONTRIBUTING.md` - Complete commit and PR guidelines
- Pre-commit checklist:
  - Run `composer test:analysis`
  - Run `composer compatibility`
  - Run `vendor/bin/phpcs`
  - Run `slic run wpunit && slic run integration`
  - Run `npm test` for JavaScript tests
  - Update documentation if needed
  - Follow conventional commit format

## Documentation

For more detailed information, refer to the documentation files:

- `docs/getting-started.md` - Installation and basic usage guide
- `docs/advanced-usage.md` - Advanced features like retries, debouncing, logging, and database utilities
- `docs/admin-ui.md` - Complete admin interface guide
- `docs/testing.md` - Comprehensive testing documentation
- `docs/tasks.md` - Information about built-in tasks
- `docs/tasks/email.md` - Detailed documentation for the Email task
- `docs/tasks/http-request.md` - HTTP Request task documentation
- `docs/api-reference.md` - Complete API documentation
- `docs/configuration.md` - Configuration guide
