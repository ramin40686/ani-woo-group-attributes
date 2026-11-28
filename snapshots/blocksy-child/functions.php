<?php
/**
 * Anishop Blocksy Child.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the Anishop text domain (Persian translations live in /languages).
// Locale-aware: WordPress loads the matching .mo only for the active locale.
add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'anishop', get_stylesheet_directory() . '/languages' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style(
			'anishop-child-parent',
			get_template_directory_uri() . '/style.css',
			array(),
			wp_get_theme( get_template() )->get( 'Version' )
		);
	},
	20
);

require_once __DIR__ . '/inc/anishop-hero/bootstrap.php';
require_once __DIR__ . '/inc/anishop-home-products/bootstrap.php';
require_once __DIR__ . '/inc/anishop-mobile-nav/config.php';
require_once __DIR__ . '/inc/anishop-mobile-nav/bootstrap.php';
require_once __DIR__ . '/inc/anishop-mobile-nav/admin.php';
require_once __DIR__ . '/inc/anishop-inline-variations/config.php';
require_once __DIR__ . '/inc/anishop-inline-variations/admin.php';
require_once __DIR__ . '/inc/anishop-inline-variations/render.php';
require_once __DIR__ . '/inc/anishop-inline-variations/key-features.php';
require_once __DIR__ . '/inc/anishop-buy-box/config.php';
require_once __DIR__ . '/inc/anishop-buy-box/admin.php';
require_once __DIR__ . '/inc/anishop-buy-box/render.php';
require_once __DIR__ . '/inc/anishop-buy-box/product-rows.php';
require_once __DIR__ . '/inc/anishop-search-box/bootstrap.php';
require_once __DIR__ . '/inc/ani-group-product-attributes/bootstrap.php';

