<?php
/**
 * Anishop Buy Box — frontend renderer (Stage 2).
 *
 * Three-Column Buy Box project, Stage 2: the three-column desktop layout +
 * the basic Buy Box (price mirror, stock/purchase status, trigger).
 *
 * Architecture (Option A — single native form + price mirror + trigger):
 *  - The native WooCommerce cart form stays in the DOM exactly once, in the
 *    middle "info" column (the `product_add_to_cart` layer is NOT removed).
 *    Its variation selectors, quantity, hidden fields, nonce, validation,
 *    stock rules, and native submission path are preserved untouched.
 *  - The Buy Box (left column) is a price/availability MIRROR + a TRIGGER that
 *    drives the real native form/button. It never recomputes price/stock and
 *    never creates a second form or a second add-to-cart path.
 *
 * Mechanism:
 *  - A layout filter disables the native `product_price` layer (the price moves
 *    to the Buy Box as a mirror) and inserts one `anishop_buy_box` custom layer
 *    immediately before `product_add_to_cart`.
 *  - The custom-layer renderer outputs the Buy Box container (price mirror,
 *    stock, trigger button).
 *  - CSS (anishop-buy-box.css) creates the three-column split (lg only) and
 *    hides the native button — but ONLY when a JS class is present, so a
 *    JavaScript failure (or the feature being off) leaves the native button
 *    visible and the original purchase path fully usable.
 *  - JS (anishop-buy-box.js) keeps the price/stock mirror in sync with the
 *    authoritative native state and drives the native form/button via the
 *    trigger.
 *
 * Feature-off guarantee: when `enabled=false` (or the Theme Mod is absent),
 * the layout filter returns the layout untouched, the custom layer renders
 * nothing, no body class is added, and no assets are enqueued. The site
 * renders exactly as today.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config.php';

/**
 * The custom single-product layer ID for the Buy Box.
 */
define( 'ANISHOP_BUY_BOX_LAYER_ID', 'anishop_buy_box' );

/**
 * Whether the Buy Box feature is active for the current request.
 *
 * Active only when: the master `enabled` switch is on, we are on a single
 * product page, and the product is a simple or variable product.
 *
 * @return bool
 */
function anishop_buy_box_is_active() {
	$config = anishop_buy_box_get_config();
	if ( empty( $config['enabled'] ) ) {
		return false;
	}
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return false;
	}
	$product_id = get_the_ID();
	if ( ! $product_id ) {
		return false;
	}
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return false;
	}
	return $product->is_type( array( 'simple', 'variable' ) );
}

/**
 * Layout filter: disable the native `product_price` layer and insert the
 * `anishop_buy_box` custom layer immediately before `product_add_to_cart`.
 *
 * The native form (`product_add_to_cart`) stays in the layout (middle column).
 * The Buy Box layer is inserted before it so the price/stock/trigger sit at
 * the purchase position; the CSS grid then places the Buy Box in the left
 * column and the remaining layers in the middle column.
 *
 * @param array $layout The normalized single product layout layers.
 * @return array
 */
function anishop_buy_box_insert_layer( $layout ) {
	if ( ! anishop_buy_box_is_active() ) {
		return $layout;
	}
	if ( ! is_array( $layout ) ) {
		return $layout;
	}
	$config = anishop_buy_box_get_config();

	// Idempotency: do not insert twice.
	foreach ( $layout as $layer ) {
		if ( is_array( $layer ) && ANISHOP_BUY_BOX_LAYER_ID === $layer['id'] ) {
			return $layout;
		}
	}

	// Disable the native product_price layer (the price moves to the Buy Box as
	// a mirror) — only when the desktop Buy Box is on.
	if ( ! empty( $config['desktop_buy_box'] ) ) {
		foreach ( $layout as $index => $layer ) {
			if ( is_array( $layer ) && 'product_price' === $layer['id'] ) {
				$layout[ $index ]['enabled'] = false;
			}
		}
	}

	// Append the Buy Box at the END of the layout. This gives the correct
	// mobile reading order (gallery → info/variations → full Buy Box) in the
	// normal document flow, and on desktop the CSS grid places the Buy Box in
	// the left column regardless of DOM order.
	$layout[] = array(
		'id'      => ANISHOP_BUY_BOX_LAYER_ID,
		'enabled' => true,
	);

	return $layout;
}
add_filter( 'blocksy:woocommerce:product-single:layout', 'anishop_buy_box_insert_layer', 10 );

/**
 * Render the Buy Box layer (Blocksy custom layer action).
 *
 * Outputs the Buy Box container with:
 *  - the price mirror (a `<p class="price">` that JS keeps in sync),
 *  - the stock/purchase status,
 *  - the trigger button (drives the native form/button).
 *
 * The native form (with its selects + quantity + native button) is NOT rendered
 * here — it stays in the `product_add_to_cart` layer (middle column). The
 * trigger drives it.
 *
 * @param array $layer The single product layout layer descriptor.
 * @return void
 */
