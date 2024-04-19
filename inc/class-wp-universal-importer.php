<?php

// The WP importer class and UI

if ( ! class_exists( 'WP_Importer' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
}

require_once( __DIR__ . '/class-site-indexer.php' );
require_once( __DIR__ . '/class-universal-importer.php' );

class WP_Universal_Importer extends WP_Importer {
	public function __construct() {
		// Initialization code here
		$this->register_importer();
	}

	public function register_importer() {
		register_importer(
			'universal-importer',
			'Universal Importer',
			__('Import any web site.', 'universal-importer'),
			array($this, 'dispatch')
		);
	}
	public function dispatch() {
		// Crude progress output
		ob_flush(); flush();
		ob_implicit_flush( true );

		echo '<div class="wrap">';
		echo '<h2>' . __('My Custom Importer', 'my-custom-importer') . '</h2>';

		// Check if the form was submitted
		if (isset($_POST['submit']) && !empty($_POST['source_url'])) {
			$url = esc_url_raw($_POST['source_url']);
			$site_indexer = SiteIndexer::instance();
			$sitemaps = $site_indexer->get_sitemaps( $_POST['source_url'] );
			if ( empty( $sitemaps ) ) {
				echo '<p>No sitemaps found at ' . esc_html($url) . '</p>';
			} elseif ( empty( $site_indexer->get_urls() ) ) {
				echo '<p>No URLs found at ' . esc_html($url) . '</p>';
			} elseif ( count( $site_indexer->get_urls() ) > 100 ) {
				echo '<p>More than 100 pages to import: ' . count( $site_indexer->get_urls() ) . ' URLs found at ' . esc_html($url) . '</p>';
			} else {
				// Start the import
				$this->perform_import($url);
			}
		} else {
			$this->greet();
		}

		echo '</div>';
	}

	private function greet() {
		?>
		<form method="post">
			<p><?php _e('Enter the URL of the source to import from:', 'my-custom-importer'); ?></p>
			<input type="url" name="source_url" value="" placeholder="https://buffalo.wordcamp.org/2024/" />
			<input type="submit" name="submit" value="<?php esc_attr_e('Import', 'my-custom-importer'); ?>">
		</form>
		<?php
	}

	private function perform_import($url) {
		echo '<p>Starting import from: ' . esc_html($url) . '</p>';

		$universal_importer = Universal_Importer::instance();
		$universal_importer->import( $url, array( $this, 'import_page' ) );
	}

	public function import_page( $url, $blocks, $page ) {
		echo '<p>Importing ' . $page->get_post_type() . ': ' . esc_html($url) . '</p>';

		$page_slug = parse_url( $page->get_canonical(), PHP_URL_PATH );
		wp_insert_post( array(
			'import_id' => $page->get_post_id(),
			'post_title' => $page->get_title(),
			'post_content' => $blocks,
			'post_status' => 'publish',
			'post_type' => $page->get_post_type(),
			'post_name' => $page_slug,
		) );
	}

}
