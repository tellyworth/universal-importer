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

	public function test_chrome_store_buttons() {
		// Steve's example, a section from the chrome web store
		$html = <<<EOF
<h2 class="WAxuqb">Top categories</h2><div class="BjaEYd"><a href="./category/extensions/lifestyle/shopping" class="ScFtwd" style="--tc-bg-color:#d3e3fd;--tc-icon-bg-color:#a8c7fa;--tc-icon-color:#000;--tc-hover-bg-color:#a8c7fa;--tc-hover-icon-bg-color:#7cacf8;--tc-focus-bg-color:#7cacf8;--tc-focus-icon-bg-color:#4c8df6;" jslog="147877; metadata:W251bGwsbnVsbCxbOV1d; track:click,keyboard_enter" data-focusid="363"><span class="qa482c">Shopping</span><span class="eBC1oe"><svg enable-background="new 0 0 24 24" height="20" viewBox="0 0 24 24" width="20" focusable="false" class=" NMm5M"><g><path d="M18,6h-2c0-2.21-1.79-4-4-4S8,3.79,8,6H6C4.9,6,4,6.9,4,8v12c0,1.1,0.9,2,2,2h12c1.1,0,2-0.9,2-2V8C20,6.9,19.1,6,18,6z M12,4c1.1,0,2,0.9,2,2h-4C10,4.9,10.9,4,12,4z M18,20H6V8h2v2c0,0.55,0.45,1,1,1s1-0.45,1-1V8h4v2c0,0.55,0.45,1,1,1s1-0.45,1-1V8 h2V20z"></path><rect fill="none" height="24" width="24"></rect></g></svg></span></a><a href="./category/extensions/lifestyle/entertainment" class="ScFtwd" style="--tc-bg-color:#e3e3e3;--tc-icon-bg-color:#c7c7c7;--tc-icon-color:#2d312f;--tc-hover-bg-color:#c7c7c7;--tc-hover-icon-bg-color:#ababab;--tc-focus-bg-color:#ababab;--tc-focus-icon-bg-color:#8f8f8f;" jslog="147877; metadata:W251bGwsbnVsbCxbMTFdXQ==; track:click,keyboard_enter"><span class="qa482c">Entertainment</span><span class="eBC1oe"><svg width="20" height="20" viewBox="0 0 24 24" focusable="false" class=" NMm5M"><path d="M20 4h-3l2 4h-3l-2-4h-2l2 4h-3L9 4H7l2 4H6L4 4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4v-8h16v8zm-9.56-4.94l2.06.94-2.06.94L9.5 17l-.94-2.06L6.5 14l2.06-.94L9.5 11l.94 2.06zM15.5 11l.63 1.37 1.37.63-1.37.63L15.5 15l-.63-1.37L13.5 13l1.37-.63.63-1.37"></path></svg></span></a><a href="./category/extensions/productivity/tools" class="ScFtwd" style="--tc-bg-color:#c2e7ff;--tc-icon-bg-color:#7fcfff;--tc-icon-color:#062e6f;--tc-hover-bg-color:#7fcfff;--tc-hover-icon-bg-color:#5ab3f0;--tc-focus-bg-color:#5ab3f0;--tc-focus-icon-bg-color:#3998d3;" jslog="147877; metadata:W251bGwsbnVsbCxbNV1d; track:click,keyboard_enter"><span class="qa482c">Tools</span><span class="eBC1oe"><svg width="20" height="20" viewBox="0 0 24 24" focusable="false" class=" NMm5M"><path d="M21.67 18.17l-5.3-5.3h-.99l-2.54 2.54v.99l5.3 5.3c.39.39 1.02.39 1.41 0l2.12-2.12a.996.996 0 0 0 0-1.41zm-2.83 1.42l-4.24-4.24.71-.71 4.24 4.24-.71.71z"></path><path d="M17.34 10.19l1.41-1.41 2.12 2.12a3 3 0 0 0 0-4.24l-3.54-3.54-1.41 1.41V1.71l-.7-.71-3.54 3.54.71.71h2.83l-1.41 1.41 1.06 1.06-2.89 2.89-4.13-4.13V5.06L4.83 2.04 2 4.87 5.03 7.9h1.41l4.13 4.13-.85.85H7.6l-5.3 5.3a.996.996 0 0 0 0 1.41l2.12 2.12c.39.39 1.02.39 1.41 0l5.3-5.3v-2.12l5.15-5.15 1.06 1.05zm-7.98 5.15l-4.24 4.24-.71-.71 4.24-4.24.71.71z"></path></svg></span></a><a href="./category/extensions/lifestyle/art" class="ScFtwd" style="--tc-bg-color:#c4eed0;--tc-icon-bg-color:#6dd58c;--tc-icon-color:#0a3818;--tc-hover-bg-color:#6dd58c;--tc-hover-icon-bg-color:#37be5f;--tc-focus-bg-color:#37be5f;--tc-focus-icon-bg-color:#1ea446;" jslog="147877; metadata:W251bGwsbnVsbCxbMTRdXQ==; track:click,keyboard_enter"><span class="qa482c">Art &amp; Design</span><span class="eBC1oe"><svg width="20" height="20" viewBox="0 0 24 24" focusable="false" class=" NMm5M"><path d="M18.64 4.75L20 6.11l-7.79 7.79-1.36-1.36 7.79-7.79m0-2c-.51 0-1.02.2-1.41.59l-7.79 7.79c-.78.78-.78 2.05 0 2.83l1.36 1.36c.39.39.9.59 1.41.59.51 0 1.02-.2 1.41-.59l7.79-7.79c.78-.78.78-2.05 0-2.83l-1.35-1.35c-.39-.4-.9-.6-1.42-.6zM7 14.25c-1.66 0-3 1.34-3 3 0 1.31-1.16 2-2 2 .92 1.22 2.49 2 4 2 2.21 0 4-1.79 4-4 0-1.66-1.34-3-3-3z"></path></svg></span></a><a href="./category/extensions/make_chrome_yours/accessibility" class="ScFtwd" style="--tc-bg-color:#e3e3e3;--tc-icon-bg-color:#c7c7c7;--tc-icon-color:#2d312f;--tc-hover-bg-color:#c7c7c7;--tc-hover-icon-bg-color:#ababab;--tc-focus-bg-color:#ababab;--tc-focus-icon-bg-color:#8f8f8f;" jslog="147877; metadata:W251bGwsbnVsbCxbMjBdXQ==; track:click,keyboard_enter"><span class="qa482c">Accessibility</span><span class="eBC1oe"><svg width="20" height="20" viewBox="0 0 24 24" focusable="false" class=" NMm5M"><path d="M20.5 6c-2.61.7-5.67 1-8.5 1s-5.89-.3-8.5-1L3 8c1.86.5 4 .83 6 1v13h2v-6h2v6h2V9c2-.17 4.14-.5 6-1l-.5-2zM12 6c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"></path></svg></span></a></div>
EOF;

		$expected = <<<EOF
<!-- wp:heading {"level":2} -->
<h2 class="WAxuqb">Top categories</h2>
<!-- /wp:heading -->
<!-- wp:group -->
<div class="wp-block-group BjaEYd"><!-- wp:paragraph --><p><a href="./category/extensions/lifestyle/shopping" class="ScFtwd" style="--tc-bg-color:#d3e3fd;--tc-icon-bg-color:#a8c7fa;--tc-icon-color:#000;--tc-hover-bg-color:#a8c7fa;--tc-hover-icon-bg-color:#7cacf8;--tc-focus-bg-color:#7cacf8;--tc-focus-icon-bg-color:#4c8df6;" jslog="147877; metadata:W251bGwsbnVsbCxbOV1d; track:click,keyboard_enter" data-focusid="363"><span class="qa482c">Shopping<span class="eBC1oe"><!-- wp:html --><svg enable-background="new 0 0 24 24" height="20" viewbox="0 0 24 24" width="20" focusable="false" class=" NMm5M"><g><path d="M18,6h-2c0-2.21-1.79-4-4-4S8,3.79,8,6H6C4.9,6,4,6.9,4,8v12c0,1.1,0.9,2,2,2h12c1.1,0,2-0.9,2-2V8C20,6.9,19.1,6,18,6z M12,4c1.1,0,2,0.9,2,2h-4C10,4.9,10.9,4,12,4z M18,20H6V8h2v2c0,0.55,0.45,1,1,1s1-0.45,1-1V8h4v2c0,0.55,0.45,1,1,1s1-0.45,1-1V8 h2V20z"></path><rect fill="none" height="24" width="24"></rect></g></svg><!-- /wp:html --></span></span></a></p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="./category/extensions/lifestyle/entertainment" class="ScFtwd" style="--tc-bg-color:#e3e3e3;--tc-icon-bg-color:#c7c7c7;--tc-icon-color:#2d312f;--tc-hover-bg-color:#c7c7c7;--tc-hover-icon-bg-color:#ababab;--tc-focus-bg-color:#ababab;--tc-focus-icon-bg-color:#8f8f8f;" jslog="147877; metadata:W251bGwsbnVsbCxbMTFdXQ==; track:click,keyboard_enter"><span class="qa482c">Entertainment<span class="eBC1oe"><!-- wp:html --><svg width="20" height="20" viewbox="0 0 24 24" focusable="false" class=" NMm5M"><path d="M20 4h-3l2 4h-3l-2-4h-2l2 4h-3L9 4H7l2 4H6L4 4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4v-8h16v8zm-9.56-4.94l2.06.94-2.06.94L9.5 17l-.94-2.06L6.5 14l2.06-.94L9.5 11l.94 2.06zM15.5 11l.63 1.37 1.37.63-1.37.63L15.5 15l-.63-1.37L13.5 13l1.37-.63.63-1.37"></path></svg><!-- /wp:html --></span></span></a></p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="./category/extensions/productivity/tools" class="ScFtwd" style="--tc-bg-color:#c2e7ff;--tc-icon-bg-color:#7fcfff;--tc-icon-color:#062e6f;--tc-hover-bg-color:#7fcfff;--tc-hover-icon-bg-color:#5ab3f0;--tc-focus-bg-color:#5ab3f0;--tc-focus-icon-bg-color:#3998d3;" jslog="147877; metadata:W251bGwsbnVsbCxbNV1d; track:click,keyboard_enter"><span class="qa482c">Tools<span class="eBC1oe"><!-- wp:html --><svg width="20" height="20" viewbox="0 0 24 24" focusable="false" class=" NMm5M"><path d="M21.67 18.17l-5.3-5.3h-.99l-2.54 2.54v.99l5.3 5.3c.39.39 1.02.39 1.41 0l2.12-2.12a.996.996 0 0 0 0-1.41zm-2.83 1.42l-4.24-4.24.71-.71 4.24 4.24-.71.71z"></path><path d="M17.34 10.19l1.41-1.41 2.12 2.12a3 3 0 0 0 0-4.24l-3.54-3.54-1.41 1.41V1.71l-.7-.71-3.54 3.54.71.71h2.83l-1.41 1.41 1.06 1.06-2.89 2.89-4.13-4.13V5.06L4.83 2.04 2 4.87 5.03 7.9h1.41l4.13 4.13-.85.85H7.6l-5.3 5.3a.996.996 0 0 0 0 1.41l2.12 2.12c.39.39 1.02.39 1.41 0l5.3-5.3v-2.12l5.15-5.15 1.06 1.05zm-7.98 5.15l-4.24 4.24-.71-.71 4.24-4.24.71.71z"></path></svg><!-- /wp:html --></span></span></a></p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="./category/extensions/lifestyle/art" class="ScFtwd" style="--tc-bg-color:#c4eed0;--tc-icon-bg-color:#6dd58c;--tc-icon-color:#0a3818;--tc-hover-bg-color:#6dd58c;--tc-hover-icon-bg-color:#37be5f;--tc-focus-bg-color:#37be5f;--tc-focus-icon-bg-color:#1ea446;" jslog="147877; metadata:W251bGwsbnVsbCxbMTRdXQ==; track:click,keyboard_enter"><span class="qa482c">Art &amp; Design<span class="eBC1oe"><!-- wp:html --><svg width="20" height="20" viewbox="0 0 24 24" focusable="false" class=" NMm5M"><path d="M18.64 4.75L20 6.11l-7.79 7.79-1.36-1.36 7.79-7.79m0-2c-.51 0-1.02.2-1.41.59l-7.79 7.79c-.78.78-.78 2.05 0 2.83l1.36 1.36c.39.39.9.59 1.41.59.51 0 1.02-.2 1.41-.59l7.79-7.79c.78-.78.78-2.05 0-2.83l-1.35-1.35c-.39-.4-.9-.6-1.42-.6zM7 14.25c-1.66 0-3 1.34-3 3 0 1.31-1.16 2-2 2 .92 1.22 2.49 2 4 2 2.21 0 4-1.79 4-4 0-1.66-1.34-3-3-3z"></path></svg><!-- /wp:html --></span></span></a></p><!-- /wp:paragraph --><!-- wp:paragraph --><p><a href="./category/extensions/make_chrome_yours/accessibility" class="ScFtwd" style="--tc-bg-color:#e3e3e3;--tc-icon-bg-color:#c7c7c7;--tc-icon-color:#2d312f;--tc-hover-bg-color:#c7c7c7;--tc-hover-icon-bg-color:#ababab;--tc-focus-bg-color:#ababab;--tc-focus-icon-bg-color:#8f8f8f;" jslog="147877; metadata:W251bGwsbnVsbCxbMjBdXQ==; track:click,keyboard_enter"><span class="qa482c">Accessibility<span class="eBC1oe"><!-- wp:html --><svg width="20" height="20" viewbox="0 0 24 24" focusable="false" class=" NMm5M"><path d="M20.5 6c-2.61.7-5.67 1-8.5 1s-5.89-.3-8.5-1L3 8c1.86.5 4 .83 6 1v13h2v-6h2v6h2V9c2-.17 4.14-.5 6-1l-.5-2zM12 6c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"></path></svg><!-- /wp:html --></span></span></a></p><!-- /wp:paragraph --></div>
<!-- /wp:group -->
EOF;

		$converter = new Block_Converter_Recursive( $html );

		$blocks = $converter->convert();
		var_dump( __METHOD__, $html, $blocks );ob_flush();flush();
		$this->assertEquals( $expected, $blocks );
	}
}