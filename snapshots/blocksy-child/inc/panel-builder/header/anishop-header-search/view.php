<?php
/**
 * Anishop Search Box — Header Builder element view (Phase 3.3C).
 *
 * Renders the live WooCommerce product search form inside the
 * `anishop-header-search` Header Builder element.
 *
 * The form is the native Blocksy search form (the parent theme's
 * `searchform.php` via `get_search_form()`) rendered with EXPLICIT arguments,
 * so the Blocksy Free Live Search engine recognizes and drives it (inline
 * entry point: `.ct-search-form[data-live-results]` outside the modal,
 * perPage 5). No second engine, no shortcode, no HTML element.
 *
 * The dashboard settings (`anishop_search_box` Theme Mod) are applied as
 * `--anishop-sb-*` CSS custom properties on the wrapper; the frontend
 * stylesheet (`assets/css/anishop-search-box.css`) maps them onto Blocksy's
 * form-field variables. The frontend script receives the text settings via
 * `wp_localize_script` (see `inc/anishop-search-box/bootstrap.php`).
 *
 * When the module is disabled it renders no visible output, while the Header
 * Builder placement itself is kept intact (the item remains in the saved
 * header data, so re-enabling requires no re-placement).
 *
 * Render args provided by Blocksy (see builder-header-renderer.php):
 * $panel_type, $atts, $section_id, $row_id, $attr, $device, $render_args, $item_id.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The config layer is loaded by the module bootstrap; guard against a
// partially-initialized environment (e.g. a direct include).
if ( ! function_exists( 'anishop_search_box_get_config' ) ) {
	return;
}

$config = anishop_search_box_get_config();

// Module disabled → no visible output (placement stays intact).
if ( empty( $config['enabled'] ) ) {
	return;
}

if ( ! isset( $attr ) || ! is_array( $attr ) ) {
	$attr = [];
}

/**
 * Explicit form arguments (Customizer-independent, Phase 2 §3):
 *  - `ct_post_type`        → hidden `post_type=product` input → product-only
 *                            search (REST `subtype=product` + the theme's
 *                            `pre_get_posts` product handling on submit).
 *  - `search_live_results` → `data-live-results` attribute (the engine's
 *                            recognition contract); `no` = plain GET form.
 *  - `live_results_attr`   → `thumbs` token (product images in results).
 *  - `ct_product_price`    → `product_price` token (WooCommerce price_html).
 *  - `ct_product_status`   → `product_status` token (stock status).
 *  - `search_placeholder`  → only when the dashboard value is set (empty =
 *                            "use the Blocksy default", per the admin UI).
 */
$form_args = array(
	'ct_post_type'        => array( 'product' ),
	'search_live_results' => ! empty( $config['live_results'] ) ? 'yes' : 'no',
	'ct_product_price'    => (bool) $config['show_price'],
	'ct_product_status'   => (bool) $config['show_status'],
);

if ( 'yes' === $form_args['search_live_results'] ) {
	$form_args['live_results_attr'] = ! empty( $config['show_thumbs'] ) ? 'thumbs' : '';
}

if ( '' !== $config['placeholder'] ) {
	$form_args['search_placeholder'] = $config['placeholder'];
}

// Appearance settings → CSS custom properties on the wrapper (read by
// assets/css/anishop-search-box.css). Values are sanitized by the config
// layer (px integers, #RRGGBB colors).
$style = array(
	'--anishop-sb-height'                 => $config['height'] . 'px',
	'--anishop-sb-max-width'              => $config['max_width'] . 'px',
	'--anishop-sb-dropdown-width'         => $config['dropdown_width'] . 'px',
	'--anishop-sb-mobile-dropdown-top'    => $config['mobile_dropdown_top'] . 'px',
	'--anishop-sb-mobile-side-margin'     => $config['mobile_dropdown_side_margin'] . 'px',
	'--anishop-sb-bg'                     => $config['bg_color'],
	'--anishop-sb-text'                   => $config['text_color'],
	'--anishop-sb-placeholder'            => $config['placeholder_color'],
	'--anishop-sb-border'                 => $config['border_color'],
	'--anishop-sb-border-width'           => $config['border_width'] . 'px',
	'--anishop-sb-radius'                 => $config['radius'] . 'px',
	'--anishop-sb-focus'                  => $config['focus_color'],
	'--anishop-sb-icon-size'              => $config['icon_size'] . 'px',
);

$style_attr = '';
foreach ( $style as $style_name => $style_value ) {
	$style_attr .= $style_name . ':' . esc_attr( $style_value ) . ';';
}
?>
<div class="anishop-header-search" style="<?php echo esc_attr( $style_attr ); ?>" <?php echo blocksy_attr_to_html( $attr ); ?>>
	<?php
	// The parent theme's isolated wrapper guards against third-party
	// `get_search_form` filters (IS_Admin_Public); fall back to the plain
	// template hierarchy if the helper is unavailable.
	if ( function_exists( 'blocksy_isolated_get_search_form' ) ) {
		blocksy_isolated_get_search_form( $form_args );
	} else {
		get_search_form( $form_args );
	}
	?>
</div>
