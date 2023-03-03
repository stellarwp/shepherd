<?php

namespace StellarWP\Pigeon\Models;

use StellarWP\Pigeon\Delivery\Modules\Mail;
use StellarWP\Pigeon\Schema\Tables\Entries;
use StellarWP\Pigeon\Tags\Collection;
use StellarWP\Pigeon\Templates\Default_Template;

class Entry implements Model_Interface {

	public $type;

	protected $data;

	protected $static_data_keys = [
		'template_id',
		'content',
		'delivery_module',
		'created_at',
	];

	protected static $instance;

	public static function instance() {
		if ( static::$instance instanceof Entry ) {
			return static::$instance;
		}

		static::$instance = new Entry();

		return static::$instance;
	}

	protected function cleanup( $args ) {
		static $clean_args = [];
		foreach( $args as $key => $arg ) {

			if ( is_numeric( $key ) && is_array( $arg ) && 1 === count( $arg ) ) {
				$this->cleanup( $arg );
			} else {
				$clean_args[ $key ] = $arg;
			}
		}

		return $clean_args;
	}

	public function set_data( ...$args ): Entry {
		$this->raw_data = $args;
		$this->data = $this->cleanup( $this->raw_data );

		try {
			$this->validate_dataset();
			$this->compose()->save();
		} catch ( \Exception $exception ) {

		}

		return $this;
	}

	public function get( $key ) {
		return $this->get_data()->{$key} ?? false;
	}

	public function module_active() {
		return true;
	}

	public function validate_dataset(): bool {

		switch ( $this->type ) {
			case Mail::class:
			default:
				return true;
		}
	}

	public function compose(): Entry {
		$this->template = new Default_Template( 'tickets/email', $this );
		$this->keys     = $this->generate_keys();

		$this->data = wp_parse_args( $this->data,
			[
				'template_id'     => $this->template->get_key( 'ID' ),
				'content'         => $this->template->render()->get_rendered_content(),
				'delivery_module' => 'mail',
				'status'          => 'draft',
				'public_key'      => $this->keys['public'],
				'private_key'     => $this->keys['private'],
				'retries'         => 0,
			]
		);

		$this->data = apply_filters( 'stellarwp_pigeon_register_entry_data', $this->data, $this );

		return $this;
	}

	public function generate_keys() {
		$public_data = json_encode( $this->data );
		$uuid        = wp_generate_uuid4();

		return [
			'public'  => $uuid,
			'private' => $this->hash_key( $public_data, $uuid ),
		];
	}

	public function hash_key( $public_data, $uuid ) {
		return md5( $public_data . $uuid );
	}

	public function check_keys( $uuid ) {
		return hash_equals( $this->get_private_key(), $this->hash_key( $this->get_static_data_string(), $uuid ) );
	}

	public function get_data() {
		return $this->data;
	}

	public function get_static_data_keys() {
		return $this->static_data_keys;
	}

	public function get_static_data() {
		return \array_intersect_key( $this->get_data(), array_flip( $this->get_static_data_keys() ) );
	}

	public function get_static_data_string() {
		return json_encode( $this->get_static_data() );
	}

	public function get_private_key() {
		return $this->data['private_key'];
	}

	public function clean_data() {
		$data = $this->get_data();
		$formats = Entries::column_formats();
		$extra_keys = array_diff( array_keys( $data ), array_keys( $formats ) );

		foreach( $extra_keys as $key ) {
			unset( $data[ $key ] );
		}

		return $data;
	}

	public function save(): Entry {
		global $wpdb;
		$entry_table = Entries::base_table_name();
		$clean_data = $this->clean_data();

		if ( false === $wpdb->insert( $entry_table, $clean_data ) ) {
			throw new \Exception();
		}

		return $this;
	}

	public function schedule(): Entry {
		return $this;
	}
}