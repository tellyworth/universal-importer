<?php

use Alley\WP\Block_Converter\Block_Converter;
use Alley\WP\Block_Converter\Block;

if ( function_exists( 'add_filter' ) ) {
	// wp_http_validate_url() seems buggy or misguided when running on localhost.
	// Adding this filter in the block converter class because the base class uses WP functions to fetch remote images.
	add_filter( 'http_request_host_is_external', function( $external, $host, $url ) {
		$site_host = strtolower( parse_url( get_site_url(), PHP_URL_HOST ) );
		return $site_host !== strtolower( $host );
	}, 10, 3 );
}

class Block_Converter_Recursive extends Block_Converter {

	/**
	 * Temporary/testing: track unknown block types.
	 */
	public $unhandled_blocks = [];

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

			$str_inner_html = implode( "\n", $inner_html );

			// FIXME: it would be much better to traverse the DOM rather than using inner HTML strings, but this will do for now.
			if ( $str_inner_html ) {
				$this->set_inner_html( $node, $str_inner_html );
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
	 * This is crafted to work reasonably well with sloppy HTML.
	 */
	protected function set_inner_html( DOMNode $element, string $html ) {
		if ( !$html ) {
			return;
		}
		$DOM_inner_HTML = new DOMDocument();
		$internal_errors = libxml_use_internal_errors( true );
		$html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );

		// Load the HTML into a bare document
		$DOM_inner_HTML->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOENT );
		libxml_use_internal_errors( $internal_errors );

		// Remove all of the existing inner content
		while ( $element->hasChildNodes() ) {
			$element->removeChild( $element->firstChild );
		}

