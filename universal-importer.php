<?php
/**
 * Plugin Name: Universal Importer (proof of concept)
 * Plugin URI: https://github.com/tellyworth/universal-importer
 * Description: Proof of Concept plugin for importing content from any source
 * Author: WordPress.org
 * Version: 0.0.1
 * Text Domain: universal-importer
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

 add_action( 'admin_init', function() {
	// autoload
	require_once __DIR__ . '/vendor/autoload.php';
	// Load the plugin
	require_once __DIR__ . '/inc/class-wp-universal-importer.php';
	$plugin = new WP_Universal_Importer();
 } );

