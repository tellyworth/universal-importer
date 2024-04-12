<?php

use Alley\WP\Block_Converter\Block_Converter;

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

		$converter = new Block_Converter( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, $html, $blocks );
		$this->assertNotEmpty( $blocks );
	}

	public function test_sample_html2() {
		$html =<<<EOF
		<p class="is-style-short-text">The open source publishing platform of choice for millions of websites worldwide—from creators and small businesses to enterprises.</p>
EOF;

		$converter = new Block_Converter( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, $html, $blocks );
		$this->assertNotEmpty( $blocks );
	}

	public function test_div() {
		$html =<<<EOF
		<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex">
EOF;

		$converter = new Block_Converter( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, $html, $blocks );
		$this->assertNotEmpty( $blocks );
	}

}