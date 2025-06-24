<?php
/**
 * Pigeon's wrapper of Action Scheduler methods.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon;

/**
 * Pigeon's wrapper of Action Scheduler methods.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon;
 */
class Action_Scheduler_Methods {
	/**
	 * Checks if an action is scheduled.
	 *
	 * @since TBD
	 *
	 * @param string $hook The hook of the action.
	 * @param array  $args The arguments of the action.
	 * @param string $group The group of the action.
	 *
	 * @return bool Whether the action is scheduled.
	 */
	public static function has_scheduled_action( string $hook, array $args = [], string $group = '' ): bool {
		return as_has_scheduled_action( $hook, $args, $group );
	}

	/**
	 * Schedules a single action.
	 *
	 * @since TBD
	 *
	 * @param int    $timestamp The timestamp of the action.
	 * @param string $hook      The hook of the action.
	 * @param array  $args      The arguments of the action.
	 * @param string $group     The group of the action.
	 * @param bool   $unique    Whether the action should be unique.
	 * @param int    $priority  The priority of the action.
	 *
	 * @return int The action ID.
	 */
	public static function schedule_single_action( int $timestamp, string $hook, array $args = [], string $group = '', bool $unique = false, int $priority = 10 ): int {
		return as_schedule_single_action( $timestamp, $hook, $args, $group, $unique, $priority );
	}
}