function anishop_buy_box_render_layer( $layer ) {
	if ( ! is_array( $layer ) || ANISHOP_BUY_BOX_LAYER_ID !== $layer['id'] ) {
		return;
	}
	if ( ! anishop_buy_box_is_active() ) {
		return;
	}

	$product_id = get_the_ID();
	if ( ! $product_id ) {
		return;
	}
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return;
	}

	$is_variable  = $product->is_type( 'variable' );
	$price_html   = $product->get_price_html();
	$availability = wc_get_stock_html( $product );
	$purchasable  = $product->is_purchasable();
	$trigger_text = $is_variable ? __( 'Choose options', 'anishop' ) : __( 'Add to cart', 'anishop' );

	// Stage 3: the configurable rows. Product-specific rows render at the TOP
	// (above the price); global rows render at the BOTTOM (below the trigger).
	// Both are static (they do not change with variation selection), so they do
	// not touch the Stage 2 price/stock mirror or the trigger state.
	$product_rows = anishop_buy_box_get_product_rows( $product_id );
	$global_rows  = anishop_buy_box_get_global_rows();

	// The row appearance (global style + color) → concrete colors as CSS vars.
	$appearance = anishop_buy_box_get_row_appearance();
	$colors     = anishop_buy_box_row_colors( $appearance['style'], $appearance['color'] );
	$style_attr = sprintf(
		'--anishop-bb-row-bg: %1$s; --anishop-bb-row-fg: %2$s; --anishop-bb-row-border: %3$s;',
		$colors['bg'],
		$colors['fg'],
		$colors['border']
	);

	// Stage 4: the desktop sticky Buy Box. When active (three-column + desktop
	// + sticky), the Buy Box is wrapped in an absolutely-positioned container
	// that spans the full summary height; the Buy Box itself becomes
	// `position: sticky` within that container (see anishop-buy-box.css).
	$config    = anishop_buy_box_get_config();
	$is_sticky = ! empty( $config['desktop_sticky_buy_box'] ) && ! empty( $config['three_column_layout'] ) && ! empty( $config['desktop_buy_box'] );
	?>
	<?php if ( $is_sticky ) : ?><div class="anishop-buy-box-sticky-wrap"><?php endif; ?>
	<div class="anishop-buy-box anishop-buy-box--rows-<?php echo esc_attr( $appearance['style'] ); ?>" style="<?php echo esc_attr( $style_attr ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-product_type="<?php echo esc_attr( $product->get_type() ); ?>">
		<?php if ( $product_rows ) : ?>
			<div class="anishop-buy-box__rows anishop-buy-box__rows--product">
				<?php foreach ( $product_rows as $row ) { anishop_buy_box_render_row( $row ); } ?>
			</div>
		<?php endif; ?>
		<p class="price anishop-buy-box__price"><?php echo wp_kses_post( $price_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price HTML (sanitized by WC). ?></p>
		<div class="anishop-buy-box__stock"><?php echo wp_kses_post( $availability ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce stock HTML (sanitized by WC). ?></div>
		<div class="anishop-buy-box__action">
			<button type="button" class="anishop-buy-box__trigger button alt"<?php echo $purchasable ? '' : ' disabled'; ?>><?php echo esc_html( $trigger_text ); ?></button>
		</div>
		<?php if ( $global_rows ) : ?>
			<div class="anishop-buy-box__rows anishop-buy-box__rows--global">
				<?php foreach ( $global_rows as $row ) { anishop_buy_box_render_row( $row ); } ?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $is_sticky ) : ?></div><?php endif; ?>
	<?php
}
add_action( 'blocksy:woocommerce:product:custom:layer', 'anishop_buy_box_render_layer', 10, 1 );

/**
 * Render a single Buy Box row (a compact, button-like assurance element).
 *
 * The row is a link (`<a>`) when it has a `link`, otherwise a non-interactive
 * `<div>`. The icon is a trusted inline SVG from the whitelist (decorative,
 * `aria-hidden`); the text is escaped. The colors come from the CSS variables
 * set on the Buy Box container (see `anishop_buy_box_render_layer()`).
 *
 * @param array $row A sanitized row `{ enabled, title, text, link, icon }`.
 * @return void
 */
function anishop_buy_box_render_row( $row ) {
	if ( ! is_array( $row ) || empty( $row['title'] ) ) {
		return;
	}
	$icon_svg = anishop_buy_box_icon_svg( isset( $row['icon'] ) ? $row['icon'] : 'none' );
	$has_link = ! empty( $row['link'] );
	?>
	<?php if ( $has_link ) : ?>
		<a class="anishop-buy-box__row" href="<?php echo esc_url( $row['link'] ); ?>" target="_blank" rel="noopener noreferrer">
	<?php else : ?>
		<div class="anishop-buy-box__row">
	<?php endif; ?>
		<?php if ( $icon_svg ) : ?>
			<span class="anishop-buy-box__row-icon-wrap"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted SVG from the icon whitelist. ?></span>
		<?php endif; ?>
		<span class="anishop-buy-box__row-text">
			<span class="anishop-buy-box__row-title"><?php echo esc_html( $row['title'] ); ?></span>
			<?php if ( ! empty( $row['text'] ) ) : ?>
				<span class="anishop-buy-box__row-desc"><?php echo esc_html( $row['text'] ); ?></span>
			<?php endif; ?>
		</span>
	<?php if ( $has_link ) : ?>
		</a>
	<?php else : ?>
		</div>
	<?php endif; ?>
	<?php
}

