# Admin UI Guide

Shepherd includes a powerful React-based admin interface for managing and monitoring background tasks. This guide covers how to enable, customize, and extend the admin UI.

## Quick Start

### Enabling the Admin UI

The admin UI is disabled by default. Enable it during configuration:

```php
use StellarWP\Shepherd\Config;

// Enable admin UI
Config::set_render_admin_ui( true );

// Optional: Set custom capability (default: 'manage_options')
Config::set_admin_page_capability( 'edit_posts' );
```

### Accessing the Interface

Once enabled, the admin interface is available at:
- **WordPress Admin** → **Tools** → **Shepherd ({your_hook_prefix})**

## Interface Overview

The admin UI provides a comprehensive table view of all background tasks with the following features:

### Task List

The main interface displays tasks in a sortable, filterable table with these columns:

- **Task ID**: Unique Shepherd task identifier
- **Action ID**: Action Scheduler action identifier
- **Task Type**: The PHP class name of the task
- **Arguments**: JSON representation of task arguments (scrollable, formatted)
- **Current Try**: Current retry attempt number
- **Status**: Task execution status (Pending, Running, Success, Failed, Cancelled)
- **Scheduled At**: When the task is/was scheduled to run

### Task Statuses

Tasks display one of five statuses:

- **Pending**: Task is queued but not yet started
- **Running**: Task is currently being processed
- **Success**: Task completed successfully
- **Failed**: Task failed after all retry attempts
- **Cancelled**: Task was cancelled before completion

### Filtering and Search

The interface supports advanced filtering with real-time AJAX updates:

- **Task Type Filter**: Filter by specific task classes (mapped to class_hash for efficiency)
- **Status Filter**: Show only tasks with specific statuses using multiple operators
- **Current Try Filter**: Filter by retry attempt number
- **Search**: Full-text search across task data
- **Dynamic Loading**: Filters trigger AJAX requests for real-time data updates
- **Multiple Operators**: Support for 'is' and 'isNot' filter operations

### Bulk Actions

The interface supports these bulk operations:

- **Edit**: Modify multiple tasks (placeholder - not yet implemented)
- **Delete**: Remove multiple tasks with confirmation dialog

### Individual Task Actions

Each task has contextual actions:

- **View**: See detailed task logs (available only for tasks with log entries)
- **Edit**: Modify task properties (placeholder - not yet implemented)
- **Delete**: Remove individual task with confirmation

## Configuration

### Customizing Page Titles

You can customize all admin page titles:

```php
use StellarWP\Shepherd\Config;

// Custom page title (browser tab, admin page list)
Config::set_admin_page_title_callback( function() {
    return __( 'Background Tasks', 'your-domain' );
} );

// Custom menu title (WordPress admin sidebar)
Config::set_admin_menu_title_callback( function() {
    return __( 'Tasks', 'your-domain' );
} );

// Custom in-page title (H1 on the admin page)
Config::set_admin_page_in_page_title_callback( function() {
    return __( 'Task Management Dashboard', 'your-domain' );
} );
```

### Default Titles

If no custom callbacks are set, Shepherd uses these defaults:

- **Page Title**: `Shepherd ({hook_prefix})`
- **Menu Title**: `Shepherd ({hook_prefix})`
- **In-Page Title**: `Shepherd Task Manager (via {hook_prefix})`

### Access Control

Control who can access the admin interface:

```php
// Default: Only administrators
Config::set_admin_page_capability( 'manage_options' );

// Allow editors
Config::set_admin_page_capability( 'edit_posts' );

// Custom capability
Config::set_admin_page_capability( 'manage_background_tasks' );
```

## Customization

### Filtering Localized Data

You can modify the data sent to the JavaScript interface:

```php
$prefix = Config::get_hook_prefix();

// Filter individual task data
add_filter( "shepherd_{$prefix}_admin_task_data", function( $task_data, $task ) {
    // Add custom fields to each task
    $task_data['custom_priority'] = get_task_priority( $task->id );
    $task_data['estimated_duration'] = calculate_duration( $task );
    
    return $task_data;
}, 10, 2 );

// Filter the entire data payload
add_filter( "shepherd_{$prefix}_admin_localized_data", function( $data ) {
    // Add global configuration
    $data['settings'] = [
        'auto_refresh' => true,
        'refresh_interval' => 30,
        'show_debug_info' => WP_DEBUG,
    ];
    
    // Add custom API endpoints
    $data['api'] = [
        'tasks_endpoint' => rest_url( 'my-plugin/v1/tasks' ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
    ];
    
    return $data;
} );
```

