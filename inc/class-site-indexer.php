<?php

use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;

require_once( dirname( __FILE__ ) . '/class-page-fetcher.php' );

// FIXME: needed for wp-now until mb_split is supported
if ( !function_exists( 'mb_split' ) ) {
	function mb_split( $pattern, $string, $limit = -1 ) {
		return preg_split( "/$pattern/u", $string, $limit );
	}
}

class SiteIndexer {
	/**
	 * @var SiteIndexer
	 */
	private static $instance;

	private $parser;

	private $sitemaps = [];
	private $urls = [];

	/**
	 * @return SiteIndexer
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * SiteIndexer constructor.
	 */
	private function __construct() {
		// Note UA string doesn't matter since we do our own fetching with wp_remote_get().
		$this->parser = new SitemapParser( 'UniversalImporter' );
	}

	/**
	 * @param string $url URL of the home page of the site to index.
	 * @param array $pages Array of pages to check for site maps, in addititon to the home page.
	 * @return array
	 */
	public function get_sitemaps( $url, $pages = [ 'robots.txt', 'wp-sitemap.xml', 'sitemap.xml', 'sitemap-index-1.xml' ] ) {
		try {
			// Reset data for a new request.
			// This class probably shouldn't be a singleton, which would make this unnecessary.
			$this->sitemaps = [];
			$this->urls = [];

			$parts = parse_url( $url );

			// Queue up specific pages first
			$urls = [];
			foreach ( $pages as $page ) {
				$urls[] = $parts['scheme'] . '://' . $parts['host'] . '/' . $page;
			}

			$done = [];
			$limit = 10;

			$fetcher = PageFetcher::instance();

			while ( $url = array_shift( $urls ) ) {
				if ( $limit-- < 0 ) {
					break;
				}
				if ( in_array( $url, $done ) ) {
					continue;
				}
				$done[] = $url;
				$res = $fetcher->fetch( $url );
				if ( !is_wp_error( $res ) ) {
					$this->parser->parse( $url, $res );
					if ( $this->parser->getURLs() ) {
						$this->urls = array_merge( $this->urls, $this->parser->getURLs() );
					}
					foreach ( $this->parser->getSitemaps() as $sitemap_url => $tags ) {
						if ( ! in_array( $sitemap_url, $done ) && ! in_array( $sitemap_url, $urls ) ) {
							$urls[] = $sitemap_url;
							$this->sitemaps[] = $sitemap_url;
						}
					}
				} else {
					return $res;
				}
			}

			return $this->sitemaps;
		} catch (SitemapParserException $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Load a sitemap from a file.
	 *
	 * @param string $url
	 * @param string $filename
	 */
	public function get_sitemap_from_file( $url, $filename ) {
		$sitemap = file_get_contents( $filename );
		$this->parser->parse( $url, $sitemap );
	}

	/**
	 * Fetch URLs from site maps, called after get_sitemaps().
	 *
	 * @return array
	 */
	public function get_urls() {
		return array_keys( $this->urls );
	}


}