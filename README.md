# universal-importer
PoC for a universal import mechanism for WP.

*This is very much Work In Progress. The code is in flux and not in its final form.*

## What is this?

This is a plugin intended to explore the idea of importing any web site into a fresh WordPress install. In other words, an "anything-to-WordPress" importer; or a site cloner.

In order to focus the Proof of Concept on useful cases, I have aimed initially at a "WordPress-to-WordPress" scenario. It seems that this is a common use case: a user has lost admin access to an old WordPress site, and wants to create a new site that copies most of the pages and content from the old one.

## How does it work?

The plugin includes:

* A simple web crawler that follows `sitemap.xml` files.
* A page parser that decomposes HTML into its main components like page content, navigation, metadata.
* A block converter, that converts rendered HTML into Gutenberg blocks.

Given a web site to import from, the plugin will crawl the site, and attempt to reproduce all of its posts and pages.

## How do I test it?

**In its current state I recomment only running this plugin in `wp-now` for safety**

Usage:

```sh
npm install
composer install
npm run wp-now-import
```

This should run a fresh empty WP site in wp-now and open a browser window to http://localhost:8881/wp-admin/admin.php?import=universal-importer

(Note: there may be a wp-now bug preventing it from opening the landing page automatically)

If not, follow that link or open your wp-now site and visit wp-admin / Tools / Import / Universal Importer

Enter the URL of a WordPress web site to import and click the button.

## Known issues

* Only some Gutenberg blocks are handled correctly
* Only some post types are handled correctly
* Permalink scheme isn't matched correctly
* Links and image/asset URLs are not yet rewritten to point to the new site
* Images and other attachments are not yet imported
* Can't run in Playground because CORS prevents crawling an external web site (could be fixed with a proxy)