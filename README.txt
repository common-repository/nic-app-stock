=== Plugin Name ===
Contributors: efraimbayarri
Donate link: https://efraim.cat
Tags: woocommerce, woocommerce rest api, stock update, stock synchronization 
Requires at least: 5.0
Tested up to: 5.5.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin creates a relationship between the SKU of the local product of its variation and another product defined in another WooCommerce system and updates the local stock with which the supplier has.

== Description ==

This plugin creates a relationship between the SKU of the local product of its variation and another product defined in another WooCommerce system and updates the local stock with which the supplier has.

The connection is made through the WooCommerce REST API. You need to have a REST API key on the provider's system. You can find information in this document on how to create the key.

The key is assigned to a Wordpress user with sufficient rights. It is not necessary to know the user's data such as name or password since all communication is done from the REST API.

This REST API key only needs to have read rights. Remember to take note of the consumer key and the consumer secret as it is only displayed during key creation.

Requirements:

php 7.1

== Installation ==

For detailed installation instructions, please read the [standard installation procedure for WordPress plugins](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 1.0.0 =

  * Initial release
