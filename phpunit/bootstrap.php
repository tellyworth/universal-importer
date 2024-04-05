<?php

// autoload
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
fwrite( STDERR, "stderr\n" );
// load WordPress if we're running within wp-now.
if ( false !== strpos( $_SERVER['_'], '@wp-now/wp-now' ) ) {
	require_once '/var/www/html/wp-load.php';
}

if ( !function_exists( 'mb_split' ) ) {
	function mb_split( $pattern, $string, $limit = -1 ) {
		return preg_split( "/$pattern/u", $string, $limit );
	}
}