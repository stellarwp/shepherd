# Herding Task

Built-in maintenance task that cleans up orphaned data from Shepherd's database tables. Runs automatically every 6 hours to ensure database integrity.

## Purpose

Removes orphaned records when tasks are deleted from Action Scheduler but remain in Shepherd's tables (due to manual cleanup, database corruption, or external modifications).

## What it Cleans

Removes:

1. **Orphaned Task Records**: Tasks in `shepherd_tasks` without corresponding Action Scheduler entries
2. **Orphaned Log Records**: Log entries in `shepherd_task_logs` for deleted tasks

## Automatic Scheduling

Automatically scheduled during WordPress initialization:

- **Frequency**: Every 6 hours
- **Hook**: WordPress `init` action (priority 20)
- **Manual intervention**: Not required

## Process Flow

1. **Identify Orphaned Tasks**: Query for task IDs in Shepherd's table but not in Action Scheduler
2. **Skip if Clean**: Complete gracefully if no orphaned tasks found
3. **Sanitize IDs**: Clean and deduplicate task IDs for safe deletion
4. **Remove Logs**: Delete log entries for orphaned tasks
5. **Remove Tasks**: Delete orphaned task records
6. **Fire Hook**: Trigger completion hook for extensibility

## Hooks

### `shepherd_{prefix}_herding_processed`

Fires when the Herding task completes its cleanup process.

**Parameters:**

- `$task` (Herding): The Herding task instance that was processed

**Example:**

```php
add_action('shepherd_myapp_herding_processed', function($task) {
    // Custom cleanup logic after herding
    error_log('Herding task completed cleanup');
});
```

## Database Operations

The Herding task performs the following database operations:

```sql
-- Find orphaned tasks
SELECT DISTINCT(task_id) FROM shepherd_tasks
WHERE action_id NOT IN (
    SELECT action_id FROM wp_actionscheduler_actions
);

-- Remove orphaned logs
DELETE FROM shepherd_task_logs
WHERE task_id IN (orphaned_task_ids);

-- Remove orphaned tasks
DELETE FROM shepherd_tasks
WHERE task_id IN (orphaned_task_ids);
```

## Configuration

The Herding task uses the following configuration:

- **Task Prefix**: `shepherd_tidy_`
- **Retries**: Uses default retry settings (inherits from Task_Abstract)
- **Scheduling**: Automatic every 6 hours via `init` hook

## Best Practices

1. **Let it Run Automatically**: The task is designed to run automatically - manual intervention is rarely needed
2. **Monitor Hook**: Use the completion hook to track cleanup operations if needed
3. **Database Backups**: Ensure regular database backups as the task performs DELETE operations
4. **Custom Cleanup**: Extend cleanup logic via the completion hook rather than modifying the task

## Performance Considerations

- The task is designed to be efficient with proper indexing
- Database operations are batched for performance
- Task IDs are sanitized and deduplicated before deletion
- No-op when no orphaned data exists

## Error Handling

The Herding task includes robust error handling:

- **Safe Deletion**: Task IDs are sanitized before use in queries
- **Graceful Handling**: No errors if no orphaned data exists
- **Database Safety**: Uses prepared statements for all database operations

## Task Properties

- **Class**: `StellarWP\Shepherd\Tasks\Herding`
- **Prefix**: `shepherd_tidy_`
- **Arguments**: None (parameterless task)
- **Retries**: Default retry behavior
- **Unique**: Yes (prevents duplicate scheduling)

## Troubleshooting

### High Frequency of Orphaned Data

If you notice frequent orphaned data:

1. Check for external modifications to Action Scheduler tables
2. Verify database integrity
3. Review any custom code that might be manipulating Action Scheduler directly

### Task Not Running

If the Herding task isn't running automatically:

1. Verify WordPress `init` hook is firing
2. Check if Shepherd is properly initialized
3. Ensure no fatal errors during initialization

### Manual Cleanup

If you need to manually clean orphaned data:

```php
// Dispatch herding task immediately
shepherd()->dispatch(new \StellarWP\Shepherd\Tasks\Herding());

// Or run the action scheduler queue manually
as_run_queue();
```

## Related Documentation

- [Advanced Usage](../advanced-usage.md) - Task scheduling and management
- [Configuration](../configuration.md) - Shepherd configuration options
- [API Reference](../api-reference.md) - Complete API documentation
