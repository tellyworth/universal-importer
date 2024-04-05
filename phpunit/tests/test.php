<?php

require_once( dirname( dirname( __DIR__ ) ) . '/inc/class-site-indexer.php' );
require_once( dirname( dirname( __DIR__ ) ) . '/inc/class-universal-importer.php' );

// Minimal PHPUnit test
class TestUniversalImporter extends \PHPUnit\Framework\TestCase {
	public function test_sitemap_live() {
		$site_indexer = SiteIndexer::instance();
		$sitemaps = $site_indexer->get_sitemaps( 'https://flightpath.blog/' );
		$this->assertNotEmpty( $sitemaps );
		$this->assertIsArray( $sitemaps );
	}

	public function test_sitemap_live_urlcount() {
		$site_indexer = SiteIndexer::instance();
		$sitemaps = $site_indexer->get_sitemaps( 'https://flightpath.blog/' );
		$this->assertNotEmpty( $sitemaps );
		$urls = $site_indexer->get_urls();
		var_dump( $urls );
		$this->assertNotEmpty( $urls );
		$this->assertIsArray( $urls );
		$this->assertGreaterThan( 10, count( $urls ) );
	}

	public function test_local_parse() {
		$sitemap = file_get_contents( dirname( __FILE__ ) . '/data/sitemap.xml' );
		$parser = new vipnytt\SitemapParser();
		$parser->parse( 'https://flightpath.blog/sitemap.xml', $sitemap );
		#var_dump( $parser->getSitemaps() );
		#var_dump( $parser->getURLs() );
		$this->assertNotEmpty( $parser->getSitemaps() );
	}

	public function test_import()  {
		$importer = Universal_Importer::instance();
		$importer->import( 'https://flightpath.blog/' );
		$this->assertTrue( true );
	}

}