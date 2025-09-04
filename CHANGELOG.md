# Change Log

All notable changes to this project will be documented in this file. This project adhere to the [Semantic Versioning](http://semver.org/) standard.

## [0.0.7] 2025-09-04

* Fix - Ensure the regulator is registered only when the tables are created/updated successfully.
* Fix - When scheduling an action, return 0 if the action ID is not an integer.
* Fix - Fix fetch_all Custom_Table_Query_Methods batch generator by properly incrementing the offset.
* Tweak - Update the schema version of the Tasks table to 0.0.3 to fix a typo in the version string.
* Tweak - Update the get_pending_actions_by_ids method to also exclude null actions.
* Tweak - Use the hook `action_scheduler_init` to determine if Action Scheduler is initialized instead of the `init` hook.

[0.0.7]: https://github.com/stellarwp/shepherd/releases/tag/0.0.7

## [0.0.6] 2025-08-26

* Fix - Update Email task to properly handle multiple email recipients separated by commas.

[0.0.6]: https://github.com/stellarwp/shepherd/releases/tag/0.0.6

## [0.0.5] 2025-08-19

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
