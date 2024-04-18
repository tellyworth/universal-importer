<?php

use Block_Converter_Recursive;
use Alley\WP\Block_Converter\Block;

// A class to transform segments of HTML into Gutenberg blocks.

class HTMLTransformer {

	private $layout_classes = [];

	/**
	 * @var HTMLTransformer
	 */
	private static $instance;

	/**
	 * @return HTMLTransformer
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * HTMLTransformer constructor.
	 */
	private function __construct() {
		// Add your hooks here
		$this->init();
	}

	private function flatten( \DOMNodeList $nodes ) {
		$flat = [];
		foreach ( $nodes as $node ) {
			$_flat = $node->ownerDocument->saveHTML( $node );
			$flat[] = $_flat;
		}
		return implode( "\n", $flat );
	}

	static function node_has_class( \DOMNode $node, $class ) {
		return in_array( $class, explode( ' ', $node->getAttribute( 'class' ) ) );
	}

	static function get_node_layout_name( \DOMNode $node ) {
		#var_dump( __METHOD__, $node->textContent, $node->getAttribute('class') );
		$layout_classes = [
			'is-layout-flow' => 'default',
			'is-layout-constrained' => 'constrained',
			'is-layout-flex' => 'flex',
			'is-layout-grid' => 'grid'
		];
		foreach ( $layout_classes as $class => $name ) {
			if ( self::node_has_class( $node, $class ) ) {
				#var_dump( __METHOD__, $class, $name );
				return $name;
			}
		}
		#var_dump( __METHOD__, "no layout found" );
		return false;
	}

	private function init() {
	}

	/**
	 * @param string $html
	 * @return Block[]
	 */
	public function transform( $html ) {
		if ( is_a( $html, '\DOMNodeList' ) ) {
			$html = $this->flatten( $html );
		}
		$converter = new Block_Converter_Recursive( $html );

		return $converter->convert();
	}
}