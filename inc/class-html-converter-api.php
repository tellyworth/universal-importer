<?php

// WordPress REST API endpoint for converting HTML to blocks

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once( dirname( __FILE__ ) . '/class-html-transformer.php' );

// FIXME: should be a class
register_rest_route( 'html-converter/v1', '/convert', array(
	'methods'  => 'POST',
	'callback' => 'convert_html_to_blocks',
) );

function convert_html_to_blocks( $request ) {
	// Retrieve the HTML content from the request
	$html = $request->get_param( 'html' );

	if ( $html ) {
		$html_transformer = HTMLTransformer::instance();
		$blocks = $html_transformer->transform( $html );

		// Return the converted blocks
		return array(
			'blocks' => $blocks,
		);
	}
}