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
		return implode( '', $flat );
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
			#var_dump( 'checking div with class', $node->getAttribute('class') ); flush(); ob_flush();
			$content = Block_Converter::get_node_html( $node );
			// Note that `self` would refer here to Block_Converter, not HTMLTransformer.
			if ( HTMLTransformer::node_has_class( $node, 'wp-block-columns' ) ) {
				// FIXME: this is incomplete and should go in a helper function so it can be reused for other block types.
				$atts = [];
				if ( $layout = HTMLTransformer::get_node_layout_name( $node ) ) {
					#var_dump( "got layout", $layout ); flush(); ob_flush();
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
				#var_dump( "core/columns", $atts, $content ); flush(); ob_flush();
				return new Block( 'core/columns', $atts, $content );
			}
			#var_dump( "default div" ); flush(); ob_flush();
			return Block_Converter::html( $node );
		} );
	}

	public function ___convert_div( \DOMNode $node ) {
		#flush();ob_flush();
		#var_dump( __METHOD__, memory_get_usage(), $node->getAttribute('class') );
		#var_dump( 'block converter macro div', $node->textContent, $node->getAttribute('class') );
		$content = Block_Converter::get_node_html( $node );
		if ( $this->node_has_class( $node, 'wp-block-columns' ) ) {
			// FIXME: this is incomplete and should go in a helper function so it can be reused for other block types.
			$atts = [];
			if ( $layout = $this->get_node_layout_name( $node ) ) {
				#var_dump( "got layout", $layout );
				$atts['layout'] = [ 'type' => $layout ];
			}
			if ( $this->node_has_class( $node, 'alignwide' ) ) {
				$atts['align'] = 'wide';
			}
			if ( $this->node_has_class( $node, 'alignfull' ) ) {
				$atts['align'] = 'full';
			}
			if ( $this->node_has_class( $node, 'has-medium-font-size' ) ) {
				$atts['fontSize'] = 'medium';
			}
			#var_dump( "core/columns", $atts, $content ); flush(); ob_flush();
			return new Block( 'core/columns', $atts, $content );
		}

		#var_dump( "div" ); flush(); ob_flush();
		return Block_Converter::p( $node );
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