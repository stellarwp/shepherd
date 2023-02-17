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



'event-title',
'event-excerpt',
'event-start-date',
'event-end-date',
'event-start-time',
'event-end-time',
'event-online',
'zoom-id',
'zoom-url',
'zoom-password',
'zoom-numbers',
'location-name',
'location-address',
'ticket-name',
'ticket-price'.
'ticket-remaining',
'event-url',
'days-until-event',
'attendee-first-name',
'attendee-last-name',
'attendee-ticket-type',
