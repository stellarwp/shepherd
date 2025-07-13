# Shepherd

Shepherd is a lightweight and powerful background processing library for WordPress, built on top of Action Scheduler. It provides a simple, fluent API for defining and dispatching asynchronous tasks, with built-in support for retries, debouncing, and logging.

## Features

- **Simple, Fluent API**: A straightforward way to define and dispatch background tasks.
- **Action Scheduler Integration**: Leverages the reliability of Action Scheduler for task processing.
- **Automatic Retries**: Configurable automatic retries for failed tasks.
- **Debouncing**: Prevent tasks from running too frequently.
- **Logging**: Built-in database logging for task lifecycle events.
- **Included Tasks**: Comes with a ready-to-use `Email` task.

## Getting Started

For a guide on how to install Shepherd and get started with creating and dispatching your first task, please see our [Getting Started guide](./docs/getting-started.md).

### Development Setup

If you're contributing to Shepherd or building the admin UI:

1. **Use the correct Node version**:
   ```bash
   nvm use
   ```
   This will switch to Node as specified in `.nvmrc`

2. **Install dependencies**:
   ```bash
   npm ci
   ```

3. **Run development build**:
   ```bash
   npm run dev
   ```

## Advanced Usage

For more detailed information on advanced features like task retries, debouncing, unique tasks, and logging, please refer to our [Advanced Usage guide](./docs/advanced-usage.md).

## Built-in Tasks

Shepherd comes with a set of pre-packaged tasks to handle common background operations. For more information, please see our [Tasks guide](./docs/tasks.md).

## Contributing

We welcome contributions! Please see our contributing guidelines for more information. (TODO: Add a CONTRIBUTING.md file)
