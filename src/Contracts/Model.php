<?php
/**
 * The Pigeon model contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Contracts;
 */

declare( strict_types=1 );

namespace StellarWP\Pigeon\Contracts;

use StellarWP\Pigeon\Abstracts\Table_Abstract;

/**
 * The Pigeon model contract.
 *
 * @since TBD
 *
 * @package StellarWP\Pigeon\Contracts;
 */
interface Model {
	/**
	 * Gets the model's ID.
	 *
	 * @since TBD
	 *
	 * @return int The model's ID.
	 */
	public function get_id(): int;

	/**
	 * Sets the model's ID.
	 *
	 * @since TBD
	 *
	 * @param int $id The model's ID.
	 */
	public function set_id( int $id ): void;

	/**
	 * Saves the model.
	 *
	 * @since TBD
	 *
	 * @return int The id of the saved model.
	 */
	public function save(): int;

	/**
	 * Deletes the model.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function delete(): void;

	/**
	 * Gets the table interface for the model.
	 *
	 * @since TBD
	 *
	 * @return Table_Abstract The table interface.
	 */
	public function get_table_interface(): Table_Abstract;

	/**
	 * Converts the model to an array.
	 *
	 * @since TBD
	 *
	 * @return array The model as an array.
	 */
	public function to_array(): array;

	/**
	 * Converts an array to a model.
	 *
	 * @since TBD
	 *
	 * @param array $data The model data.
	 *
	 * @return self The model.
	 */
	public static function from_array( array $data ): self;
}
