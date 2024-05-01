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
		} else {
			$this->greet();
		}

		echo '</div>';
	}

	private function greet() {
		?>
		<form method="post">
			<p><?php _e('Enter the URL of the site to import from:', 'my-custom-importer'); ?></p>
			<input type="url" name="source_url" value="" placeholder="https://buffalo.wordcamp.org/2024/" />
			<input type="submit" name="submit" value="<?php esc_attr_e('Import', 'my-custom-importer'); ?>">
			<p><label><input type="checkbox" name="single" value="1" /> <?php _e('Import a single page only', 'my-custom-importer'); ?></label></p>
		</form>
		<?php
	}

	public function perform_import($url, $single_page = false ) {
		echo '<p>Starting import from: ' . esc_html($url) . '</p>';

		$universal_importer = Universal_Importer::instance();

		if ( $single_page ) {
			$universal_importer->import_page( $url, array( $this, 'import_page' ), array( $this, 'import_media' ) );
		} else {
			$universal_importer->import( $url, array( $this, 'import_page' ), array( $this, 'import_media' ) );
		}
	}

	public function import_media( $media_urls, $page ) {

		$post_id = $page->get_post_id() ?: null;

		// Fetch media files
		if ( $media_urls ) {
			foreach ( $media_urls as $media_url ) {
				if ( isset( $this->url_remap[ $media_url ] ) ) {
					// We've already fetched this file
					continue;
				}
				$attachment_id = $this->fetch_remote_file_to_attachment( $media_url, $post_id );
				if ( is_wp_error( $attachment_id ) ) {
					echo '<p>Error fetching media file: ' . esc_html($media_url) . ' (' . esc_html($attachment_id->get_error_message()) . ')</p>';
				} else {
					echo '<p>Fetched ' . esc_html($media_url) . ' as attachment ID ' . $attachment_id . '</p>';
				}
			}

			// Replace the URLs in the content with the new URLs
			$page->replace_media_urls( $this->url_remap );
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

	/**
	 * Fetch a remote media file, store in it wp-content, and create an attachment post for it.
	 * This and following code was stolen and adapted from class WP_Import in the wordpress-importer plugin
	 *
	 * @param array $post Attachment post details from WXR
	 * @param string $url URL to fetch attachment from
	 * @return int|WP_Error Post ID on success, WP_Error otherwise
	 */
	function fetch_remote_file_to_attachment( $url, $parent_post_id = null ) {

		// if the URL is absolute, but does not contain address, then upload it assuming base_site_url
		if ( preg_match( '|^/[\w\W]+$|', $url ) ) {
			$url = rtrim( $this->base_url, '/' ) . $url;
		}

		$upload = $this->fetch_remote_file( $url );
		if ( is_wp_error( $upload ) ) {
			return $upload;
		}

		$info = wp_check_filetype( $upload['file'] );
		if ( $info ) {
			$post['post_mime_type'] = $info['type'];
		} else {
			return new WP_Error( 'attachment_processing_error', __( 'Invalid file type', 'wordpress-importer' ) );
		}

		$post = [];
		$post['guid'] = $upload['url'];
		$post['content'] = '';
		$post['post_status'] = 'inherit';
		$post['post_title'] = basename( $upload['file'] );

		// as per wp-admin/includes/upload.php
		$post_id = wp_insert_attachment( $post, $upload['file'], $parent_post_id );
		wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );

		return $post_id;
	}


	/**
	 * Attempt to download a remote file attachment
	 *
	 * @param string $url URL of item to fetch
	 * @param string|null $date Date for the attachment, formatted as `yyyy/mm`. Optional.
	 * @return array|WP_Error Local file location details on success, WP_Error otherwise
	 */
	function fetch_remote_file( $url, $date = null ) {
		// Extract the file name from the URL.
		$path      = parse_url( $url, PHP_URL_PATH );
		$file_name = '';
		if ( is_string( $path ) ) {
			$file_name = basename( $path );
		}

		if ( ! $file_name ) {
			$file_name = md5( $url );
		}

		$tmp_file_name = wp_tempnam( $file_name );
		if ( ! $tmp_file_name ) {
			return new WP_Error( 'import_no_file', __( 'Could not create temporary file.', 'wordpress-importer' ) );
		}

		// Fetch the remote URL and write it to the placeholder file.
		$remote_response = wp_remote_get(
			$url,
			array(
				'timeout'  => 300,
				'stream'   => true,
				'filename' => $tmp_file_name,
				'headers'  => array(
					'Accept-Encoding' => 'identity',
				),
			)
		);

		if ( is_wp_error( $remote_response ) ) {
			@unlink( $tmp_file_name );
			return new WP_Error(
				'import_file_error',
				sprintf(
					/* translators: 1: The WordPress error message. 2: The WordPress error code. */
					__( 'Request failed due to an error: %1$s (%2$s)', 'wordpress-importer' ),
					esc_html( $remote_response->get_error_message() ),
					esc_html( $remote_response->get_error_code() )
				)
			);
		}

		$remote_response_code = (int) wp_remote_retrieve_response_code( $remote_response );

		// Make sure the fetch was successful.
		if ( 200 !== $remote_response_code ) {
			@unlink( $tmp_file_name );
			return new WP_Error(
				'import_file_error',
				sprintf(
					/* translators: 1: The HTTP error message. 2: The HTTP error code. */
					__( 'Remote server returned the following unexpected result: %1$s (%2$s)', 'wordpress-importer' ),
					get_status_header_desc( $remote_response_code ),
					esc_html( $remote_response_code )
				)
			);
		}

		$headers = wp_remote_retrieve_headers( $remote_response );

		// Request failed.
		if ( ! $headers ) {
			@unlink( $tmp_file_name );
			return new WP_Error( 'import_file_error', __( 'Remote server did not respond', 'wordpress-importer' ) );
		}

		$filesize = (int) filesize( $tmp_file_name );

		if ( 0 === $filesize ) {
			@unlink( $tmp_file_name );
			return new WP_Error( 'import_file_error', __( 'Zero size file downloaded', 'wordpress-importer' ) );
		}

		if ( ! isset( $headers['content-encoding'] ) && isset( $headers['content-length'] ) && $filesize !== (int) $headers['content-length'] ) {
			@unlink( $tmp_file_name );
			return new WP_Error( 'import_file_error', __( 'Downloaded file has incorrect size', 'wordpress-importer' ) );
		}

		$max_size = 0; // FIXME: pick a number
		if ( ! empty( $max_size ) && $filesize > $max_size ) {
			@unlink( $tmp_file_name );
			return new WP_Error( 'import_file_error', sprintf( __( 'Remote file is too large, limit is %s', 'wordpress-importer' ), size_format( $max_size ) ) );
		}

		// Override file name with Content-Disposition header value.
		if ( ! empty( $headers['content-disposition'] ) ) {
			$file_name_from_disposition = self::get_filename_from_disposition( (array) $headers['content-disposition'] );
			if ( $file_name_from_disposition ) {
				$file_name = $file_name_from_disposition;
			}
		}

		// Set file extension if missing.
		$file_ext = pathinfo( $file_name, PATHINFO_EXTENSION );
		if ( ! $file_ext && ! empty( $headers['content-type'] ) ) {
			$extension = self::get_file_extension_by_mime_type( $headers['content-type'] );
			if ( $extension ) {
				$file_name = "{$file_name}.{$extension}";
			}
		}

		// Handle the upload like _wp_handle_upload() does.
		$wp_filetype     = wp_check_filetype_and_ext( $tmp_file_name, $file_name );
		$ext             = empty( $wp_filetype['ext'] ) ? '' : $wp_filetype['ext'];
		$type            = empty( $wp_filetype['type'] ) ? '' : $wp_filetype['type'];
		$proper_filename = empty( $wp_filetype['proper_filename'] ) ? '' : $wp_filetype['proper_filename'];

		// Check to see if wp_check_filetype_and_ext() determined the filename was incorrect.
		if ( $proper_filename ) {
			$file_name = $proper_filename;
		}

		if ( ( ! $type || ! $ext ) && ! current_user_can( 'unfiltered_upload' ) ) {
			return new WP_Error( 'import_file_error', __( 'Sorry, this file type is not permitted for security reasons.', 'wordpress-importer' ) );
		}

		$uploads = wp_upload_dir( $date );
		if ( ! ( $uploads && false === $uploads['error'] ) ) {
			return new WP_Error( 'upload_dir_error', $uploads['error'] );
		}

		// Move the file to the uploads dir.
		$file_name     = wp_unique_filename( $uploads['path'], $file_name );
		$new_file      = $uploads['path'] . "/$file_name";
		$move_new_file = copy( $tmp_file_name, $new_file );

		if ( ! $move_new_file ) {
			@unlink( $tmp_file_name );
			return new WP_Error( 'import_file_error', __( 'The uploaded file could not be moved', 'wordpress-importer' ) );
		}

		// Set correct file permissions.
		$stat  = stat( dirname( $new_file ) );
		$perms = $stat['mode'] & 0000666;
		chmod( $new_file, $perms );

		$upload = array(
			'file'  => $new_file,
			'url'   => $uploads['url'] . "/$file_name",
			'type'  => $wp_filetype['type'],
			'error' => false,
		);

		// keep track of the old and new urls so we can substitute them later
		$this->url_remap[ $url ]          = $upload['url'];
		// keep track of the destination if the remote url is redirected somewhere else
		if ( isset( $headers['x-final-location'] ) && $headers['x-final-location'] != $url ) {
			$this->url_remap[ $headers['x-final-location'] ] = $upload['url'];
		}

		return $upload;
	}

	// return the difference in length between two strings
	function cmpr_strlen( $a, $b ) {
		return strlen( $b ) - strlen( $a );
	}

	/**
	 * Parses filename from a Content-Disposition header value.
	 *
	 * As per RFC6266:
	 *
	 *     content-disposition = "Content-Disposition" ":"
	 *                            disposition-type *( ";" disposition-parm )
	 *
	 *     disposition-type    = "inline" | "attachment" | disp-ext-type
	 *                         ; case-insensitive
	 *     disp-ext-type       = token
	 *
	 *     disposition-parm    = filename-parm | disp-ext-parm
	 *
	 *     filename-parm       = "filename" "=" value
	 *                         | "filename*" "=" ext-value
	 *
	 *     disp-ext-parm       = token "=" value
	 *                         | ext-token "=" ext-value
	 *     ext-token           = <the characters in token, followed by "*">
	 *
	 * @since 0.7.0
	 *
	 * @see WP_REST_Attachments_Controller::get_filename_from_disposition()
	 *
	 * @link http://tools.ietf.org/html/rfc2388
	 * @link http://tools.ietf.org/html/rfc6266
	 *
	 * @param string[] $disposition_header List of Content-Disposition header values.
	 * @return string|null Filename if available, or null if not found.
	 */
	protected static function get_filename_from_disposition( $disposition_header ) {
		// Get the filename.
		$filename = null;

		foreach ( $disposition_header as $value ) {
			$value = trim( $value );

			if ( strpos( $value, ';' ) === false ) {
				continue;
			}

			list( $type, $attr_parts ) = explode( ';', $value, 2 );

			$attr_parts = explode( ';', $attr_parts );
			$attributes = array();

			foreach ( $attr_parts as $part ) {
				if ( strpos( $part, '=' ) === false ) {
					continue;
				}

				list( $key, $value ) = explode( '=', $part, 2 );

				$attributes[ trim( $key ) ] = trim( $value );
			}

			if ( empty( $attributes['filename'] ) ) {
				continue;
			}

			$filename = trim( $attributes['filename'] );

			// Unquote quoted filename, but after trimming.
			if ( substr( $filename, 0, 1 ) === '"' && substr( $filename, -1, 1 ) === '"' ) {
				$filename = substr( $filename, 1, -1 );
			}
		}

		return $filename;
	}

	/**
	 * Retrieves file extension by mime type.
	 *
	 * @since 0.7.0
	 *
	 * @param string $mime_type Mime type to search extension for.
	 * @return string|null File extension if available, or null if not found.
	 */
	protected static function get_file_extension_by_mime_type( $mime_type ) {
		static $map = null;

		if ( is_array( $map ) ) {
			return isset( $map[ $mime_type ] ) ? $map[ $mime_type ] : null;
		}

		$mime_types = wp_get_mime_types();
		$map        = array_flip( $mime_types );

		// Some types have multiple extensions, use only the first one.
		foreach ( $map as $type => $extensions ) {
			$map[ $type ] = strtok( $extensions, '|' );
		}

		return isset( $map[ $mime_type ] ) ? $map[ $mime_type ] : null;
	}
}
