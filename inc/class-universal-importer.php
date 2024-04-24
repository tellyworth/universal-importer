<?php

require_once( dirname( __FILE__ ) . '/class-site-indexer.php' );
require_once( dirname( __FILE__ ) . '/class-page-fetcher.php' );
require_once( dirname( __FILE__ ) . '/class-page-traverser.php' );
require_once( dirname( __FILE__ ) . '/class-html-transformer.php' );

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

	public function import( $url, $handler = null, $media_handler = null ) {
		$site_indexer = SiteIndexer::instance();
		$sitemaps = $site_indexer->get_sitemaps( $url );

		if ( empty( $sitemaps ) ) {
			return [];
		}
		$page_fetcher = PageFetcher::instance();
		$page_traverser = PageTraverser::instance();
		$html_transformer = HTMLTransformer::instance();

		$pages = $site_indexer->get_urls();

		foreach ( $pages as $url ) {
			$html = $page_fetcher->fetch( $url );
			$page_traverser->parse_content( $html );

			// Process internal media URLs before we start fetching content, so we can rewrite URLs to local attachments.
			$this->internal_media_urls = $page_traverser->get_internal_media_urls();
			if ( $this->internal_media_urls && is_callable( $media_handler ) ) {
				call_user_func( $media_handler, $this->internal_media_urls, $page_traverser );
			}

			$this->navigation = $page_traverser->get_navigation();

			$this->last_page_content = $page_traverser->get_content();

			if ( $this->last_page_content ) {
				$blocks = $html_transformer->transform( $this->last_page_content );

				if ( $handler && is_callable( $handler ) ) {
					call_user_func( $handler, $url, $blocks, $page_traverser );
				}

			}
			#return; // FIXME short circuit for now
		}

	}
}