<?php
/**
 * Anishop Buy Box — product-specific rows (Stage 3).
 *
 * Three-Column Buy Box project, Stage 3: per-product Buy Box rows configured
 * directly on the WooCommerce product-edit screen (the Advanced / «پیشرفته»
 * tab of the product data panel).
 *
 * Data model:
 *  - Post meta `anishop_buy_box_rows` on the PARENT product: an ordered array
 *    of row objects `{ enabled, title, text, link, icon }`. This is the single
 *    source of truth for the product-specific rows.
 *  - The rows are rendered at the TOP of the Buy Box (above the price), while
 *    the global rows (Theme Mod) render at the BOTTOM (below the trigger).
 *
 * Architecture:
 *  - The section is added to the product data panel's Advanced tab through the
 *    `woocommerce_product_options_advanced` hook (the product editor is the
 *    CLASSIC editor on this site — verified).
 *  - The save handler hooks `save_post_product` (the same convention as the
 *    Key Features module): nonce → autosave/revision guards → `edit_post`
 *    capability → single sanitization authority → update/delete meta.
 *  - The rows repeater UI reuses the shared `anishop_buy_box_admin_rows_repeater()`
 *    (defined in admin.php) + the shared admin script.
 *
 * Scope (important):
 *  - This file is self-contained. It does not modify the config, admin, or
 *    renderer files (it only consumes their public functions).
 *  - No new plugins, libraries, external assets, or database tables.
 *  - Text domain: `anishop`.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin.php';

/**
 * The nonce action for the product-rows save.
 */
define( 'ANISHOP_BUY_BOX_PRODUCT_ROWS_NONCE_ACTION', 'anishop_buy_box_product_rows_save' );

/**
 * The nonce field name for the product-rows save.
 */
define( 'ANISHOP_BUY_BOX_PRODUCT_ROWS_NONCE_FIELD', 'anishop_buy_box_product_rows_nonce' );

/**
 * Render the Buy Box rows section in the product data panel's Advanced tab.
 *
 * @return void
 */
function anishop_buy_box_product_rows_render_section() {
	$product_id = get_the_ID();
	if ( ! $product_id ) {
		return;
	}
	$rows = anishop_buy_box_get_product_rows_all( $product_id );
	?>
	<div class="options_group anishop-buy-box-product-rows">
		<h2><?php esc_html_e( 'Buy Box rows', 'anishop' ); ?></h2>
		<p class="description">
			<?php echo esc_html( __( 'These rows apply ONLY to this product. They appear at the TOP of the Buy Box, above the price. They do not modify the global Buy Box rows (set under Appearance → Anishop → Buy Box Container) or any other product.', 'anishop' ) ); ?>
		</p>
		<?php wp_nonce_field( ANISHOP_BUY_BOX_PRODUCT_ROWS_NONCE_ACTION, ANISHOP_BUY_BOX_PRODUCT_ROWS_NONCE_FIELD ); ?>
		<?php anishop_buy_box_admin_rows_repeater( $rows, 'anishop_buy_box_rows' ); ?>
	</div>
	<?php
}
add_action( 'woocommerce_product_options_advanced', 'anishop_buy_box_product_rows_render_section' );

/**
 * Save the product-specific Buy Box rows on product save.
 *
 * Guards (in order): nonce, autosave, revision, capability. The submitted
 * value is passed through the single sanitization authority. An empty result
 * deletes the meta (so the product has no rows); a non-empty result updates it.
 *
 * @param int $post_id The product post ID.
 * @return void
 */
function anishop_buy_box_product_rows_save( $post_id ) {
	if ( ! isset( $_POST[ ANISHOP_BUY_BOX_PRODUCT_ROWS_NONCE_FIELD ] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ ANISHOP_BUY_BOX_PRODUCT_ROWS_NONCE_FIELD ] ) ), ANISHOP_BUY_BOX_PRODUCT_ROWS_NONCE_ACTION ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the raw value is passed to the single sanitization authority below.
	$raw   = isset( $_POST['anishop_buy_box_rows'] ) ? $_POST['anishop_buy_box_rows'] : array();
	$clean = anishop_buy_box_sanitize_rows( $raw );

	if ( empty( $clean ) ) {
		delete_post_meta( $post_id, ANISHOP_BUY_BOX_ROWS_META_KEY );
	} else {
		update_post_meta( $post_id, ANISHOP_BUY_BOX_ROWS_META_KEY, $clean );
	}
}
add_action( 'save_post_product', 'anishop_buy_box_product_rows_save', 10, 1 );

/**
 * Enqueue the rows repeater admin script on the product edit screen.
 *
 * @param string $hook The current admin page hook.
 * @return void
 */
function anishop_buy_box_product_rows_admin_enqueue( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}
	$css_version = filemtime( get_stylesheet_directory() . '/assets/css/anishop-buy-box-admin.css' );
	$js_version  = filemtime( get_stylesheet_directory() . '/assets/js/anishop-buy-box-admin.js' );
	wp_enqueue_style(
		'anishop-buy-box-admin',
		get_stylesheet_directory_uri() . '/assets/css/anishop-buy-box-admin.css',
		array(),
		$css_version
	);
	wp_enqueue_script(
		'anishop-buy-box-admin',
		get_stylesheet_directory_uri() . '/assets/js/anishop-buy-box-admin.js',
		array(),
		$js_version,
		true
	);
	wp_localize_script(
		'anishop-buy-box-admin',
		'anishopBuyBoxAdmin',
		array(
			'i18n' => array(
				'confirmRemove' => __( 'Remove this row?', 'anishop' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'anishop_buy_box_product_rows_admin_enqueue' );
