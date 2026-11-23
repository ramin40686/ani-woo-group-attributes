<?php
/**
 * Gutenberg dynamic block registration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {
		$theme = wp_get_theme();

		wp_register_style(
			'anishop-hero-slider',
			get_stylesheet_directory_uri() . '/assets/css/anishop-hero.css',
			array(),
			$theme->get( 'Version' )
		);

		wp_register_script(
			'anishop-hero-slider',
			get_stylesheet_directory_uri() . '/assets/js/anishop-hero.js',
			array(),
			$theme->get( 'Version' ),
			true
		);

		wp_register_script(
			'anishop-hero-slider-editor',
			get_stylesheet_directory_uri() . '/assets/js/anishop-hero-editor.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n' ),
			$theme->get( 'Version' ),
			true
		);

		register_block_type(
			'anishop/hero-slider',
			array(
				'api_version'     => 3,
				'title'           => 'اسلایدر هیرو',
				'description'     => 'اسلایدر هیروی صفحه اصلی آنی شاپ.',
				'category'        => 'design',
				'icon'            => 'images-alt2',
				'editor_script'   => 'anishop-hero-slider-editor',
				'render_callback' => 'anishop_hero_render',
				'supports'        => array(
					'html' => false,
				),
			)
		);
	}
);
