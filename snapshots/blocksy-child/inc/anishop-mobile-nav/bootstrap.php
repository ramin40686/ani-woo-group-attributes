<?php
/**
 * Anishop mobile bottom navigation bootstrap.
 *
 * Enqueues the scoped CSS + a small event-driven script (frontend only) and
 * renders the navigation globally via wp_footer. The active state, account
 * state and the initial cart count are resolved server-side. The cart count
 * badge is kept in sync after AJAX add/remove-to-cart (no page reload) via the
 * standard WooCommerce fragments mechanism: a dedicated cart-count fragment is
 * added to the AJAX response, and the script reads the real count from the
 * `added_to_cart` / `removed_from_cart` event payload.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/render.php';

add_action(
	'wp_enqueue_scripts',
	function () {
		$theme = wp_get_theme();

		$css_file    = get_stylesheet_directory() . '/assets/css/anishop-mobile-nav.css';
		$css_version = file_exists( $css_file ) ? (string) filemtime( $css_file ) : $theme->get( 'Version' );

		wp_enqueue_style(
			'anishop-mobile-nav',
			get_stylesheet_directory_uri() . '/assets/css/anishop-mobile-nav.css',
			array(),
			$css_version
		);

		// Only load the cart-sync script when WooCommerce is active: it relies
		// on the WooCommerce add/remove-to-cart AJAX events.
		if ( function_exists( 'WC' ) ) {
			$js_file    = get_stylesheet_directory() . '/assets/js/anishop-mobile-nav.js';
			$js_version = file_exists( $js_file ) ? (string) filemtime( $js_file ) : $theme->get( 'Version' );

			wp_enqueue_script(
				'anishop-mobile-nav',
				get_stylesheet_directory_uri() . '/assets/js/anishop-mobile-nav.js',
				array( 'jquery' ),
				$js_version,
				true
			);

			// Expose the Store API cart endpoint so the script can resolve the
			// real cart count on pages where neither the fragment payload nor
			// the data store is available (e.g. the Homepage Product Collection,
			// which uses the Interactivity API).
			wp_localize_script(
				'anishop-mobile-nav',
				'anishopMobileNav',
				array(
					'storeApiUrl' => rest_url( 'wc/store/v1/cart' ),
				)
			);
		}
	},
	20
);

add_action( 'wp_footer', 'anishop_mobile_nav_render', 20 );

/**
 * Add a cart-count fragment so the real WooCommerce cart count is carried in
 * the AJAX add/remove-to-cart response. The mobile bottom navigation script
 * reads it from the `added_to_cart` / `removed_from_cart` event payload and
 * updates the badge without a page reload.
 *
 * The fragment targets a dedicated selector that is not present in the DOM;
 * WooCommerce's fragment refresh is a no-op for it, and the value is consumed
 * from the event payload instead. Using the server-side count (not a client
 * increment) keeps the badge accurate for quantity changes, duplicates and
 * removals.
 *
 * @param array $fragments WooCommerce cart fragments (selector => HTML).
 * @return array
 */
function anishop_mobile_nav_cart_count_fragment( $fragments ) {
	$count = 0;
	if ( function_exists( 'WC' ) && null !== WC() && null !== WC()->cart ) {
		$count = (int) WC()->cart->get_cart_contents_count();
	}

	$fragments['.anishop-mobile-nav__cart-count'] = '<span class="anishop-mobile-nav__cart-count" data-count="' . esc_attr( $count ) . '">' . esc_html( number_format_i18n( $count ) ) . '</span>';

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'anishop_mobile_nav_cart_count_fragment' );
