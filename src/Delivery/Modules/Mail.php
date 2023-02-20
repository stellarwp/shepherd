<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Entry\Base;
use StellarWP\Pigeon\Entry\Model_Interface;
use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Scheduling\Action_Scheduler;
use StellarWP\Pigeon\Templates\Template_Interface;

class Mail implements Module_Interface {

	public static $instance;

	public $scheduled = true;


	public static function init() :Mail {
		if ( static::$instance instanceof Mail ) {
			return static::$instance;
		}

		static::$instance = new Mail();
		return static::$instance;
	}

	public function send( Entry $entry ) :Mail {
		// wp_mail();
		return $this;
	}
}



