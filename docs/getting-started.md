# Getting Started with Pigeon

This guide will walk you through the basics of installing and using Pigeon to run your first background task.

## Installation

Pigeon is a Composer package. To install it, you'll need to have Composer in your project. If you don't have it already, you can follow the instructions on the [Composer website](https://getcomposer.org/).

Once you have Composer set up, you can add Pigeon to your project by running the following command in your project's root directory:

```bash
composer require stellarwp/pigeon
```

After installing Pigeon, you need to make sure you're including the Composer autoloader in your plugin or theme. This is typically done by adding the following line to your main plugin file or `functions.php`:

```php
require_once __DIR__ . '/vendor/autoload.php';
```

Finally, you need to register Pigeon. This is usually done in your plugin's main file, on the `plugins_loaded` action.

```php
// Pigeon expects to be registered by a DI StellarWP\ContainerContract\ContainerInterface container.
function dummy_function_to_get_my_apps_container(): \StellarWP\ContainerContract\ContainerInterface {
    return $container;
}

add_action( 'plugins_loaded', function() {
    \StellarWP\Pigeon\Config::set_hook_prefix( 'my_app' );
    $container = dummy_function_to_get_my_apps_container();
    $container->singleton( \StellarWP\Pigeon\Provider::class );
    $container->get( \StellarWP\Pigeon\Provider::class )->register();
} );
```

## Creating Your First Task

Tasks in Pigeon are classes that extend the `StellarWP\Pigeon\Abstracts\Task_Abstract` class. At a minimum, you need to implement the `process()` method.

Let's create a simple task that logs a message to the debug log.

```php
<?php

namespace My\App\Tasks;

use StellarWP\Pigeon\Abstracts\Task_Abstract;

class Log_Message_Task extends Task_Abstract {
    /**
     * Overwriting the Abstract's constructor is completely optional.
     * It helps with type hinting and IDE autocompletion. So that others using your task can easily see the arguments it accepts.
     *
     * Do always remember to call the parent constructor, since important operations are performed there.
     */
    public function __construct( string $message, int $code = 200 ) {
        parent::__construct( $message, $code );
    }

    public function process(): void {
        error_log( 'Pigeon Task: ' . $this->get_args()[0] . ' with code ' . $this->get_args()[1] );
    }

    public function get_task_prefix(): string {
         return 'log_msg_'; // Can't be longer than 15 characters.
    }
}
```

A few things to note:

- We're passing the arguments to the `parent::__construct()` method. This is important for Pigeon to be able to store and retrieve the arguments for your task.
- The `process()` method contains the logic that will be executed in the background.

## Dispatching Your Task

Once you've created your task, you can dispatch it using the `pigeon()` helper function.

```php
use My\App\Tasks\Log_Message_Task;
use function StellarWP\Pigeon\pigeon;

$my_task = new Log_Message_Task( 'Hello, World!' );
pigeon()->dispatch( $my_task );
```

This will schedule your task to be executed as soon as possible. You can also add a delay to the execution of your task:

```php
// Execute the task after a 5 minute delay.
pigeon()->dispatch( $my_task, 5 * MINUTE_IN_SECONDS );
```

And that's it! You've created and dispatched your first background task with Pigeon. Check your `debug.log` file, and you should see the message "Pigeon Task: Hello, World!".

For more advanced topics, check out the [Advanced Usage guide](./advanced-usage.md).
