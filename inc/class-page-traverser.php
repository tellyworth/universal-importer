<?php

class PageTraverser {
	/**
	 * @var PageTraverser
	 */
	private static $instance; // FIXME: not sure this should be a singleton.

	private $dom;
	private $xpath;

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
	 * Parse content from a page.
	 */
	public function parse_content( $html ) {
		$this->dom = new DOMDocument();
		$this->dom->loadHTML( $html , LIBXML_NOERROR | LIBXML_NOWARNING );
		$this->xpath = new DOMXPath( $this->dom );
	}

	/**
	 * Traverse a page and return the nodes matching the given xpath.
	 * parse_content() must be called first.
	 *
	 * @param string $xpath
	 * @return string
	 */
	public function get_xpath( $xpath ) {
		return $this->xpath->query( $xpath );
	}

	public function get_content() {
		$article = $this->get_xpath( '//article/*' );
		if ( $article->count() ) {
			return $article;
		}

		// Common in modern themes
		$div = $this->get_xpath( '//main//div[contains(@class, \'entry-content\')]/*' );
		if ( $div->count() ) {
			return $div;
		}

		$main = $this->get_xpath( '//main/*' );
		if ( $main->count() ) {
			return $main;
		}
	}

	public function get_navigation() {
		// Links within the nav element
		$nav_a = $this->get_xpath( '//nav//a' );

		return $nav_a;
	}

	public function get_title() {
		$title = $this->get_xpath( '//meta[@property="og:title"]' );
		if ( $title->count() ) {
			return $title->item(0)->getAttribute('content');
		}
		// Title tag is sub-optimal because it often includes the site name and other SEO cruft.
		$title = $this->get_xpath( '//title' );
		if ( $title->count() ) {
			return $title->item(0)->textContent;
		}
	}

	public function get_canonical() {
		$canonical = $this->get_xpath( '//link[@rel="canonical"]' );
		if ( $canonical->count() ) {
			return $canonical->item(0)->getAttribute('href');
		}
	}

	public function get_post_type() {
		// Check for a post type in the body class
		$body = $this->get_xpath( '//body' );
		if ( $body->count() ) {
			$classes = explode( ' ', $body->item(0)->getAttribute('class') );
			if ( in_array( 'page', $classes ) ) {
				return 'page';
			}
			foreach ( $classes as $class ) {
				if ( preg_match( '/([-\w]+)-template-default/', $class, $matches ) ) {
					return $matches[1];
				}
			}
		}
	}

	public function get_post_id() {
		// Check for a post ID in the body class
		$body = $this->get_xpath( '//body' );
		if ( $body->count() ) {
			$classes = explode( ' ', $body->item(0)->getAttribute('class') );
			foreach ( $classes as $class ) {
				if ( preg_match( '/(?:postid|pageid|page-id)-(\d+)/', $class, $matches ) ) {
					return $matches[1];
				}
			}
		}
	}

	public function is_static_home_page() {
		// Check for a static home page in the body class
		$body = $this->get_xpath( '//body' );
		if ( $body->count() ) {
			$classes = explode( ' ', $body->item(0)->getAttribute('class') );
			if ( in_array( 'home', $classes ) && $this->get_post_id() ) {
				return true;
			}
		}
	}

	public function get_media_urls() {
		// FIXME: find all image/media URLs, not just img tags
		$images = $this->get_xpath( '//img' );
		$urls = [];
		foreach ( $images as $image ) {
			$src = $image->getAttribute('src');
			if ( $src ) {
				$urls[] = $src;
			}
		}
		return $urls;
	}

	/**
	 * Get a list of internal media URLs.
	 * That is, images and other media that are part of the site being imported; links to external sites are excluded.
	 */
	public function get_internal_media_urls() {
		$all_urls = $this->get_media_urls();

		// FIXME: should get the site host from the URL being fetched, not rely on markup within it.
		$site_host = strtolower( parse_url( $this->get_canonical(), PHP_URL_HOST ) );
		foreach ( $all_urls as $url ) {
			$parts = parse_url( $url );
			// FIXME: account for CDNs, wp.com files domains, etc
			if ( $parts['host'] === $site_host ) {
				$urls[] = $url;
			} elseif ( ! isset( $parts['host'] ) ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}
}