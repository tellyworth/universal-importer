<?php

require_once( dirname( __FILE__ ) . '/class-site-indexer.php' );
require_once( dirname( __FILE__ ) . '/class-page-fetcher.php' );
require_once( dirname( __FILE__ ) . '/class-page-traverser.php' );

class Universal_Importer {

	/**
	 * @var Universal_Importer
	 */
	private static $instance;

	public $navigation;
	public $last_page_content;

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

	public function import( $url ) {
		$site_indexer = SiteIndexer::instance();
		$sitemaps = $site_indexer->get_sitemaps( $url );

		if ( empty( $sitemaps ) ) {
			return [];
		}
		$page_fetcher = PageFetcher::instance();
		$page_traverser = PageTraverser::instance();

		$pages = $site_indexer->get_urls();

		foreach ( $pages as $url ) {
			$html = $page_fetcher->fetch( $url );
			$page_traverser->parse_content( $html );
			$this->navigation = $page_traverser->get_navigation();

			$this->last_page_content = $page_traverser->get_content();
			return; # FIXME
		}

	}
}