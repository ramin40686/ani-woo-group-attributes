<?php
/**
 * Anishop homepage product carousels bootstrap.
 *
 * Enqueues the scoped CSS/JS that turns the four front-page
 * Product Collection sections into aligned horizontal carousels.
 * Front page only — no other page is affected.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_front_page() ) {
			return;
		}

		$theme = wp_get_theme();

		wp_enqueue_style(
			'anishop-home-products',
			get_stylesheet_directory_uri() . '/assets/css/anishop-home-products.css',
			array(),
			$theme->get( 'Version' )
		);

		wp_enqueue_script(
			'anishop-home-products',
			get_stylesheet_directory_uri() . '/assets/js/anishop-home-products.js',
			array(),
			$theme->get( 'Version' ),
			true
		);
	},
	20
);
