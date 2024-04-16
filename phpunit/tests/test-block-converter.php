<?php

require_once( dirname( dirname( __DIR__ ) ) . '/inc/class-block-converter-recursive.php' );

class TestBlockConverter extends \PHPUnit\Framework\TestCase {

	public function test_sample_html() {
		$html =<<<EOF

		<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex">
		<div class="wp-block-column is-vertically-aligned-center is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:50%">
		<h1 class="wp-block-heading" style="font-size:70px">Meet WordPress</h1>



		<p class="is-style-short-text">The open source publishing platform of choice for millions of websites worldwide—from creators and small businesses to enterprises.</p>
		</div>



		<div class="wp-block-column is-vertically-aligned-center is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:50%">
		<figure class="wp-block-image size-full"><img width="1198" height="323" src="https://wordpress.org/files/2024/04/brush.png" alt="" class="wp-image-39758" srcset="https://i0.wp.com/wordpress.org/files/2024/04/brush.png?w=1198&amp;ssl=1 1198w, https://i0.wp.com/wordpress.org/files/2024/04/brush.png?resize=300%2C81&amp;ssl=1 300w, https://i0.wp.com/wordpress.org/files/2024/04/brush.png?resize=1024%2C276&amp;ssl=1 1024w, https://i0.wp.com/wordpress.org/files/2024/04/brush.png?resize=768%2C207&amp;ssl=1 768w" sizes="(max-width: 1198px) 100vw, 1198px"></figure>
		</div>
		</div>
EOF;

// Here's the exact expected output, if we could do it 100%:
/*
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"top":"30px","left":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"70px"}}} -->
<h1 class="wp-block-heading" style="font-size:70px">Meet WordPress</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"is-style-short-text"} -->
<p class="is-style-short-text">The open source publishing platform of choice for millions of websites worldwide—from creators and small businesses to enterprises.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:image {"id":39758,"sizeSlug":"full","linkDestination":"none"} -->
<figure class="wp-block-image size-full"><img src="https://wordpress.org/files/2024/04/brush.png" alt="" class="wp-image-39758" /></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
 */

		// And here's what it currently outputs.
		// Note that there are whitespace differences, but functionally this is very similar minus some styling attributes.
		// Importantly, it manages to produce the correct block types including columns/column and the image block.
		$expected = <<<EOF
<!-- wp:columns {"layout":{"type":"flex"},"align":"wide"} -->
<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex"><!-- wp:column {"layout":{"type":"default"}} --><div class="wp-block-column is-vertically-aligned-center is-layout-flow wp-block-column-is-layout-flow"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Meet WordPress</h1><!-- /wp:heading -->
<!-- wp:paragraph --><p class="is-style-short-text">The open source publishing platform of choice for millions of websites worldwide—from creators and small businesses to enterprises.</p><!-- /wp:paragraph --></div><!-- /wp:column -->
<!-- wp:column {"layout":{"type":"default"}} --><div class="wp-block-column is-vertically-aligned-center is-layout-flow wp-block-column-is-layout-flow"><!-- wp:image {"id":39758,"sizeSlug":"full"} --><figure class="wp-block-image size-full"><img src="https://wordpress.org/files/2024/04/brush.png" alt="" class="wp-image-39758"></figure><!-- /wp:image --></div><!-- /wp:column --></div>
<!-- /wp:columns -->
EOF;

		$converter = new Block_Converter_Recursive( $html );

		$blocks = $converter->convert();
		var_dump( __METHOD__, 'input', $html, 'actual', $blocks );ob_flush();flush();
		$this->assertEquals( $expected, $blocks );
	}

	public function test_sample_html2() {
		$html =<<<EOF
		<p class="is-style-short-text">The open source publishing platform of choice for millions of websites worldwide—from creators and small businesses to enterprises.</p>
EOF;

		// FIXME: the class should correspond with a block attribute probably
		$expected = <<<EOF
<!-- wp:paragraph -->
<p class="is-style-short-text">The open source publishing platform of choice for millions of websites worldwide—from creators and small businesses to enterprises.</p>
<!-- /wp:paragraph -->
EOF;

		$converter = new Block_Converter_Recursive( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, $html, $blocks );ob_flush();flush();
		$this->assertEquals( $expected, $blocks );
	}

	public function test_div() {
		$html =<<<EOF
		<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex">
EOF;

		// FIXME: confirm if end div should be added
		$expected =<<<EOF
<!-- wp:columns {"layout":{"type":"flex"},"align":"wide"} -->
<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex"></div>
<!-- /wp:columns -->
EOF;

		$converter = new Block_Converter_Recursive( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, $html, $blocks );ob_flush();flush();
		$this->assertEquals( $expected, $blocks );
	}

}