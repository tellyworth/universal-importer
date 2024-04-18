<?php

// The WP importer class and UI

if ( ! class_exists( 'WP_Importer' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
}

require_once( __DIR__ . '/class-site-indexer.php' );

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
		echo '<div class="wrap">';
		echo '<h2>' . __('My Custom Importer', 'my-custom-importer') . '</h2>';

		var_dump( $_POST );

		// Check if the form was submitted
		if (isset($_POST['submit']) && !empty($_POST['source_url'])) {
			$url = esc_url_raw($_POST['source_url']);
			$site_indexer = SiteIndexer::instance();
			$sitemaps = $site_indexer->get_sitemaps( 'https://buffalo.wordcamp.org/2024/' );
			var_dump( __METHOD__, $sitemaps );
			var_dump( "found pages", count($site_indexer->get_urls()) );
			#$this->perform_import($url);
		} else {
			$this->greet();
		}

		echo '</div>';
	}

	private function greet() {
		?>
		<form method="post">
			<p><?php _e('Enter the URL of the source to import from:', 'my-custom-importer'); ?></p>
			<input type="url" name="source_url" value="" placeholder="https://boulder.wordcamp.org/2024/" />
			<input type="submit" name="submit" value="<?php esc_attr_e('Import', 'my-custom-importer'); ?>">
		</form>
		<?php
	}

	private function perform_import($url) {
		// Your import logic based on the URL
		echo '<p>Starting import from: ' . esc_html($url) . '</p>';
		// Example of fetching data:
		$response = wp_remote_get($url);
		if (is_wp_error($response)) {
			echo '<p>Error: Unable to fetch data.</p>';
		} else {
			$data = wp_remote_retrieve_body($response); // assuming the data is directly usable or further processing is needed
			// Implement your actual data processing and importing logic here.
			echo '<p>Import complete.</p>';
		}
	}

}