### Extending React Components

The React source code is available in the `app/` directory:

```
app/
├── index.tsx                    # Main entry point
├── components/
│   └── ShepherdTable.tsx       # Main table component
├── data.tsx                    # Data processing functions
├── types.ts                    # TypeScript definitions
└── style.scss                 # Component styles
```

To modify the interface:

1. **Install dependencies**: `npm ci`
2. **Start development**: `npm run dev`
3. **Make changes** to React components
4. **Build for production**: `npm run build`

### Adding Custom Actions

You can extend the table with custom actions by modifying `ShepherdTable.tsx`:

```typescript
// Add to the actions array in ShepherdTable.tsx
{
    id: 'reschedule',
    label: 'Reschedule',
    icon: <Icon icon={ calendar } />,
    callback: ( items ) => {
        // Custom reschedule logic
        console.log( 'Rescheduling items:', items );
    },
}
```

### Adding Custom Fields

Extend the table with additional columns by modifying `data.tsx`:

```typescript
// Add to the getFields() function
{
    id: 'priority',
    label: __( 'Priority', 'your-domain' ),
    enableHiding: true,
    enableSorting: true,
    getValue: ( { item } ) => {
        return item.custom_priority || 'normal';
    },
    elements: [
        { value: 'low', label: __( 'Low', 'your-domain' ) },
        { value: 'normal', label: __( 'Normal', 'your-domain' ) },
        { value: 'high', label: __( 'High', 'your-domain' ) },
    ],
}
```

## Development

### Development Environment

Set up the development environment:

```bash
# Switch to correct Node version
nvm use

# Install dependencies
npm ci

# Start development server with hot reload
npm run dev
```

### Building for Production

```bash
# Production build
npm run build

# The build outputs to the build/ directory:
# - main.js (JavaScript bundle)
# - style-main.css (Styles)
# - main.asset.php (WordPress metadata)
```

### Testing React Components

Run JavaScript tests:

```bash
# Run all tests
npm test

# Watch mode for development
npm run test:watch

# Test specific component
npm test ShepherdTable
```

### Code Quality

Maintain code quality with linting:

```bash
# Lint JavaScript/TypeScript
npm run lint:js

# Auto-fix linting issues
npm run format:js

# Lint CSS
npm run lint:css
```

## Technical Implementation

### Data Flow

1. **Initial Page Load** (`src/Admin/Provider.php`):
   - Fetches default tasks from database (first 10 items)
   - Performs JOIN queries with Action Scheduler actions table
   - Maps task statuses based on Action Scheduler state
   - Localizes initial data and configuration to JavaScript
   - Includes security nonce for AJAX requests

2. **AJAX API** (`wp_ajax_shepherd_get_tasks`):
   - Handles dynamic filtering, sorting, and searching
   - Processes filter parameters (task_type → class_hash mapping)
   - Supports multiple filter operators (is, isNot)
   - Returns paginated results with metadata
   - Maintains security with nonce verification

3. **JavaScript Frontend** (`app/`):
   - Receives initial data via `window.shepherdData`
   - Detects when new parameters differ from defaults
   - Makes AJAX requests for dynamic data updates
   - Transforms data for DataViews component
   - Renders interactive table interface
   - Handles user interactions and state management

### WordPress Integration

The admin UI integrates with WordPress through:

- **WordPress DataViews**: Official table component for consistency
- **WordPress i18n**: All strings are translatable
- **WordPress Scripts**: Build tooling and dependencies
- **WordPress Admin**: Standard admin page integration

### Performance Considerations

- **Pagination**: Tasks are paginated server-side (default: 10 per page)
- **Hybrid Loading**: Initial page load with data, subsequent requests via AJAX
- **Server-side Filtering**: All filtering and searching performed on the server
- **Database Optimization**: JOIN queries with Action Scheduler for enriched data
- **Efficient Queries**: Task type filters use hashed class names for faster lookups
- **Caching**: Unique values are cached for filter dropdowns

