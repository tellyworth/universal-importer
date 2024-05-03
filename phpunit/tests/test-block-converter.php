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
<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex"><!-- wp:column {"layout":{"type":"default"},"width":"50%"} --><div class="wp-block-column is-vertically-aligned-center is-layout-flow wp-block-column-is-layout-flow"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Meet WordPress</h1><!-- /wp:heading --><!-- wp:paragraph --><p class="is-style-short-text">The open source publishing platform of choice for millions of websites worldwide—from creators and small businesses to enterprises.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column {"layout":{"type":"default"},"width":"50%"} --><div class="wp-block-column is-vertically-aligned-center is-layout-flow wp-block-column-is-layout-flow"><!-- wp:image {"id":39758,"sizeSlug":"full"} --><figure class="wp-block-image size-full"><img src="https://wordpress.org/files/2024/04/brush.png" alt="" class="wp-image-39758"></figure><!-- /wp:image --></div><!-- /wp:column --></div>
<!-- /wp:columns -->
EOF;

		$converter = new Block_Converter_Recursive( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, 'input', $html, 'actual', $blocks );ob_flush();flush();
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

	public function test_fragment_bug() {
		$html =<<<EOF
		<div style="" class="grunion-field-text-wrap grunion-field-wrap">
		<label for="g4-contactname" class="grunion-field-label text">Contact Name<span class="grunion-label-required" aria-hidden="true">(required)</span></label>
		<input type="text" name="g4-contactname" id="g4-contactname" value="" class="text  grunion-field" required aria-required="true">
			</div>
EOF;

		// Should be left intact inside the html wrapper
		$expected = <<<EOF
<!-- wp:html -->
<div style="" class="grunion-field-text-wrap grunion-field-wrap"><label for="g4-contactname" class="grunion-field-label text"><span class="grunion-label-required" aria-hidden="true">(required)</span><input type="text" name="g4-contactname" id="g4-contactname" value="" class="text  grunion-field" required aria-required="true"></label></div>
<!-- /wp:html -->
EOF;

		$converter = new Block_Converter_Recursive( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, $html, $blocks );ob_flush();flush();
		$this->assertEquals( $expected, $blocks );

	}

	public function test_latest_posts_block() {
		$html =<<<EOF
		<ul class="wp-block-latest-posts__list has-dates has-author wp-block-latest-posts"><li><a class="wp-block-latest-posts__post-title" href="http://localhost:8881/2024-sponsor_level-global/">Global – WordCamp Sydney 2024</a><div class="wp-block-latest-posts__post-author">by admin</div><time datetime="2024-04-24T00:58:42+00:00" class="wp-block-latest-posts__post-date">April 24, 2024</time><div class="wp-block-latest-posts__post-excerpt">WPBeginner WPBeginnerfree WordPress video courses Whether you’re looking to learn how to build a WordPress website, decide which WordPress plugins to pick, or just learn the WordPress best practices to grow your website, WPBeginner’s free resources can help: WPBeginner Blog WPBeginner Solution Center WPBeginner Dictionary WPBeginner VideosWordPress 101WordPress SEO for Beginners WPBeginner Deals WPBeginner Tools… <a href="http://localhost:8881/2024-sponsor_level-global/" rel="noopener noreferrer">Read more<span class="screen-reader-text">: Global – WordCamp Sydney 2024</span></a></div></li>
			<li><a class="wp-block-latest-posts__post-title" href="http://localhost:8881/2024-sponsor_level-event-sponsor/">Event Sponsor – WordCamp Sydney 2024</a><div class="wp-block-latest-posts__post-author">by admin</div><time datetime="2024-04-24T00:58:40+00:00" class="wp-block-latest-posts__post-date">April 24, 2024</time><div class="wp-block-latest-posts__post-excerpt">Linux Australia Linux AustraliaEverything Open</div></li>
			<li><a class="wp-block-latest-posts__post-title" href="http://localhost:8881/2024-category-sponsorship/">Sponsorship – WordCamp Sydney 2024</a><div class="wp-block-latest-posts__post-author">by admin</div><time datetime="2024-04-24T00:58:38+00:00" class="wp-block-latest-posts__post-date">April 24, 2024</time><div class="wp-block-latest-posts__post-excerpt">Posted on23 April 202423 April 2024 Call for Sponsors</div></li>
			<li><a class="wp-block-latest-posts__post-title" href="http://localhost:8881/2024-category-uncategorized/">Uncategorized – WordCamp Sydney 2024</a><div class="wp-block-latest-posts__post-author">by admin</div><time datetime="2024-04-24T00:58:36+00:00" class="wp-block-latest-posts__post-date">April 24, 2024</time><div class="wp-block-latest-posts__post-excerpt">Posted on10 April 202416 April 2024 Welcome to WordCamp Sydney, NSW, Australia We’re happy to announce that WordCamp Sydney is officially on the calendar! WordCamp Sydney will be held from 2 to 3 November 2024 at the University of Technology Sydney (UTS). Subscribe to email updates in the sidebar and stay updated on recent news.… <a href="http://localhost:8881/2024-category-uncategorized/" rel="noopener noreferrer">Read more<span class="screen-reader-text">: Uncategorized – WordCamp Sydney 2024</span></a></div></li>
			<li><a class="wp-block-latest-posts__post-title" href="http://localhost:8881/2024-blog/">WordCamp Sydney 2024</a><div class="wp-block-latest-posts__post-author">by admin</div><time datetime="2024-04-24T00:57:57+00:00" class="wp-block-latest-posts__post-date">April 24, 2024</time><div class="wp-block-latest-posts__post-excerpt">Posted on23 April 202423 April 2024 Call for Sponsors Posted on10 April 202416 April 2024 Welcome to WordCamp Sydney, NSW, Australia We’re happy to announce that WordCamp Sydney is officially on the calendar! WordCamp Sydney will be held from 2 to 3 November 2024 at the University of Technology Sydney (UTS). Subscribe to email updates… <a href="http://localhost:8881/2024-blog/" rel="noopener noreferrer">Read more<span class="screen-reader-text">: WordCamp Sydney 2024</span></a></div></li>
			<li><a class="wp-block-latest-posts__post-title" href="http://localhost:8881/2024-call-for-sponsors/">Call for Sponsors</a><div class="wp-block-latest-posts__post-author">by admin</div><time datetime="2024-04-24T00:57:35+00:00" class="wp-block-latest-posts__post-date">April 24, 2024</time><div class="wp-block-latest-posts__post-excerpt">Posted on23 April 202423 April 2024DeveloperWil Call for Sponsors</div></li>
			<li><a class="wp-block-latest-posts__post-title" href="http://localhost:8881/2024-welcome-to-wordcamp-sydney-nsw-australia/">Welcome to WordCamp Sydney, NSW, Australia</a><div class="wp-block-latest-posts__post-author">by admin</div><time datetime="2024-04-24T00:57:33+00:00" class="wp-block-latest-posts__post-date">April 24, 2024</time><div class="wp-block-latest-posts__post-excerpt">Posted on10 April 202416 April 2024DeveloperWil Welcome to WordCamp Sydney, NSW, Australia</div></li>
			<li><a class="wp-block-latest-posts__post-title" href="http://localhost:8881/hello-world/">Hello world!</a><div class="wp-block-latest-posts__post-author">by admin</div><time datetime="2024-04-24T00:53:06+00:00" class="wp-block-latest-posts__post-date">April 24, 2024</time><div class="wp-block-latest-posts__post-excerpt">Welcome to WordPress. This is your first post. Edit or delete it, then start writing!</div></li>
		</ul>
EOF;

		// All of that should collapse to a single block. The inner markup determines the attributes.
		// <!-- wp:latest-posts {"postsToShow":8,"displayPostContent":true,"displayAuthor":true,"displayPostDate":true,"displayFeaturedImage":true,"addLinkToFeaturedImage":true} /-->

		$expected =<<<EOF
<!-- wp:latest-posts {"displayPostDate":true,"displayAuthor":true} /-->
EOF;

		$converter = new Block_Converter_Recursive( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, $html, $blocks );ob_flush();flush();
		$this->assertEquals( $expected, $blocks );
	}

	public function test_query_loop() {
		$html = <<<EOF

		<div class="wp-block-query is-layout-flow wp-block-query-is-layout-flow"><ul class="wp-block-post-template is-layout-flow wp-block-post-template-is-layout-flow"><li class="wp-block-post post-10 post type-post status-publish format-standard hentry category-uncategorized">

		<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex">
		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:66.66%"></div>



		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:33.33%"><h2 class="wp-block-post-title"><a href="https://playground.wordpress.net/scope:0.5199335684158969/?p=10" target="_self">Second Post</a></h2>

		<div class="wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt">second post excerpy </p></div></div>
		</div>

		</li><li class="wp-block-post post-8 post type-post status-publish format-standard hentry category-uncategorized">

		<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-2 wp-block-columns-is-layout-flex">
		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:66.66%"></div>



		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:33.33%"><h2 class="wp-block-post-title"><a href="https://playground.wordpress.net/scope:0.5199335684158969/?p=8" target="_self">First Post</a></h2>

		<div class="wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt">first post excerpt </p></div></div>
		</div>

		</li><li class="wp-block-post post-5 post type-post status-publish format-standard hentry category-uncategorized">

		<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-3 wp-block-columns-is-layout-flex">
		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:66.66%"></div>



		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:33.33%">

		<div class="wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt"> </p></div></div>
		</div>

		</li><li class="wp-block-post post-1 post type-post status-publish format-standard hentry category-uncategorized">

		<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-4 wp-block-columns-is-layout-flex">
		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:66.66%"></div>



		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow" style="flex-basis:33.33%"><h2 class="wp-block-post-title"><a href="https://playground.wordpress.net/scope:0.5199335684158969/?p=1" target="_self">Hello world!</a></h2>

		<div class="wp-block-post-excerpt"><p class="wp-block-post-excerpt__excerpt">Welcome to WordPress. This is your first post. Edit or delete it, then start writing! </p></div></div>
		</div>

		</li></ul></div>

EOF;

		// This is the exact block code from the editor
		$original = <<<EOF
<!-- wp:query {"queryId":13,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:post-featured-image {"isLink":true} /--></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:post-title {"isLink":true} /-->

<!-- wp:post-excerpt /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
EOF;

		// This is our expected approximation. It's structurally the same, but some attributes don't make it through.
		$expected = <<<EOF
<!-- wp:query {"layout":{"type":"default"},"query":{"perPage":"4","postType":"post"}} -->
<div class="wp-block-query is-layout-flow wp-block-query-is-layout-flow"><!-- wp:post-template --><!-- wp:columns {"layout":{"type":"flex"},"align":"wide"} --><div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-1 wp-block-columns-is-layout-flex"><!-- wp:column {"layout":{"type":"default"},"width":"66.66%"} --><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"></div><!-- /wp:column --><!-- wp:column {"layout":{"type":"default"},"width":"33.33%"} --><div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><!-- wp:post-title {"level":2,"isLink":true} /--><!-- wp:post-excerpt /--></div><!-- /wp:column --></div><!-- /wp:columns --><!-- /wp:post-template --></div>
<!-- /wp:query -->
EOF;

		$converter = new Block_Converter_Recursive( $html );

		$blocks = $converter->convert();
		#var_dump( __METHOD__, $html, $blocks );ob_flush();flush();
		$this->assertEquals( $expected, $blocks );

	}
}