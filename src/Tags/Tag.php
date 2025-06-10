<?php

namespace StellarWP\Pigeon\Tags;

use StellarWP\Pigeon\Models\Entry;
use StellarWP\Pigeon\Templates\Template_Interface;

abstract class Tag implements Tag_Interface {

	protected $entry;

	protected $composed;

	protected $template;

	public function __construct() {
		add_filter( 'stellarwp_pigeon_register_tag', [ $this, 'register' ] );
	}

	public function set_template( Template_Interface $template ) {
		$this->template = $template;

		return $this;
	}

	public function get_template(): Template_Interface {
		return $this->template;
	}

	public function set_entry( Entry $entry ) {
		$this->entry = $entry;

		return $this;
	}

	public function get_entry() {
		return $this->entry;
	}

	public function get_tag_name(): string {
		return "%%{$this->tag_name}%%";
	}

	public function compose(): string {
		return str_replace( $this->get_tag_name(), $this->render(), $this->get_template()->get_rendered_content() );
	}
}
