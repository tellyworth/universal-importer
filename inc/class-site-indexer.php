<?php

use vipnytt\SitemapParser;
use vipnytt\SitemapParser\Exceptions\SitemapParserException;

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
		$this->parser = new SitemapParser( 'MyCustomUserAgent' );
	}

	/**
	 * @param string $url URL of the home page of the site to index.
	 * @param array $pages Array of pages to check for site maps, in addititon to the home page.
	 * @return array
	 */
	public function get_sitemaps( $url, $pages = [ 'robots.txt', 'sitemap.xml', 'wp-sitemap.xml', 'sitemap-index-1.xml' ] ) {
		try {
			$parts = parse_url( $url );
			// Queue up specific pages first
			$urls = [];
			foreach ( $pages as $page ) {
				$urls[] = $parts['scheme'] . '://' . $parts['host'] . '/' . $page;
			}

			#$parser = new SitemapParser( 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.4; rv:124.0) Gecko/20100101 Firefox/124.0', $config );
			#$parser->addToQueue( $urls );


			#$parser->parseRecursive( $url );
			#var_dump( $parser );
			var_dump( $urls );
			$done = [];
			$limit = 10;

			while ( $url = array_shift( $urls ) ) {
				if ( $limit-- < 0 ) {
					break;
				}
				if ( in_array( $url, $done ) ) {
					continue;
				}
				echo "Fetching $url\n";
				$done[] = $url;
				$res = wp_remote_get( $url );
				if ( !is_wp_error( $res ) ) {
					$this->parser->parse( $url, $res['body'] );
					if ( $this->parser->getURLs() ) {
						$this->urls = array_merge( $this->urls, $this->parser->getURLs() );
					}
					foreach ( $this->parser->getSitemaps() as $sitemap_url => $tags ) {
						echo '*** Sitemap: ' . $sitemap_url . "\n";
						if ( ! in_array( $sitemap_url, $done ) && ! in_array( $sitemap_url, $urls ) ) {
							$urls[] = $sitemap_url;
							$this->sitemaps[] = $sitemap_url;
							echo "*** Count is now " . count( $urls ) . "\n";
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