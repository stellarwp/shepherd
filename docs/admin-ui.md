# Admin UI Guide

Pigeon includes a powerful React-based admin interface for managing and monitoring background tasks. This guide covers how to enable, customize, and extend the admin UI.

## Quick Start

### Enabling the Admin UI

The admin UI is disabled by default. Enable it during configuration:

```php
use StellarWP\Pigeon\Config;

// Enable admin UI
Config::set_render_admin_ui( true );

// Optional: Set custom capability (default: 'manage_options')
Config::set_admin_page_capability( 'edit_posts' );
```

### Accessing the Interface

Once enabled, the admin interface is available at:
- **WordPress Admin** → **Tools** → **Pigeon ({your_hook_prefix})**

## Interface Overview

The admin UI provides a comprehensive table view of all background tasks with the following features:

### Task List

The main interface displays tasks in a sortable, filterable table with these columns:

- **Task ID**: Unique Pigeon task identifier
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

The interface supports advanced filtering:

- **Task Type Filter**: Filter by specific task classes
- **Status Filter**: Show only tasks with specific statuses
- **Current Try Filter**: Filter by retry attempt number
- **Search**: Full-text search across task data

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
use StellarWP\Pigeon\Config;

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

If no custom callbacks are set, Pigeon uses these defaults:

- **Page Title**: `Pigeon ({hook_prefix})`
- **Menu Title**: `Pigeon ({hook_prefix})`
- **In-Page Title**: `Pigeon Task Manager (via {hook_prefix})`

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
add_filter( "pigeon_{$prefix}_admin_task_data", function( $task_data, $task ) {
    // Add custom fields to each task
    $task_data['custom_priority'] = get_task_priority( $task->id );
    $task_data['estimated_duration'] = calculate_duration( $task );
    
    return $task_data;
}, 10, 2 );

// Filter the entire data payload
add_filter( "pigeon_{$prefix}_admin_localized_data", function( $data ) {
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

1. **PHP Backend** (`src/Admin/Provider.php`):
   - Fetches tasks from database
   - Retrieves Action Scheduler data
   - Maps task statuses
   - Localizes data to JavaScript

2. **JavaScript Frontend** (`app/`):
   - Receives data via `window.shepherdData`
   - Transforms data for DataViews component
   - Renders interactive table interface
   - Handles user interactions

### WordPress Integration

The admin UI integrates with WordPress through:

- **WordPress DataViews**: Official table component for consistency
- **WordPress i18n**: All strings are translatable
- **WordPress Scripts**: Build tooling and dependencies
- **WordPress Admin**: Standard admin page integration

### Performance Considerations

- **Pagination**: Tasks are paginated server-side (default: 10 per page)
- **Lazy Loading**: Only visible data is loaded initially
- **Filtering**: Server-side filtering for large datasets
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
3. **Check database**: Verify tasks exist in Pigeon tables
4. **Check Action Scheduler**: Ensure Action Scheduler is active

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
    // Use Pigeon's data APIs to build custom interface
    $tasks = \StellarWP\Pigeon\Tables\Tasks::get_all();
    
    // Render your custom interface
    include 'custom-task-interface.php';
}
```

## Future Enhancements

The admin UI is designed to be extensible. Planned enhancements include:

- **Real-time updates**: WebSocket or polling for live status updates
- **Task management**: Edit task parameters, reschedule tasks
- **Performance metrics**: Task execution time, success rates
- **Advanced filtering**: Date ranges, custom field filters
- **Bulk operations**: Cancel running tasks, bulk reschedule
- **Export functionality**: CSV/JSON export of task data
- **Dashboard widgets**: Summary statistics for WordPress dashboard

For the latest development status and to contribute ideas, see the project's GitHub repository.