/**
 * Add the Buy Box body classes when the feature is active.
 *
 *  - `anishop-buy-box-enabled` — the feature is active (used to gate the
 *    native-button hiding, together with the JS class).
 *  - `anishop-buy-box-three-col` — the three-column split is active.
 *  - `anishop-buy-box-inline` — the Buy Box is inline (no three-column split).
 *
 * @param array $classes The existing body classes.
 * @return array
 */
function anishop_buy_box_body_class( $classes ) {
	if ( ! anishop_buy_box_is_active() ) {
		return $classes;
	}
	$config = anishop_buy_box_get_config();
	$classes[] = 'anishop-buy-box-enabled';
	if ( ! empty( $config['desktop_buy_box'] ) ) {
		if ( ! empty( $config['three_column_layout'] ) ) {
			$classes[] = 'anishop-buy-box-three-col';
			// Stage 4: the desktop sticky Buy Box (only in three-column mode).
			if ( ! empty( $config['desktop_sticky_buy_box'] ) ) {
				$classes[] = 'anishop-buy-box-sticky';
			}
		} else {
			$classes[] = 'anishop-buy-box-inline';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'anishop_buy_box_body_class' );

/**
 * Enqueue the Buy Box frontend assets (CSS + JS) only when active.
 *
 * @return void
 */
function anishop_buy_box_frontend_enqueue() {
	if ( ! anishop_buy_box_is_active() ) {
		return;
	}
	$version = wp_get_theme( get_template() )->get( 'Version' );
	wp_enqueue_style( 'anishop-buy-box', get_stylesheet_directory_uri() . '/assets/css/anishop-buy-box.css', array(), $version );
	wp_enqueue_script( 'anishop-buy-box', get_stylesheet_directory_uri() . '/assets/js/anishop-buy-box.js', array( 'jquery' ), $version, true );
	$config = anishop_buy_box_get_config();
	wp_localize_script(
		'anishop-buy-box',
		'anishopBuyBox',
		array(
			'enabled'           => true,
			'desktopBuyBox'     => ! empty( $config['desktop_buy_box'] ),
			'threeColumnLayout' => ! empty( $config['three_column_layout'] ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'anishop_buy_box_frontend_enqueue' );

/**
 * Render the mobile-only sticky buy bar (Stage 5).
 *
 * A compact fixed bar (price + trigger) pinned to the bottom of the viewport
 * on mobile, so the price and the add-to-cart action stay reachable while the
 * user scrolls the product page (where the full Buy Box sits far down the
 * normal document flow).
 *
 * Architecture (extends the Buy Box module — no parallel purchase path):
 *  - The bar is a MIRROR of the Buy Box, which is itself a mirror of the
 *    authoritative native state. It never recomputes price/stock.
 *  - Its button drives the SAME single native WooCommerce form via the same
 *    `requestSubmit( nativeEl )` path as the Buy Box trigger (see
 *    anishop-buy-box.js). There is no second form and no second source of
 *    truth.
 *  - Rendered in `wp_footer` (AFTER the mobile navbar's priority 20) so the
 *    bar is a top-level element, NOT nested inside the product — this keeps
 *    its `position: fixed` anchored to the viewport even if a product ancestor
 *    has a `transform`/`filter`. The CSS positions it above the mobile navbar
 *    (see anishop-buy-box.css) and shows it only on mobile (<992px) when the
 *    Buy Box is active and the JS has wired it.
 *
 * Feature-off guarantee: when `enabled=false`, the Theme Mod is absent, or
 * `mobile_sticky_buy_bar` is off, this renders nothing.
 *
 * @return void
 */
function anishop_buy_box_render_mobile_bar() {
	if ( ! anishop_buy_box_is_active() ) {
		return;
	}
	$config = anishop_buy_box_get_config();
	if ( empty( $config['mobile_sticky_buy_bar'] ) ) {
		return;
	}

	$product_id = get_the_ID();
	if ( ! $product_id ) {
		return;
	}
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return;
	}

	$is_variable  = $product->is_type( 'variable' );
	$price_html   = $product->get_price_html();
	$purchasable  = $product->is_purchasable();
	$trigger_text = $is_variable ? __( 'Choose options', 'anishop' ) : __( 'Add to cart', 'anishop' );
	?>
	<div class="anishop-buy-box-mobile-bar" data-product_id="<?php echo esc_attr( $product_id ); ?>" data-product_type="<?php echo esc_attr( $product->get_type() ); ?>">
		<span class="anishop-buy-box-mobile-bar__price price"><?php echo wp_kses_post( $price_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce price HTML (sanitized by WC). ?></span>
		<button type="button" class="anishop-buy-box-mobile-bar__trigger button alt"<?php echo $purchasable ? '' : ' disabled'; ?>><?php echo esc_html( $trigger_text ); ?></button>
	</div>
	<?php
}
add_action( 'wp_footer', 'anishop_buy_box_render_mobile_bar', 25 );

