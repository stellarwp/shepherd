# Testing Guide

Shepherd includes a comprehensive testing framework with 46+ PHP tests and JavaScript component tests. This guide covers how to run tests, write new tests, and understand the testing patterns.

## Test Structure Overview

Shepherd uses multiple testing approaches for complete coverage:

```
tests/
├── integration/          # WordPress integration tests (9 files)
├── wpunit/              # WordPress unit tests (35+ files)  
├── js/                  # JavaScript/React tests (3 files)
├── _support/            # Test utilities and helpers
│   ├── Helper/          # Test helper classes
│   └── Traits/          # Reusable test traits
└── _bootstrap.php       # Global test setup
```

## PHP Testing

### Test Frameworks

Shepherd uses [Codeception](https://codeception.com/) for PHP testing with two main suites:

- **WPUnit**: WordPress unit tests with database access
- **Integration**: Full WordPress integration tests

### Running PHP Tests

#### Using slic (Recommended)

[slic](https://github.com/stellarwp/slic) provides a Docker-based testing environment:

```bash
# Run all unit tests
slic run wpunit

# Run all integration tests  
slic run integration

# Run specific test file
slic run wpunit tests/wpunit/Config_Test.php

# Run specific test method
slic run wpunit tests/wpunit/Config_Test.php::it_should_set_and_get_hook_prefix

# Run with coverage
slic run wpunit --coverage
```

#### Using Local Environment

If you have a local WordPress testing environment:

```bash
# Install dependencies (ignore uopz if not in Docker)
composer install --ignore-platform-req=ext-uopz

# Run Codeception tests
vendor/bin/codecept run wpunit
vendor/bin/codecept run integration
```

### Test Categories

#### Core Component Tests

**Configuration** (`tests/wpunit/Config_Test.php`):
- Hook prefix validation
- Logger configuration
- Admin UI settings
- Container management

**Task Processing** (`tests/wpunit/Regulator_Test.php`):
- Task dispatch and execution
- Retry mechanisms
- Error handling
- Hook integration

**Database Operations** (`tests/wpunit/Tables/`):
- Task storage and retrieval
- Log management
- Table schema validation
- Query performance

#### Built-in Task Tests

**Email Task** (`tests/wpunit/Tasks/Email_Test.php`, `tests/integration/Tasks/Email_Test.php`):
- Email sending functionality
- Retry behavior (up to 5 attempts)
- Attachment handling
- Error scenarios

**HTTP Request Task** (`tests/wpunit/Tasks/HTTP_Request_Test.php`):
- HTTP request execution
- Authentication handling
- Response processing
- Timeout scenarios

#### Logger Tests

**ActionScheduler_DB_Logger** (`tests/wpunit/Loggers/ActionScheduler_DB_Logger_Test.php`):
- Log entry creation
- Task-specific log retrieval
- Special format handling

**DB_Logger** (`tests/wpunit/Loggers/DB_Logger_Test.php`):
- Dedicated table logging
- Log level filtering
- Performance characteristics

**Null_Logger** (`tests/wpunit/Loggers/Null_Logger_Test.php`):
- Disabling logs for testing
- Performance optimization

### Writing PHP Tests

#### Basic Test Structure

```php
<?php

namespace StellarWP\Shepherd;

use lucatume\WPBrowser\TestCase\WPTestCase;

class My_Feature_Test extends WPTestCase {
    /**
     * @test
     */
    public function it_should_do_something(): void {
        // Arrange
        $task = new My_Task( 'test-arg' );
        
        // Act  
        $result = shepherd()->dispatch( $task );
        
        // Assert
        $this->assertNotNull( $result );
    }
}
```

#### Using Test Helpers

Shepherd provides extensive test utilities:

```php
use StellarWP\Shepherd\Tests\Traits\With_Uopz;
use StellarWP\Shepherd\Tests\Traits\With_Clock_Mock;

class Advanced_Test extends WPTestCase {
    use With_Uopz;
    use With_Clock_Mock;
    
    /**
     * @test
     */
    public function it_should_handle_time_sensitive_operations(): void {
        // Mock specific function returns
        $this->set_fn_return( 'time', 1640995200 );
        
        // Mock WordPress time functions
        $this->mock_clock( '2022-01-01 00:00:00' );
        
        // Test time-dependent functionality
        $task = new Scheduled_Task();
        $result = shepherd()->dispatch( $task, 3600 ); // 1 hour delay
        
        $this->assertNotNull( $result );
    }
}
```

#### Mock Tasks for Testing

Use provided mock tasks for consistent testing:

```php
use StellarWP\Shepherd\Tests\Helper\Tasks\Always_Fail_Task;
use StellarWP\Shepherd\Tests\Helper\Tasks\Internal_Counting_Task;
use StellarWP\Shepherd\Tests\Helper\Tasks\Retryable_Do_Action_Task;

class Task_Behavior_Test extends WPTestCase {
    /**
     * @test
     */
    public function it_should_retry_failed_tasks(): void {
        $task = new Always_Fail_Task();
        
        shepherd()->dispatch( $task );
        
        // Process the task through Action Scheduler
        $this->process_scheduled_actions();
        
        // Verify retry behavior
        $logs = $this->get_task_logs( $task );
        $this->assertGreaterThan( 1, count( $logs ) );
    }
}
```

#### Snapshot Testing

Use snapshots for complex output verification:

```php
use StellarWP\Shepherd\Tests\Traits\With_Log_Snapshot;

class Snapshot_Test extends WPTestCase {
    use With_Log_Snapshot;
    
    /**
     * @test
     */
    public function it_should_produce_expected_logs(): void {
        $task = new Email_Task( 'test@example.com', 'Subject', 'Body' );
        
        shepherd()->dispatch( $task );
        $this->process_scheduled_actions();
        
        // Compare actual logs with stored snapshot
        $this->assertLogSnapshot( $task );
    }
}
```

## JavaScript Testing

### Framework Setup

JavaScript tests use Jest with WordPress-specific configuration:

- **Jest**: Test runner and assertion library
- **React Testing Library**: Component testing utilities
- **WordPress Mocks**: Mock WordPress functions and components

### Running JavaScript Tests

```bash
# Run all JavaScript tests
npm test

# Watch mode for development
npm run test:watch

# Run specific test file
npm test ShepherdTable

# Generate coverage report
npm test -- --coverage
```

### Test Structure

JavaScript tests are located in `tests/js/` with this structure:

```
tests/js/
├── app/
│   ├── components/
│   │   └── ShepherdTable.test.tsx
│   └── data.test.tsx
└── setup.js
```

### Writing React Component Tests

#### Testing the ShepherdTable Component

```typescript
import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import { ShepherdTable } from '../../../../app/components/ShepherdTable';

// Mock WordPress dependencies
jest.mock( '@wordpress/dataviews/wp', () => ({
    DataViews: jest.fn( ({ data, fields }) => (
        <div data-testid="dataviews-mock">
            {data.length} items, {fields.length} fields
        </div>
    )),
}));

describe( 'ShepherdTable', () => {
    it( 'should render the DataViews component', () => {
        render( <ShepherdTable /> );
        
        expect( screen.getByTestId( 'dataviews-mock' ) ).toBeInTheDocument();
    });
});
```

#### Testing Data Functions

```typescript
import { getFields, getTasks } from '../../../app/data';

// Mock WordPress dependencies
jest.mock( '@wordpress/i18n', () => ({
    __: jest.fn( ( text ) => text ),
}));

describe( 'data functions', () => {
    it( 'should return field definitions', () => {
        const fields = getFields();
        
        expect( fields ).toHaveLength( 7 );
        expect( fields[0].id ).toBe( 'id' );
    });
    
    it( 'should transform task data', () => {
        global.window.shepherdData = {
            tasks: [{ id: 1, status: { slug: 'pending' } }]
        };
        
        const tasks = getTasks( 1, 10 );
        
        expect( tasks ).toHaveLength( 1 );
        expect( tasks[0].id ).toBe( 1 );
    });
});
```

### WordPress Mocking Patterns

Mock WordPress functions consistently:

```typescript
// Mock i18n functions
jest.mock( '@wordpress/i18n', () => ({
    __: jest.fn( ( text ) => text ),
    sprintf: jest.fn( ( format, ...args ) => 
        format.replace( /%s/g, () => args.shift() )
    ),
}));

// Mock WordPress components
jest.mock( '@wordpress/components', () => ({
    Button: jest.fn( ({ children, onClick }) => 
        <button onClick={onClick}>{children}</button>
    ),
    Icon: jest.fn( ({ icon }) => <span>{icon}</span> ),
}));

// Mock date functions
jest.mock( '@wordpress/date', () => ({
    getDate: jest.fn( () => new Date( '2024-01-01' ) ),
    humanTimeDiff: jest.fn( () => '2 hours ago' ),
    dateI18n: jest.fn( () => '2024-01-01' ),
}));
```

## Test Configuration

### Jest Configuration

The Jest configuration (`jest.config.js`) includes:

```javascript
module.exports = {
    testMatch: [ '**/tests/js/**/*.test.[jt]s?(x)' ],
    setupFilesAfterEnv: [ '<rootDir>/tests/js/setup.js' ],
    moduleNameMapper: {
        '^@/(.*)$': '<rootDir>/app/$1',
    },
    transform: {
        '^.+\\.(ts|tsx)$': [ '@wordpress/scripts/config/babel-transform' ],
    },
};
```

### Test Setup File

The setup file (`tests/js/setup.js`) configures the test environment:

```javascript
import '@testing-library/jest-dom';

// Mock console methods to reduce test noise
global.console = {
    ...console,
    log: jest.fn(),
    debug: jest.fn(),
    warn: jest.fn(),
    error: jest.fn(),
};
```

## Code Quality

### PHP Code Quality

Run static analysis and coding standards:

```bash
# PHPStan static analysis
composer test:analysis

# PHP compatibility check
composer compatibility

# WordPress coding standards
vendor/bin/phpcs

# Auto-fix coding standards
vendor/bin/phpcbf
```

### JavaScript Code Quality

Lint and format JavaScript code:

```bash
# Lint JavaScript/TypeScript
npm run lint:js

# Auto-fix JavaScript issues
npm run format:js

# Lint CSS
npm run lint:css
```

## Test Utilities Reference

### Available Test Traits

1. **With_Uopz**: Function mocking and overrides
2. **With_Clock_Mock**: Time and date mocking
3. **With_Log_Snapshot**: Log output snapshot testing
4. **With_AS_Assertions**: Action Scheduler assertions

### Mock Task Classes

1. **Always_Fail_Task**: Always throws exceptions
2. **Do_Action_Task**: Fires WordPress actions
3. **Do_Prefixed_Action_Task**: Fires prefixed actions
4. **Internal_Counting_Task**: Tracks execution count
5. **Retryable_Do_Action_Task**: Configurable retry behavior

### Helper Functions

Key functions in `test-functions.php`:

```php
// Container management
tests_shepherd_get_container()
tests_shepherd_reset_config()

// Task processing
process_scheduled_actions()
get_task_logs( $task )

// Database utilities
create_test_task( $args )
clear_test_data()
```

## Best Practices

### PHP Testing Best Practices

1. **Use descriptive test names**: Start with `it_should_` for clarity
2. **Follow AAA pattern**: Arrange, Act, Assert
3. **Mock external dependencies**: Use traits for consistent mocking
4. **Test edge cases**: Include failure scenarios and boundary conditions
5. **Use snapshots for complex output**: Especially for log verification

### JavaScript Testing Best Practices

1. **Mock WordPress dependencies**: Consistent mocking patterns
2. **Test user interactions**: Use React Testing Library for user-centric tests
3. **Test data transformations**: Verify data processing functions
4. **Keep tests focused**: One concept per test
5. **Use semantic queries**: Prefer `getByRole`, `getByText` over `getByTestId`

### General Testing Guidelines

1. **Write tests first**: TDD approach when adding new features
2. **Maintain test independence**: Each test should be isolated
3. **Use meaningful assertions**: Clear expectations and error messages
4. **Keep tests fast**: Optimize for quick feedback loops
5. **Document complex scenarios**: Add comments for non-obvious test logic

## Continuous Integration

### Running Tests in CI

Example GitHub Actions workflow:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  php-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.1
      
      - name: Install dependencies
        run: composer install
      
      - name: Run PHP tests
        run: |
          composer test:analysis
          vendor/bin/phpcs
          vendor/bin/codecept run wpunit
  
  js-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install dependencies
        run: npm ci
      
      - name: Run JavaScript tests
        run: |
          npm run lint:js
          npm test
```

## Troubleshooting Tests

### Common PHP Test Issues

1. **Container not set**: Ensure `tests_shepherd_reset_config()` is called
2. **Action Scheduler not processing**: Call `process_scheduled_actions()`
3. **Database state**: Use proper setup/teardown methods
4. **Time-dependent tests**: Use clock mocking traits

### Common JavaScript Test Issues

1. **WordPress mocks missing**: Ensure all WordPress dependencies are mocked
2. **Global state**: Reset `window.shepherdData` between tests
3. **Async operations**: Use proper async/await patterns
4. **Component props**: Mock all required props and callbacks

### Performance Issues

1. **Slow PHP tests**: Check database queries and Action Scheduler processing
2. **Slow JS tests**: Reduce DOM queries and mock heavy operations
3. **Memory usage**: Clear test data between tests
4. **CI timeouts**: Optimize test parallelization

For additional help, see the project's GitHub issues or contribute test improvements through pull requests.