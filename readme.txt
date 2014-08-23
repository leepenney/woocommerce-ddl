=== Plugin Name ===
Contributors: longplay
Tags: woocommerce, ddl, datalayer, integration
Requires at least: 3.9.1
Tested up to: 3.9.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a JavaScript data layer to your WooCommerce installation that conforms to the W3C spec

== Description ==

The W3C have issues a [Customer Experience Digital Layer Specification](http://www.w3.org/community/custexpdata/) that allows sites to define a JavaScript object to make data accessible to third parties.

This plugin creates that object for an installation of WooCommerce, exposing various pieces of product and cart data to allow integration with third party code (for example, cart abandonment scripts).

It also exposes the logged in user's name and email address, but only on their machine, so shouldn't pose a privacy issue.

Tested on WooCommerce version 2.1.9

== Installation ==

To install the plugin:

1. Upload `woocommerce-ddl.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. That's it

== Changelog ==

= 1.0 =
* Initial release
