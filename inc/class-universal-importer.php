<?php

require_once( dirname( __FILE__ ) . '/class-site-indexer.php' );
require_once( dirname( __FILE__ ) . '/class-page-fetcher.php' );
require_once( dirname( __FILE__ ) . '/class-page-traverser.php' );

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
			var_dump( "fetching $url" );
			$html = $page_fetcher->fetch( $url );
			$nav_links = $page_traverser->get_navigation( $html );
			var_dump( "nav links", $nav_links );

			$content = $page_traverser->get_content( $html );
			var_dump( "content", $content );
		}

	}
}