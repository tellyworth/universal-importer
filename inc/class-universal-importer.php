<?php

class Universal_Importer {

	/**
	 * @var Universal_Importer
	 */
	private static $instance;

	/**
	 * @return Universal_Importer
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Universal_Importer constructor.
	 */
	private function __construct() {
		// Add your hooks here
	}
}