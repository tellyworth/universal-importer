<?php

class PageFetcher {
	/**
	 * @var PageFetcher
	 */
	private static $instance;

	private $local_cache_dir; // Used for testing; will read from this directory instead of fetching from the web.


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

	public function set_local_cache_dir( $path ) {
		// Note: insecure; this is intended for unit testing only.
		if ( !file_exists( $path ) ) {
			mkdir( $path, 0777, true );
		}
		$this->local_cache_dir = realpath( $path );
	}

	public function get_cache_filename( $domain, $url ) {
		return $this->get_local_cache_dir() ? $this->local_cache_dir . '/' . $domain . '-' . hash( 'sha256', $url ) : false;
	}

	public function get_local_cache_dir() {
		return $this->local_cache_dir && file_exists( $this->local_cache_dir ) && is_dir( $this->local_cache_dir );
	}

	private function fetch_url_with_cache( $url ) {
		$domain = parse_url( $url, PHP_URL_HOST );
		$cache_file = $this->get_cache_filename( $domain, $url );

		if ( $cache_file ) {
			if ( file_exists( $cache_file ) ) {
				return file_get_contents( $cache_file );
			}
		}

		$res = wp_remote_get( $url );
		if ( !is_wp_error( $res ) ) {
			if ( $cache_file ) {
				$body = wp_remote_retrieve_body( $res );
			}
			file_put_contents( $cache_file, $body );
			return $body;
		}

		return $res;
	}


	/**
	 * Fetch a page
	 *
	 * @param string $url
	 * @return string
	 */
	public function fetch( $url ) {
		// Fetch the page
		$response = $this->fetch_url_with_cache( $url );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		return $response;
	}
}