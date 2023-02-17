<?php

namespace StellarWP\Pigeon\Delivery;

class Batch {

	const MAX_BATCH_SIZE = 50;

	protected $type;

	protected $entries;

	public function __construct( $type = null ) {
		$this->type = $type;
		$this->entries = $this->fetch();
	}

	public function fetch() {

	}

	public function dispatch() {
		foreach ( $batch as $entry ) {
			$envelope = Entry::get_entry_envelope( $entry );
			$sender   = new $envelope( $entry );
			$sender->send();
		}
	}

}