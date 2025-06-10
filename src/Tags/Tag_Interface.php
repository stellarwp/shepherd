<?php

namespace StellarWP\Pigeon\Tags;

interface Tag_Interface {

	public function register( $tags );

	public function get_tag_name(): string;

	public function compose(): string;

	public function render(): string;
}
