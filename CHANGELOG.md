# Change Log

All notable changes to this project will be documented in this file. This project adhere to the [Semantic Versioning](http://semver.org/) standard.

## [0.0.5] 2025-08-20

* Fix - Ensure the AS logger table exists before using it. Introduce a filter `shepherd_<hook_prefix>_should_log` to disable logging.

[0.0.5]: https://github.com/stellarwp/shepherd/releases/tag/0.0.5

## [0.0.4] 2025-08-04

* Fix - Deal with issues of auto-loading the functions.php file while using Strauss.
* Fix - Issue with php 7.4.

[0.0.4]: https://github.com/stellarwp/shepherd/releases/tag/0.0.4

## [0.0.3] 2025-07-31

* Fix - Removed an empty line after the columns and before the primary key of the Table creation SQL.

[0.0.3]: https://github.com/stellarwp/shepherd/releases/tag/0.0.3

## [0.0.2] 2025-07-14

* Fix - Fix the path to the Action Scheduler file to take into consideration the different ways Composer can be installed.

[0.0.2]: https://github.com/stellarwp/shepherd/releases/tag/0.0.2

## [0.0.1] 2025-07-14

* Feature - Initial release of Shepherd.

[0.0.1]: https://github.com/stellarwp/shepherd/releases/tag/0.0.1
