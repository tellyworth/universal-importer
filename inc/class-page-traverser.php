<?php

class PageTraverser {
	/**
	 * @var PageTraverser
	 */
	private static $instance;

	/**
	 * @return PageTraverser
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * PageTraverser constructor.
	 */
	private function __construct() {
		// Add your hooks here
	}

	/**
	 * Traverse a page
	 *
	 * @param string $html
	 * @return string
	 */
	public function get_xpath( $html, $xpath ) {
		// Traverse the page
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$xpath = new DOMXPath( $dom );
		$nodes = $xpath->query( $xpath );

		return $nodes;
	}

	public function get_content( $html ) {
		if ( $this->get_xpath( $html, '//article' ) ) {
			return $this->get_xpath( $html, '//article' );
		}
	}

	public function get_navigation( $html ) {
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$xpath = new DOMXPath( $dom );
		// Links within the nav element
		$nav_a = $xpath->query( '//nav//a' );
		$links = [];
		foreach ( $nav_a as $node ) {
			$links[] = $node->getAttribute( 'href' );
		}
		return $nav_a;
	}
}