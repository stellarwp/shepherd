<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Templates\Template_Interface;

interface Module_Interface {

	public function deliver( Model_Interface $entry );
	public function send( array $envelopes );
	public static function mail( $to, $subject, $message, $headers = '', $attachments = array() );
}