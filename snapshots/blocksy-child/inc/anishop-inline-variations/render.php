<?php
/**
 * Anishop inline variations — frontend renderer (Phase 4B).
 *
 * Presentation-only layer over the native WooCommerce variation form. This
 * file does NOT render any controls itself: it only (1) enqueues the frontend
 * assets on the pages where the renderer is needed and (2) hands a small
 * per-select configuration to the frontend script via `wp_localize_script`.
 *
 * The frontend script (assets/js/anishop-inline-variations.js) reads the
 * native `<select>` elements as the source of truth, renders the inline
 * controls (chip / radio / swatch) from their live `<option>` list, and
 * delegates ALL variation behavior (matching, price, stock, SKU, image,
 * validation, add-to-cart) back to native WooCommerce. No variation logic is
 * duplicated.
 *
 * Renderer resolution (V1, per the Phase 4A-R corrected model — the legacy
 * `renderer_map` is NOT used):
 *  - Global (taxonomy) attribute classified `color` -> fixed `swatch`.
 *  - Global (taxonomy) attribute (default)          -> `default_renderer`.
 *  - Custom / product-level attribute               -> `fallback_renderer`.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the V1 renderer for a single variation attribute.
 *
 * @param string $attribute_name The attribute name (a `pa_` taxonomy slug for
 *                               global attributes, or a raw name for custom /
 *                               product-level attributes).
 * @param array  $config         The Layer B configuration (from
 *                               anishop_iv_get_config()).
 * @return string One of `dropdown`, `chip`, `radio`, or `swatch`.
 */
function anishop_iv_resolve_renderer( $attribute_name, $config ) {
	if ( taxonomy_exists( $attribute_name ) ) {
		if ( 'color' === anishop_iv_get_classification_for_taxonomy( $attribute_name ) ) {
			return anishop_iv_color_renderer();
		}
		return $config['default_renderer'];
	}
	return $config['fallback_renderer'];
}

/**
 * Build the per-select frontend configuration.
 *
 * For the fixed `swatch` renderer on a taxonomy attribute, each option's color
 * HEX is resolved from the existing term meta (`anishop_presentation_color_hex`).
 * A missing / invalid HEX resolves to an empty string; the frontend renders a
 * neutral swatch in that case (it never breaks the native form).
 *
 * @param string $attribute_name The attribute name.
 * @param array  $options        The attribute option values (term slugs for
 *                               global attributes, raw values for custom ones).
 * @param array  $config         The Layer B configuration.
 * @return array
 */
function anishop_iv_build_select_config( $attribute_name, $options, $config ) {
	$renderer = anishop_iv_resolve_renderer( $attribute_name, $config );

	$label = function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $attribute_name ) : $attribute_name;

	$select_config = array(
		'renderer' => $renderer,
		'label'    => $label,
	);

	if ( 'swatch' === $renderer && taxonomy_exists( $attribute_name ) ) {
		$options_config = array();
		foreach ( (array) $options as $option_value ) {
			$option_value = (string) $option_value;
			if ( '' === $option_value ) {
				continue;
			}
			$term = get_term_by( 'slug', $option_value, $attribute_name );
			$hex  = $term ? anishop_iv_get_term_hex( $term->term_id ) : '';
			$options_config[ $option_value ] = array( 'hex' => $hex );
		}
		$select_config['options'] = $options_config;
	}

	return $select_config;
}

/**
 * Build the full frontend configuration for a product.
 *
 * Keys are `attribute_` + `sanitize_title( $attribute_name )` — exactly the
 * value WooCommerce puts in each select's `data-attribute_name` attribute, so
 * the frontend can match config entries to selects with no other coupling.
 *
 * @param WC_Product $product The variable product.
 * @param array      $config  The Layer B configuration.
 * @return array
 */
function anishop_iv_build_frontend_config( $product, $config ) {
	$selects = array();
	foreach ( $product->get_variation_attributes() as $attribute_name => $options ) {
		$selects[ 'attribute_' . sanitize_title( $attribute_name ) ] = anishop_iv_build_select_config( $attribute_name, $options, $config );
	}

	return array(
		'shape'         => $config['color_shape'],
		'size'          => (int) $config['color_size'],
		'spacing'       => (int) $config['spacing'],
		'show_selected' => (bool) $config['show_selected'],
		'selects'       => $selects,
	);
}

/**
 * Enqueue the frontend assets and localize the per-select configuration.
 *
 * Gated on: feature enabled + single product page + variable product type +
 * at least one variation attribute. This keeps the assets off every unrelated
 * page (shop, cart, simple products, the homepage, etc.).
 *
 * @return void
 */
function anishop_iv_frontend_enqueue() {
	$config = anishop_iv_get_config();
	if ( empty( $config['enabled'] ) ) {
		return;
	}

	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
	if ( ! $product || 'variable' !== $product->get_type() ) {
		return;
	}

	$variation_attributes = $product->get_variation_attributes();
	if ( empty( $variation_attributes ) ) {
		return;
	}

	$version = wp_get_theme( get_stylesheet_directory() )->get( 'Version' );

	wp_enqueue_style(
		'anishop-inline-variations',
		get_stylesheet_directory_uri() . '/assets/css/anishop-inline-variations.css',
		array(),
		$version
	);
	wp_enqueue_script(
		'anishop-inline-variations',
		get_stylesheet_directory_uri() . '/assets/js/anishop-inline-variations.js',
		array( 'jquery' ),
		$version,
		true
	);

	wp_localize_script( 'anishop-inline-variations', 'anishopInlineVariations', anishop_iv_build_frontend_config( $product, $config ) );
}
add_action( 'wp_enqueue_scripts', 'anishop_iv_frontend_enqueue' );