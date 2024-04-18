<?php

require_once( dirname( dirname( __DIR__ ) ) . '/inc/class-site-indexer.php' );
require_once( dirname( dirname( __DIR__ ) ) . '/inc/class-universal-importer.php' );

// Minimal PHPUnit test
class TestUniversalImporter extends \PHPUnit\Framework\TestCase {
	public function setUp(): void {
		// Since this is a singleton, we're setting the local cache dir for all subsequent fetches.
		$fetcher = PageFetcher::instance();
		$fetcher->set_local_cache_dir( dirname( __FILE__ ) . '/data' );
	}

	// FIXME: move these to separate test classes
	public function test_sitemap_live() {
		$site_indexer = SiteIndexer::instance();
		$sitemaps = $site_indexer->get_sitemaps( 'https://buffalo.wordcamp.org/2024/' );
		$this->assertNotEmpty( $sitemaps );
		$this->assertIsArray( $sitemaps );
	}

	public function test_sitemap_urlcount() {
		$site_indexer = SiteIndexer::instance();
		$sitemaps = $site_indexer->get_sitemaps( 'https://buffalo.wordcamp.org/2024/' );
		$this->assertNotEmpty( $sitemaps );
		$urls = $site_indexer->get_urls();
		#var_dump( $urls );
		$this->assertNotEmpty( $urls );
		$this->assertIsArray( $urls );
		$this->assertGreaterThan( 10, count( $urls ) );
	}

	public function test_import()  {
		// FIXME: use phpunit's mock stuff for this instead
		$pages = [];

		$handler = function( $url, $blocks ) use ( &$pages ) {
			$pages[ $url ] = $blocks;
		};

		$importer = Universal_Importer::instance();
		$importer->import( 'https://buffalo.wordcamp.org/2024/', $handler );
		// Should be exactly one "content" section
		$this->assertGreaterThan( 1, $importer->last_page_content->count() );
		$this->assertEquals( 16, $importer->navigation->count() );

		$this->assertEquals( 29, count( $pages ) );

		$fetcher = PageFetcher::instance();
		foreach ( $pages as $url => $blocks ) {
			$this->assertIsString( $url );
			$this->assertIsString( $blocks );

			$expected_file = $fetcher->get_cache_filename( 'buffalo.wordcamp.org', $url ) . '.blocks';
			if ( !file_exists( $expected_file ) ) {
				// FIXME: used to create test files
				#file_put_contents( $expected_file, $blocks  );
			}
			$expected_blocks = file_get_contents( $expected_file );
			$this->assertEquals( $expected_blocks, $blocks, "Failed matching extracted blocks to expected for $url ($expected_file)" );
		}
	}

}