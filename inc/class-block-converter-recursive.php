<?php

use Alley\WP\Block_Converter\Block_Converter;
use Alley\WP\Block_Converter\Block;

class Block_Converter_Recursive extends Block_Converter {
	/**
	 * Convert HTML to Gutenberg blocks, recursing to handle nested blocks.
	 *
	 * @return string The HTML.
	 */
	public function convert(): string {
		// Get tags from the html.
		$content = static::get_node_tag_from_html( $this->html );
		/*
		$dom = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $this->html );
		$content = $dom->childNodes;
		*/
		#var_dump( "content", $content ); flush(); ob_flush();

		// Bail early if is empty.
		if ( empty( $content ) ) {
			return '';
		}

		$html = [];
		foreach ( $content->item( 0 )->childNodes as $node ) {
			if ( '#text' === $node->nodeName ) {
				continue;
			}

			#var_dump( "content child node", $node->nodeName, $node->getAttribute('class') );
			$html[] = $this->convert_recursive( $content->item( 0 ) );

		}

		return implode( "\n\n", $html );


		foreach ( $content->childNodes as $node ) {
			if ( '#text' === $node->nodeName ) {
				continue;
			}

			#var_dump( "content child node", $node );
			/**
			 * Hook to allow output customizations.
			 *
			 * @since 1.0.0
			 *
			 * @param Block|null $block The generated block object.
			 * @param DOMNode   $node  The node being converted.
			 */
			$tag_block = apply_filters( 'wp_block_converter_block', $this->{$node->nodeName}( $node ), $node );


			// Bail early if is empty.
			if ( empty( $tag_block ) ) {
				continue;
			}


			// Merge the block into the HTML collection.


			$html[] = $this->minify_block( (string) $tag_block );
		}


		$html = implode( "\n\n", $html );


		// Remove empty blocks.
		$html = $this->remove_empty_blocks( $html );


		/**
		 * Content converted into blocks.
		 *
		 * @since 1.0.0
		 *
		 * @param string        $html    HTML converted into Gutenberg blocks.
		 * @param DOMNodeList $content The original DOMNodeList.
		 */
		return trim( (string) apply_filters( 'wp_block_converter_document_html', $html, $content ) );
	}

	public function convert_recursive( DOMNode $node ): string {

		// Depth-first recursion through child nodes.
		if ( $node->hasChildNodes() ) {
			$inner_html = [];
			foreach( $node->childNodes as $child_node ) {
				if ( '#text' === $child_node->nodeName ) {
					continue;
				}

				$inner_html[] = $this->convert_recursive( $child_node );
			}

			// FIXME: it would be much better to traverse the DOM rather than using inner HTML strings, but this will do for now.
			if ( ! empty( $inner_html ) ) {
				$this->set_inner_html( $node, implode( "\n", $inner_html ) );
			}
		}

		/**
		 * Hook to allow output customizations.
		 *
		 * @since 1.0.0
		 *
		 * @param Block|null $block The generated block object.
		 * @param DOMNode   $node  The node being converted.
		 */
		$tag_block = apply_filters( 'wp_block_converter_block', $this->{$node->nodeName}( $node ), $node );

		if ( empty( $tag_block ) ) {
			return '';
		}

		// Remove empty blocks.
		$html = $this->remove_empty_blocks( $tag_block );

		return $html;
	}

	/**
	 * Set the inner HTML property of a DOMNode.
	 */
	protected function set_inner_html( DOMNode $element, string $html)
	{
		if ( !$html ) {
			return;
		}
		$html = force_balance_tags( $html ); // FIXME: hack.
		#var_dump( "set inner html", $element->nodeName, $element->getAttribute('class'), $html );
		libxml_use_internal_errors(true);
		$fragment = $element->ownerDocument->createDocumentFragment();
		$fragment->appendXML($html);
		while ($element->hasChildNodes())
			$element->removeChild($element->firstChild);
		#var_dump( "fragment", $element->ownerDocument->saveHTML( $fragment ) );
		$element->appendChild($fragment);
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


	/**
	 * Handle some div blocks
	 */
	public function div( \DOMNode $node ) {
		#var_dump( 'div macro', get_called_class() ); flush(); ob_flush();
		#var_dump( 'checking div with class', $node->getAttribute('class') ); flush(); ob_flush();

		#$content = $node->textContent;
		#$content = null;

		$content = static::get_node_html( $node );
		if ( empty( $content ) ) {
			var_dump( "empty content" );
			return null;
		}

		// Note that `self` would refer here to Block_Converter, not HTMLTransformer.
		// FIXME: this is incomplete and should go in a helper function so it can be reused for other block types.
		$atts = [];
		if ( $layout = static::get_node_layout_name( $node ) ) {
			#var_dump( "got layout", $layout );
			$atts['layout'] = [ 'type' => $layout ];
		}
		if ( static::node_has_class( $node, 'alignwide' ) ) {
			$atts['align'] = 'wide';
		}
		if ( static::node_has_class( $node, 'alignfull' ) ) {
			$atts['align'] = 'full';
		}
		if ( static::node_has_class( $node, 'has-medium-font-size' ) ) {
			$atts['fontSize'] = 'medium';
		}
		if ( static::node_has_class( $node, 'wp-block-columns' ) ) {
			#var_dump( "core/columns", $atts, $node->nodeName ); flush(); ob_flush();
			return new Block( 'core/columns', $atts, $content );
		} elseif ( static::node_has_class( $node, 'wp-block-column' ) ) {
			#var_dump( "core/column", $atts, $node->nodeName ); flush(); ob_flush();
			return new Block( 'core/column', $atts, $content );
		}

		return null; // Default
	}

	/**
	 * A null handler for <body> tags, to work around an extraneous <body> tag in the DOM.
	 */
	public function body( \DOMNode $node ) {
		$content = [];
		foreach ( $node->childNodes as $child_node ) {
			$content[] = static::get_node_html( $child_node );
		}
		return implode( "\n", $content );;
	}

}