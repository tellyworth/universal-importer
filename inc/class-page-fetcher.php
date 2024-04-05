<?php

class PageFetcher {
	/**
	 * @var PageFetcher
	 */
	private static $instance;

	/**
	 * @return PageFetcher
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * PageFetcher constructor.
	 */
	private function __construct() {
		// Add your hooks here
	}

	/**
	 * Fetch a page
	 *
	 * @param string $url
	 * @return string
	 */
	public function fetch( $url ) {
		// Fetch the page
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		return wp_remote_retrieve_body( $response );
	}
}