## Troubleshooting

### Admin UI Not Appearing

1. **Check if enabled**: Ensure `Config::set_render_admin_ui( true )` is called
2. **Check permissions**: Verify user has required capability
3. **Check hook prefix**: Ensure hook prefix is set before registration
4. **Check for JavaScript errors**: Open browser console for error messages

### Build Issues

1. **Node version**: Ensure correct Node.js version with `nvm use`
2. **Clean install**: Delete `node_modules` and run `npm ci`
3. **Build errors**: Check for TypeScript errors with `npm run lint:js`

### Data Not Loading

1. **Check PHP errors**: Review WordPress debug log
2. **Check localized data**: View page source for `window.shepherdData`
3. **Check AJAX endpoint**: Test AJAX requests in browser network tab
4. **Check nonce validity**: Ensure nonce is being passed correctly
5. **Check database**: Verify tasks exist in Shepherd tables
6. **Check Action Scheduler**: Ensure Action Scheduler is active
7. **Check JOIN queries**: Verify Action Scheduler tables exist

### AJAX Issues

1. **403 Errors**: Check user permissions and nonce validity
2. **500 Errors**: Review PHP error logs for database or code issues
3. **Slow responses**: Check database performance and query optimization
4. **Filter not working**: Verify filter format matches expected JSON structure

## Extending with Custom Interfaces

For advanced customizations, you can create entirely custom interfaces:

```php
// Disable built-in UI
Config::set_render_admin_ui( false );

// Register custom admin page
add_action( 'admin_menu', function() {
    add_management_page(
        'Custom Task Manager',
        'Tasks',
        'manage_options',
        'custom-task-manager',
        'render_custom_task_interface'
    );
} );

function render_custom_task_interface() {
    // Use Shepherd's data APIs to build custom interface
    $tasks = \StellarWP\Shepherd\Tables\Tasks::get_all();
    
    // Render your custom interface
    include 'custom-task-interface.php';
}
```

## AJAX API Reference

### Endpoint: `wp_ajax_shepherd_get_tasks`

The AJAX API provides real-time data fetching with advanced filtering capabilities.

#### Request Parameters

- **action**: `shepherd_get_tasks` (required)
- **nonce**: Security nonce from `shepherdData.nonce` (required)
- **perPage**: Number of items per page (default: 10)
- **page**: Page number (default: 1)
- **orderby**: Sort column (default: 'id')
- **order**: Sort direction - 'asc' or 'desc' (default: 'desc')
- **search**: Search term for full-text search
- **filters**: JSON array of filter objects

#### Filter Format

Filters are passed as a JSON array of objects:

```json
[
  {
    "field": "status",
    "operator": "is",
    "value": "pending"
  },
  {
    "field": "task_type",
    "operator": "isNot",
    "value": "EmailTask"
  }
]
```

#### Response Format

```json
{
  "success": true,
  "data": {
    "tasks": [
      {
        "id": 123,
        "action_id": 456,
        "data": {
          "task_class": "MyTask",
          "args": ["arg1", "arg2"]
        },
        "current_try": 1,
        "status": "pending",
        "scheduled_at": "2024-01-01T12:00:00+00:00",
        "logs": []
      }
    ],
    "totalItems": 150,
    "totalPages": 15
  }
}
```

#### Security

- Requires valid WordPress nonce
- Respects admin page capability settings
- Input sanitization and validation
- SQL injection protection via prepared statements

## Future Enhancements

The admin UI is designed to be extensible. Planned enhancements include:

- **Real-time updates**: WebSocket or polling for live status updates
- **Task management**: Edit task parameters, reschedule tasks
- **Performance metrics**: Task execution time, success rates
- **Advanced filtering**: Date ranges, custom field filters
- **Bulk operations**: Cancel running tasks, bulk reschedule
- **Export functionality**: CSV/JSON export of task data
- **Dashboard widgets**: Summary statistics for WordPress dashboard
- **Advanced search**: Field-specific search operators
- **Task dependencies**: Visual dependency graphs

For the latest development status and to contribute ideas, see the project's GitHub repository.