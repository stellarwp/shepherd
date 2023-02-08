<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Entry\Entry_Interface;
use StellarWP\Pigeon\Templates\Template_Interface;

interface Module_Interface {

	public function deliver( Entry_Interface $entry )
	public function envelope( Template_Interface $template );
	public function send( array $envelopes );

}