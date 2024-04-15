<?php

use Alley\WP\Block_Converter\Block_Converter;
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
		/*if ( false && function_exists( 'gutenberg_get_layout_definitions' ) ) {
			if ( $layouts = \gutenberg_get_layout_definitions() ) {
				foreach ( $layouts as $name => $layout ) {
					$this->layout_classes[ $layout['className'] ] = $name;
				}
			}
		} else {*/
		//}

		#Block_Converter::macro( 'div', array( $this, 'convert_div') );
		Block_Converter::macro( 'div', function( \DOMNode $node ) {
			#var_dump( 'div macro', get_called_class() ); flush(); ob_flush();
			var_dump( 'checking div with class', $node->getAttribute('class') ); flush(); ob_flush();
			$content = Block_Converter::get_node_html( $node );

			#$content = $node->textContent;
			#$content = null;
			// Note that `self` would refer here to Block_Converter, not HTMLTransformer.
			// FIXME: this is incomplete and should go in a helper function so it can be reused for other block types.
			$atts = [];
			if ( $layout = HTMLTransformer::get_node_layout_name( $node ) ) {
				#var_dump( "got layout", $layout );
				$atts['layout'] = [ 'type' => $layout ];
			}
			if ( HTMLTransformer::node_has_class( $node, 'alignwide' ) ) {
				$atts['align'] = 'wide';
			}
			if ( HTMLTransformer::node_has_class( $node, 'alignfull' ) ) {
				$atts['align'] = 'full';
			}
			if ( HTMLTransformer::node_has_class( $node, 'has-medium-font-size' ) ) {
				$atts['fontSize'] = 'medium';
			}
			if ( HTMLTransformer::node_has_class( $node, 'wp-block-columns' ) ) {
				var_dump( "core/columns", $atts, $content ); flush(); ob_flush();
				return new Block( 'core/columns', $atts, $content );
			} elseif ( HTMLTransformer::node_has_class( $node, 'wp-block-column' ) ) {
				var_dump( "core/column", $atts, $content ); flush(); ob_flush();
				return new Block( 'core/column', $atts, $content );
			}

			var_dump( "default div" ); flush(); ob_flush();
			// FIXME: this should be a div; need to double check it's not causing an infinite loop like early bugs.
			return Block_Converter::html( $node );
		} );
	}

	/**
	 * @param string $html
	 * @return Block[]
	 */
	public function transform( $html ) {
		if ( is_a( $html, '\DOMNodeList' ) ) {
			$html = $this->flatten( $html );
		}
		$converter = new Block_Converter( $html );

		return $converter->convert();
	}
}