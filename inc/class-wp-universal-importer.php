<?php

// The WP importer class and UI

if ( ! class_exists( 'WP_Importer' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
}

require_once( __DIR__ . '/class-site-indexer.php' );
require_once( __DIR__ . '/class-universal-importer.php' );

class WP_Universal_Importer extends WP_Importer {

	protected $url_remap = [];

	public function __construct() {
		// Initialization code here
		$this->register_importer();
	}

	public function register_importer() {
		if ( function_exists( 'register_importer' ) ) {
			register_importer(
				'universal-importer',
				'Universal Importer',
				__('Import any web site.', 'universal-importer'),
				array($this, 'dispatch')
			);
		}
	}
	public function dispatch() {
		// Crude progress output
		while( ob_get_level() > 0 ) {
			ob_end_flush();
		}
		flush();
		ob_implicit_flush( true );

		echo '<div class="wrap">';
		echo '<h2>' . __('My Custom Importer', 'my-custom-importer') . '</h2>';

		// FIXME: need nonce checks for form handling

		// Check if the form was submitted
		if (isset($_POST['submit']) && !empty($_POST['source_url'])) {
			$url = esc_url_raw( trim( $_POST['source_url'] ) );
			// Note: this is not really sufficient validation; unfortunately wp_http_validate_url() is misnamed and there is no core function fit for purpose.
			$url = filter_var( $url, FILTER_VALIDATE_URL );

			if ( ! $url ) {
				echo '<p>Invalid URL: <code>' . esc_html( $_POST['source_url'] ) . '</code></p>';
				$this->greet();
				return;
			}

			if ( ! empty( $_POST['single'] ) ) {
				// Just a single page
				$this->perform_import($url, true);
			} else {
				$site_indexer = SiteIndexer::instance();
				$sitemaps = $site_indexer->get_sitemaps( $url );
				if ( empty( $sitemaps ) ) {
					echo '<p>No sitemaps found at ' . esc_html($url) . '</p>';
				} elseif ( empty( $site_indexer->get_urls() ) ) {
					echo '<p>No URLs found at ' . esc_html($url) . '</p>';
				} elseif ( count( $site_indexer->get_urls() ) > 100 ) {
					echo '<p>More than 100 pages to import: ' . count( $site_indexer->get_urls() ) . ' URLs found at ' . esc_html($url) . '</p>';
				} else {
					if ( empty( $_POST['confirm'] ) ) {
						echo '<p>Found ' . count( $site_indexer->get_urls() ) . ' pages to import from ' . esc_html($url) . '</p>';
						echo '<form method="post">';
						echo '<input type="hidden" name="source_url" value="' . esc_attr($url) . '" />';
						echo '<input type="hidden" name="confirm" value="1" />';
						echo '<input type="submit" name="submit" value="' . esc_attr__('Confirm Import', 'my-custom-importer') . '" />';
						echo '</form>';
					} else {
						// Start the import
						$this->perform_import($url);
					}
				}
			}
		} elseif ( isset( $_POST['submit'] ) && !empty( $_POST['html_in'] ) ) {
			// Note: zero validation or sanitizing, not sure yet what we need to allow or prevent.
			$html = wp_unslash( $_POST['html_in'] );

			$html_transformer = HTMLTransformer::instance();
			$blocks = $html_transformer->transform( $html );

			if ( $blocks && !empty( $_POST['html_to_page'] ) ) {
				$post_id = wp_insert_post( array(
					'post_title' => 'Imported Page',
					'post_content' => $blocks,
					'post_status' => 'publish',
					'post_type' => 'page',
				) );
				printf( '<p>Created page <a href="%s">%s</a></p>', get_page_link( $post_id ), get_page_link( $post_id ) );
				echo '<pre><code style="white-space: pre-wrap">' . esc_html( $blocks ) . '</code></pre>';
			} else {
				echo '<pre><code style="white-space: pre-wrap">' . esc_html( $blocks ) . '</code></pre>';
				$this->greet();
			}
		} else {
			$this->greet();
		}

		echo '</div>';
	}

	private function greet() {
		?>
		<form method="post">
			<p><?php _e('Enter the URL of the site to import from:', 'my-custom-importer'); ?></p>
			<input type="url" name="source_url" value="" placeholder="https://buffalo.wordcamp.org/2024/" size=80 />
			<input type="submit" name="submit" value="<?php esc_attr_e('Import', 'my-custom-importer'); ?>">
			<p><label><input type="checkbox" name="single" value="1" /> <?php _e('Import a single page only', 'my-custom-importer'); ?></label></p>
		</form>

		<form method="post">
			<p><?php _e('Or, enter some HTML markup to convert to blocks:', 'my-custom-importer'); ?></p>
			<textarea name="html_in" rows="20" cols="80" placeholder="Paste HTML here"><?php echo !empty( $_POST['html_in'] ) ? esc_html( wp_unslash( $_POST['html_in'] ) ) : '' ?></textarea>
			<input type="submit" name="submit" value="<?php esc_attr_e('Convert', 'my-custom-importer'); ?>">
			<p><label><input type="checkbox" name="html_to_page" value="1" <?php checked( !empty( $_POST['html_to_page'] ) ); ?> /> <?php _e('Create a page with this content', 'my-custom-importer'); ?></label></p>
		</form>

		<?php
	}

	public function perform_import($url, $single_page = false ) {
		echo '<p>Starting import from: ' . esc_html($url) . '</p>';

		$universal_importer = Universal_Importer::instance();

		if ( $single_page ) {
			$universal_importer->import_page( $url, array( $this, 'import_page' ) );
		} else {
			$universal_importer->import( $url, array( $this, 'import_page' ) );
		}
	}

	public function import_page( $url, $blocks, $page ) {
		echo '<p>Importing ' . $page->get_post_type() . ': ' . esc_html($url) . '</p>';

		$page_slug = parse_url( $page->get_canonical(), PHP_URL_PATH );
		$post_id = $page->get_post_id() ?: null;

		wp_insert_post( array(
			'import_id' => $post_id,
			'post_title' => $page->get_title(),
			'post_content' => $blocks,
			'post_status' => 'publish',
			'post_type' => $page->get_post_type(),
			'post_name' => $page_slug,
		) );

		// Set the front page if this is the home page
		// FIXME: should also/alternatively check if the URL matches the site root.
		if ( $page->is_static_home_page() && $page->get_post_id() ) {
			update_option( 'page_on_front', $page->get_post_id() );
			update_option( 'show_on_front', 'page' );
			echo '<p>Set ' . esc_html($url) . ' as the front page</p>';
		}

		flush();
	}


}
