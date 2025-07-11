# Herding Task

The Herding task is a built-in maintenance task that cleans up orphaned data from Shepherd's database tables. This task automatically runs every 6 hours to ensure database integrity and prevent accumulation of stale data.

## Purpose

Over time, tasks may be removed from Action Scheduler (due to various reasons like manual cleanup, database corruption, or external modifications) while their corresponding data remains in Shepherd's tables. The Herding task identifies and removes these orphaned records to maintain a clean database state.

## What it Cleans

The Herding task removes:

1. **Orphaned Task Records**: Tasks in the `shepherd_tasks` table that no longer have corresponding entries in Action Scheduler
2. **Orphaned Log Records**: Log entries in the `shepherd_task_logs` table for tasks that no longer exist

## Automatic Scheduling

The Herding task is automatically scheduled during WordPress initialization:

- **Frequency**: Every 6 hours
- **Hook**: Attached to WordPress `init` action with priority 20
- **Automatic**: No manual intervention required

## Process Flow

1. **Identify Orphaned Tasks**: Query for task IDs that exist in Shepherd's tasks table but not in Action Scheduler
2. **Skip if Clean**: If no orphaned tasks found, complete gracefully
3. **Sanitize IDs**: Clean and deduplicate task IDs for safe deletion
4. **Remove Logs**: Delete all log entries for orphaned tasks
5. **Remove Tasks**: Delete the orphaned task records
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
