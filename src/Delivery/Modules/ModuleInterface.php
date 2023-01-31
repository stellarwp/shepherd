<?php

namespace StellarWP\Pigeon\Delivery\Modules;

use StellarWP\Pigeon\Templates\TemplateInterface;

interface ModuleInterface {

	public function envelope( TemplateInterface $template );
	public function send( array $envelopes );

}