		// Append the new content one node at a time
		while ( $content_node = $DOM_inner_HTML->firstChild ) {
			$_content_node = $element->ownerDocument->importNode( $content_node, true );
			$element->appendChild( $_content_node );
			$DOM_inner_HTML->removeChild( $content_node );
		}

	}


	static function node_has_class( \DOMNode $node, $class ) {
		if ( !method_exists( $node, 'getAttribute' ) ) {
			return false;
		}
		return in_array( $class, explode( ' ', $node->getAttribute( 'class' ) ) );
	}

	static function node_matches_class( \DOMNode $node, $class_prefix ) {
		if ( !method_exists( $node, 'getAttribute' ) ) {
			return false;
		}
		$classes = explode( ' ', $node->getAttribute( 'class' ) );
		foreach ( $classes as $class ) {
			if ( str_starts_with( $class, $class_prefix ) ) {
				return $class;
			}
		}

		return false;
	}

	static function node_ancestor_has_class( \DOMNode $node, $class ) {
		$parent = $node->parentNode;
		while ( $parent ) {
			if ( self::node_has_class( $parent, $class ) ) {
				return $parent;
			}
			$parent = $parent->parentNode;
		}
		return false;
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
			$node->removeAttribute('style');
			$content = static::get_node_html( $node );
			#var_dump( "core/columns", $atts, $node->nodeName ); flush(); ob_flush();
			$block = new Block( 'core/columns', $atts, $content );
			#var_dump( $block );
			return $block;
		} elseif ( static::node_has_class( $node, 'wp-block-column' ) ) {
			$node->removeAttribute('style');
			$content = static::get_node_html( $node );
			#var_dump( "core/column", $atts, $node->nodeName ); flush(); ob_flush();
			$block = new Block( 'core/column', $atts, $content );
			#var_dump( $block );
			return $block;
		} elseif ( static::node_has_class( $node, 'wp-block-buttons' ) ) {
			$node->removeAttribute('style');
			$content = static::get_node_html( $node );
			// Basically just a wrapper div for individual button blocks
			$block = new Block( 'core/buttons', $atts, $content );
			return $block;
		} elseif ( static::node_has_class( $node, 'wp-block-button') || static::node_matches_class( $node, 'wp-block-button__') ) {
			$node->removeAttribute('style');
			$content = static::get_node_html( $node );
			// Should contain a single <a> tag; does that need processing?
			$block = new Block( 'core/button', $atts, $content );
			return $block;
		} elseif ( static::node_has_class( $node, 'wp-block-spacer') ) {
			// Ignore the inner content entirely.
			$block = new Block( 'core/spacer', $atts, '' );
			return $block;
		} elseif ( static::node_has_class( $node, 'wp-block-group') ) {
			$node->removeAttribute('style');
			$content = static::get_node_html( $node );
			// Group blocks seem to behave inconsistently depending on style. Sometimes the editor seems to replace them with something else like rows.
			$block = new Block( 'core/group', $atts, $content );
			return $block;
		} elseif ( static::node_matches_class( $node, 'wp-block-jetpack-' ) ) {
			// Jetpack form/button; bypass for now.
			return new Block( null, [], static::get_node_html( $node ) );
		} elseif ( static::node_matches_class( $node, 'wp-block-wordcamp-' ) ) {
			// WordCamp blocks; bypass for now.
			return new Block( null, [], static::get_node_html( $node ) );
		} elseif ( static::node_has_class( $node, 'wp-block-query' ) ) {
			// Query block! This one requires us to look at the inner markup.
			$node->removeAttribute('style');
			$query_atts = [];
			$xpath = new DOMXPath( $node->ownerDocument );
			if ( $querypost = $xpath->query( 'contains(@class, "wp-block-post")', $node ) ) {
				if ( $querypost->count() ) {
					$query_atts['perPage'] = $querypost->count();
					if ( $type = self::node_matches_class( $querypost->item(0), 'type-' ) ) {
						$query_atts['postType'] = str_replace( 'type-', '', $type );
					}
					$atts['query'] = $query_atts;
					// Inner content is a template, but we have a list of multiple instances.
					// So we want to delete all but one, and let the remaining one be the template.
					for ( $i = 1; $i < $querypost->count(); $i++ ) {
						$querypost->item( $i )->parentNode->removeChild( $querypost->item( $i ) );
					}
				}
			}
			return new Block( 'core/query', $atts, static::get_node_html( $node ) );
		} elseif ( static::node_has_class( $node, 'wp-block-post-content') ) {
			// If we're within a query block, this is a template block; ignore the inner content entirely.
			if ( static::node_ancestor_has_class( $node, 'wp-block-query' ) ) {
				return new Block( 'core/post-content', [], '' );
			}
			// Otherwise just leave this as a div.
			return new Block( null, [], static::get_node_html( $node ) );
		} elseif ( static::node_has_class( $node, 'wp-block-post-date') ) {
			// Template block; ignore the inner content entirely.
			return new Block( 'core/post-date', [], '' );
		} elseif ( static::node_has_class( $node, 'wp-block-post-excerpt') ) {
			// Template block; ignore the inner content entirely.
			return new Block( 'core/post-excerpt', [], '' );
		} elseif ( static::node_has_class( $node, 'wp-block-post-author-name') ) {
			// Template block; ignore the inner content entirely.
			return new Block( 'core/post-author-name', [], '' );
		} elseif ( static::node_has_class( $node, 'wp-block-post-terms') ) {
			// Template block; ignore the inner content entirely.
			return new Block( 'core/post-terms', [], '' );
		} elseif ( static::node_has_class( $node, 'wp-block-template-part') ) {
			// Treat this like a group block for now.
			$content = static::get_node_html( $node );
			return new Block( 'core/template-part', $atts, $content );
		} elseif ( $_class = static::node_matches_class( $node, 'wp-block-latest-posts__' ) ) {
			// Set some additional classes on the parent latest-posts block so as to preserve info about what the attributes should be.
			$inner_class = str_replace( 'wp-block-latest-posts__', '', $_class );
			if ( $_parent = static::node_ancestor_has_class( $node, 'wp-block-latest-posts' ) ) {
				$_parent->setAttribute( 'class', $_parent->getAttribute( 'class' ) . ' has-' . $inner_class );
			}
			// implicit inner block will be generated by the latest-posts parent block I think, so we can remove this entirely.
			return new Block( null, [], '' );
		}

		// Default should leave the HTML as-is.
		#return static::get_node_html( $node );
		return self::html( $node );
	}

	protected function li( \DOMNode $node ) {
		if ( static::node_has_class( $node, 'wp-block-post' ) ) {
			$node->removeAttribute('style');
			$content = static::get_node_html( $node );
			$block = new Block( 'core/post-template', [], $content );
			return $block;
		}

		return self::html( $node );
	}

	protected function button( \DOMNode $node ) {
		if ( static::node_matches_class( $node, 'wp-block-button' ) ) {
			if ( static::node_matches_class( $node->parentNode, 'wp-block-jetpack-button' ) ) {
				// FIXME: bypass Jetpack forms for now.
				return new Block( null, [], static::get_node_html( $node ) );
			}
		}
		return self::html( $node );
	}

	protected function form ( \DOMNode $node ) {
		if ( static::node_matches_class( $node, 'wp-block-jetpack-' ) ) {
				// FIXME: bypass Jetpack forms for now.
				return new Block( null, [], static::get_node_html( $node ) );
		}
		return self::html( $node );
	}

	/**
	 * A null handler for <body> tags, to work around an extraneous <body> tag in the DOM.
	 */
	public function body( \DOMNode $node ) {
		$content = [];
		foreach ( $node->childNodes as $child_node ) {
			$content[] = static::get_node_html( $child_node );
		}
		return implode( "\n", $content );
	}

	protected function h( \DOMNode $node ): ?Block {
		// A post-title block is a template; we don't want any of the inner content.
		if ( static::node_has_class( $node, 'wp-block-post-title' ) ) {
			// eg <!-- wp:post-title {"level":3,"isLink":true} /-->
			$atts = [
				'level' => absint( str_replace( 'h', '', $node->nodeName ) )
			];
			if ( $node->hasChildNodes() && 'a' === $node->firstChild->nodeName ) {
				$atts['isLink'] = true;
			}
			return new Block( 'core/post-title', $atts, '' );
		}

		// Remove style attributes to prevent block validation errors.
		$node->removeAttribute( 'style' );
		return parent::h( $node );
	}

	protected function time( \DOMNode $node ): Block {
		// Implicit block generated by a latest-posts block; we don't want any of the inner content.
		if ( static::node_matches_class( $node, 'wp-block-latest-posts__' ) ) {
			return new Block( null, [], '' );
		}

		// Remove style attributes to prevent block validation errors.
		$node->removeAttribute( 'style' );
		return parent::time( $node );
	}

	protected function figure( \DOMNode $node ): ?Block {

		// A post-featured-image block is a template; we don't want any of the inner content.
		if ( static::node_has_class( $node, 'wp-block-post-featured-image' ) ) {
			return new Block( 'core/post-featured-image', [], '' );
		}

		// Must contain an img tag.
		if ( ! $node->hasChildNodes() ) {
			return null;
		}
		if ( 'img' !== $node->firstChild->nodeName ) {
			return null;
		}

		// Must also be a block
		if ( ! static::node_has_class( $node, 'wp-block-image' ) ) {
			return null;
		}

		// Remove style attributes to prevent block validation errors.
		$node->removeAttribute( 'style' );
		$img = $node->firstChild;
		// Find the img ID if set
		$img_id = null;
		if ( preg_match( '/wp-image-(\d+)/', $img->getAttribute( 'class' ), $matches ) ) {
			$img_id = intval( $matches[1] );
		}
		// Remove most of the img attributes since they'll be handled by the block.
		$img->removeAttribute( 'style' );
		$img->removeAttribute( 'width' );
		$img->removeAttribute( 'height' );
		$img->removeAttribute( 'sizes' );
		$img->removeAttribute( 'srcset' );

		$atts = [];
		if ( $img_id ) {
			$atts['id'] = $img_id;
		}
		if ( static::node_has_class( $node, 'size-full' ) ) {
			$atts['sizeSlug'] = 'full';
		} elseif ( static::node_has_class( $node, 'size-large' ) ) {
			$atts['sizeSlug'] = 'large';
		} elseif ( static::node_has_class( $node, 'size-medium' ) ) {
			$atts['sizeSlug'] = 'medium';
		} elseif ( static::node_has_class( $node, 'size-thumbnail' ) ) {
			$atts['sizeSlug'] = 'thumbnail';
		}
		// FIXME: handle linkDestination if <a> tag is present?

		$content = static::get_node_html( $node );
		$block = new Block( 'core/image', $atts, $content );
		return $block;
	}

	// Parent class uses p for many inline blocks, so we need to override it.
	function p( \DOMNode $node ): ?Block {
		if ( 'p' === $node->nodeName ) {
			return parent::p( $node );
		}

		// Default should leave the HTML as-is.
		return new Block( null, [], static::get_node_html( $node ) );
	}

	function ul( \DOMNode $node ): Block {
		// A latest-posts block is similar to a template: we don't want any of the inner content.
		if ( static::node_has_class( $node, 'wp-block-latest-posts__list' ) ) {
			$atts = [];
			if ( self::node_has_class( $node, 'is-grid' ) ) {
				$atts['postLayout'] = 'grid';
			}
			// FIXME: share this code with div so we get other alignments etc.
			if ( static::node_has_class( $node, 'alignfull' ) ) {
				$atts['align'] = 'full';
			}
			// <!-- wp:latest-posts {"postsToShow":8,"displayPostContent":true,"displayAuthor":true,"displayPostDate":true,"displayFeaturedImage":true,"addLinkToFeaturedImage":true} /-->
			if ( $node->childNodes->length > 1 ) {
				$atts['postsToShow'] = $node->childNodes->length;
			}
			// It's a shame this seems to be the only block attribute that shows up in a class.
			if ( static::node_has_class( $node, 'has-dates' ) ) {
				$atts['displayPostDate'] = true;
			}
			// These classes were added in div() above.
			if ( static::node_has_class( $node, 'has-post-author' ) ) {
				$atts['displayAuthor'] = true;
			}
			if ( static::node_has_class( $node, 'has-featured-image' ) ) {
				$atts['displayFeaturedImage'] = true;
			}
			if ( static::node_has_class( $node, 'has-post-content' ) ) {
				$atts['displayPostContent'] = true;
			}

			return new Block( 'core/latest-posts', $atts, '' );
		}

		// Default should leave the HTML as-is.
		return parent::ul( $node );
	}

	function html( \DOMNode $node ): ?Block {
		#var_dump( "html", $node->nodeName );

		$ignore = [
			'a',        // Anchor element
			'abbr',     // Abbreviation
			'b',        // Bold text
			'bdi',      // Bi-directional Isolation
			'bdo',      // Bi-directional Override
			'br',       // Line Break
			'cite',     // Citation
			'code',     // Code element
			'data',     // Add machine-readable translation
			'dfn',      // Definition element
			'em',       // Emphasis
			'i',        // Italic
			'img',      // Image
			'input',    // Input field
			'kbd',      // Keyboard input
			'label',    // Label for a form element
			'li',       // List item
			'mark',     // Marked text
			'q',        // Inline quotation
			'rp',       // For ruby annotations (fallback parentheses)
			'rt',       // Ruby text
			'rtc',      // Ruby text container
			'ruby',     // Ruby annotation
			's',        // Strikethrough text
			'samp',     // Sample output from a computer program
			'small',    // Small text
			'span',     // Generic inline container
			'strong',   // Strong importance
			'sub',      // Subscript
			'sup',      // Superscript
			'time',     // Date/time
			'u',        // Underline
			'var',      // Variable
			'wbr'       // Word break opportunity
		];

		if ( $block_type = self::node_matches_class( $node, 'wp-block-' ) ) {
			$this->unhandled_blocks[ $block_type ] = $node;
			#var_dump( static::get_node_html( $node ) );
			trigger_error( "Unhandled block type: <$node->nodeName> $block_type", E_USER_WARNING );
		}

		if ( in_array( $node->nodeName, $ignore ) ) {
			#return static::get_node_html( $node );
			return new Block( null, [], static::get_node_html( $node ) );
		}


		// Default should leave the HTML as-is.
		return parent::html( $node );
	}

}