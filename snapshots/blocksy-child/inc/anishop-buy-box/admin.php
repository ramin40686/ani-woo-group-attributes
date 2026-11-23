<?php
/**
 * Anishop Buy Box — admin (Stage 2: settings form + save).
 *
 * Three-Column Buy Box project, Stage 2: the admin SURFACE for the Buy Box
 * feature. This file hosts:
 *
 *  - the "Buy Box Container" tab, added to the Appearance → Anishop tab
 *    navigation through the existing extensible `anishop_admin_tabs` filter
 *    (the same mechanism Key Features uses) — so the tab-nav function itself
 *    is NOT modified;
 *  - the Stage 2 settings form: the master `enabled` switch + the
 *    `three_column_layout` / `desktop_buy_box` controls + a Save button;
 *  - the nonce-protected save handler (POST → `edit_theme_options` → POST
 *    field → `wp_verify_nonce` → `anishop_buy_box_save_config()` → redirect).
 *
 * Stage 2/3/4/5 scope (important):
 *  - The controls exposed: `enabled`, `three_column_layout`,
 *    `desktop_buy_box`, `desktop_sticky_buy_box` (Stage 4), and
 *    `mobile_sticky_buy_bar` (Stage 5). The 60/40 width ratio is a fixed
 *    design value (not a control).
 *  - All submitted data is passed through `anishop_buy_box_sanitize()` (the
 *    single validation authority in config.php). The Theme Mod
 *    `anishop_buy_box` is written ONLY through `anishop_buy_box_save_config()`.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config.php';

/**
 * The admin page slug (Appearance → Anishop) — owned by the mobile-nav module.
 * The Buy Box tab lives on this same page, so its save handler hooks the same
 * `load-` action.
 */
define( 'ANISHOP_BUY_BOX_ADMIN_SLUG', 'anishop-mobile-nav' );

/**
 * Handle the Buy Box save action (nonce-protected).
 *
 * Flow: POST → capability check → nonce check → configuration-layer
 * validation (inside save_config) → redirect back with a notice.
 *
 * Hooked to the same `load-` action as the page owner (the mobile-nav module),
 * so it runs when the Appearance → Anishop page loads.
 *
 * @return void
 */
function anishop_buy_box_admin_handle_actions() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( ! isset( $_POST['anishop_buy_box_save'] ) ) {
		return;
	}

	$nonce = isset( $_POST['anishop_buy_box_save_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['anishop_buy_box_save_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'anishop_buy_box_save' ) ) {
		wp_die( esc_html__( 'The link you followed has expired.', 'anishop' ) );
	}

	$raw = ( isset( $_POST['anishop_buy_box'] ) && is_array( $_POST['anishop_buy_box'] ) )
		? wp_unslash( $_POST['anishop_buy_box'] )
		: array();

	// The standard hidden(0) + checkbox(1) pair can submit the same field twice
	// (an array); the sanitizer's bool coercion handles both shapes.
	anishop_buy_box_save_config( $raw );

	$redirect = add_query_arg(
		array(
			'page'                => ANISHOP_BUY_BOX_ADMIN_SLUG,
			'tab'                 => ANISHOP_BUY_BOX_TAB,
			'anishop_bb_notice'   => 'saved',
		),
		admin_url( 'themes.php' )
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'load-appearance_page_' . ANISHOP_BUY_BOX_ADMIN_SLUG, 'anishop_buy_box_admin_handle_actions' );

/**
 * The tab identifier for the Buy Box Container tab.
 */
define( 'ANISHOP_BUY_BOX_TAB', 'buy-box' );

/**
 * Add the Buy Box Container tab to the Anishop admin tab navigation.
 *
 * Hooked to the `anishop_admin_tabs` filter (applied in
 * anishop_iv_admin_render_tab_nav()). This keeps the tab list extensible
 * without modifying the existing Mobile Bottom Navbar / Display Variations
 * Inline / Key Features tabs.
 *
 * @param array $tabs The existing tab map (tab id => label).
 * @return array
 */
function anishop_buy_box_admin_add_tab( $tabs ) {
	$tabs[ ANISHOP_BUY_BOX_TAB ] = __( 'Buy Box Container', 'anishop' );
	return $tabs;
}
add_filter( 'anishop_admin_tabs', 'anishop_buy_box_admin_add_tab' );

/**
 * Render a single Buy Box row in the admin repeater (used for existing rows
 * and the hidden <template> row).
 *
 * Shared by the global rows (in the «کانتینر Buy Box» tab) and the
 * product-specific rows (in the product-edit Advanced tab).
 *
 * @param int|string $index       The row index (or `__INDEX__` for the template).
 * @param array      $row         The row `{ enabled, title, text, link, icon }`.
 * @param string     $base        The field-name base (e.g. `anishop_buy_box[rows]`).
 * @param bool       $is_template Whether this is the hidden <template> row.
 * @return void
 */
function anishop_buy_box_admin_row( $index, $row, $base, $is_template = false ) {
	$enabled = ! empty( $row['enabled'] );
	$title   = isset( $row['title'] ) ? $row['title'] : '';
	$text    = isset( $row['text'] ) ? $row['text'] : '';
	$link    = isset( $row['link'] ) ? $row['link'] : '';
	$icon    = isset( $row['icon'] ) ? $row['icon'] : 'none';
	$name    = $base . '[' . $index . ']';
	$state   = ( $is_template || $enabled ) ? '' : ' anishop-bb-admin__row--disabled';
	?>
	<li class="anishop-bb-admin__row<?php echo esc_attr( $state ); ?>" data-anishop-bb-item>
		<div class="anishop-bb-admin__row-fields">
			<label class="anishop-bb-admin__field anishop-bb-admin__field--enabled">
				<span><?php esc_html_e( 'Enabled', 'anishop' ); ?></span>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
				<input type="checkbox" class="anishop-bb-admin__enabled" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $enabled ); ?>>
			</label>
			<label class="anishop-bb-admin__field anishop-bb-admin__field--title">
				<span><?php esc_html_e( 'Title', 'anishop' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $title ); ?>">
			</label>
			<label class="anishop-bb-admin__field anishop-bb-admin__field--text">
				<span><?php esc_html_e( 'Text', 'anishop' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[text]" value="<?php echo esc_attr( $text ); ?>">
			</label>
			<label class="anishop-bb-admin__field anishop-bb-admin__field--link">
				<span><?php esc_html_e( 'Link (URL)', 'anishop' ); ?></span>
				<input type="url" name="<?php echo esc_attr( $name ); ?>[link]" value="<?php echo esc_attr( $link ); ?>">
			</label>
			<label class="anishop-bb-admin__field anishop-bb-admin__field--icon">
				<span><?php esc_html_e( 'Icon', 'anishop' ); ?></span>
				<select name="<?php echo esc_attr( $name ); ?>[icon]">
					<?php foreach ( anishop_buy_box_icon_keys() as $key ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon, $key ); ?>><?php echo esc_html( $key ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
		</div>
		<div class="anishop-bb-admin__row-actions">
			<button type="button" class="button anishop-bb-admin__move" data-direction="up" aria-label="<?php esc_attr_e( 'Move up', 'anishop' ); ?>">&uarr;</button>
			<button type="button" class="button anishop-bb-admin__move" data-direction="down" aria-label="<?php esc_attr_e( 'Move down', 'anishop' ); ?>">&darr;</button>
			<button type="button" class="button anishop-bb-admin__remove"><?php esc_html_e( 'Remove', 'anishop' ); ?></button>
		</div>
	</li>
	<?php
}

/**
 * Render the Buy Box rows repeater (a list of rows + add button + template).
 *
 * Shared by the global rows (in the «کانتینر Buy Box» tab) and the
 * product-specific rows (in the product-edit Advanced tab).
 *
 * @param array  $rows The list of rows to render.
 * @param string $base The field-name base (e.g. `anishop_buy_box[rows]`).
 * @return void
 */
function anishop_buy_box_admin_rows_repeater( $rows, $base ) {
	$empty_row = array(
		'enabled' => true,
		'title'   => '',
		'text'    => '',
		'link'    => '',
		'icon'    => 'none',
	);
	?>
	<div class="anishop-bb-rows-admin">
		<ul class="anishop-bb-rows-admin__list" data-anishop-bb-rows data-anishop-bb-rows-base="<?php echo esc_attr( $base ); ?>">
			<?php if ( empty( $rows ) ) : ?>
				<li class="anishop-bb-rows-admin__empty"><?php esc_html_e( 'No rows yet. Add one below.', 'anishop' ); ?></li>
			<?php else : ?>
				<?php foreach ( $rows as $index => $row ) : ?>
					<?php anishop_buy_box_admin_row( $index, $row, $base ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
		<p class="anishop-bb-rows-admin__actions">
			<button type="button" class="button anishop-bb-admin__add"><?php esc_html_e( 'Add row', 'anishop' ); ?></button>
		</p>
		<template data-anishop-bb-template>
			<?php anishop_buy_box_admin_row( '__INDEX__', $empty_row, $base, true ); ?>
		</template>
	</div>
	<?php
}

/**
 * Enqueue the Buy Box rows repeater admin script on the Anishop admin page.
 *
 * The product-edit screen enqueues the same script from product-rows.php.
 *
 * @param string $hook The current admin page hook.
 * @return void
 */
function anishop_buy_box_admin_enqueue( $hook ) {
	if ( 'appearance_page_' . ANISHOP_BUY_BOX_ADMIN_SLUG !== $hook ) {
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
add_action( 'admin_enqueue_scripts', 'anishop_buy_box_admin_enqueue' );

/**
 * Render the Buy Box Container tab (Stage 2 settings + Stage 3 global rows +
 * Stage 4 desktop sticky + Stage 5 mobile sticky bar).
 *
 * Shows the master `enabled` switch + the `three_column_layout` /
 * `desktop_buy_box` / `desktop_sticky_buy_box` / `mobile_sticky_buy_bar`
 * controls + the Stage 3 row appearance (style + color) + the global rows
 * repeater + a Save button.
 *
 * All output is escaped. The repeater styles live in the external stylesheet
 * `assets/css/anishop-buy-box-admin.css` (enqueued by
 * `anishop_buy_box_admin_enqueue()`); they use only logical /
 * direction-agnostic properties (works in both RTL and LTR).
 *
 * @return void
 */
function anishop_buy_box_admin_render_tab() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$config = anishop_buy_box_get_config();
	$notice = isset( $_GET['anishop_bb_notice'] ) ? sanitize_key( wp_unslash( $_GET['anishop_bb_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.
	?>
	<div class="anishop-buy-box-admin">
		<?php if ( 'saved' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Buy Box settings saved.', 'anishop' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'anishop_buy_box_save', 'anishop_buy_box_save_nonce' ); ?>

			<?php
			$label_enabled   = __( 'Enable Buy Box', 'anishop' );
			$label_three_col = __( 'Three-column layout', 'anishop' );
			$label_desktop   = __( 'Desktop Buy Box', 'anishop' );
			?>
			<div class="anishop-buy-box-admin__intro">
				<p><?php echo esc_html( __( 'The Buy Box is a price/availability mirror plus a trigger button that drives the native WooCommerce form. The native form — its variation selectors, quantity input, validation, and add-to-cart submission — is preserved untouched and remains the only real purchase path. The settings below control the Buy Box presentation only.', 'anishop' ) ); ?></p>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo esc_html( $label_enabled ); ?></th>
					<td>
						<label>
							<input type="hidden" name="anishop_buy_box[enabled]" value="0" />
							<input type="checkbox" name="anishop_buy_box[enabled]" value="1" <?php checked( ! empty( $config['enabled'] ) ); ?> />
							<?php echo esc_html( __( 'Enable the Buy Box on single product pages. This is the master switch — the settings below have no effect until this is on.', 'anishop' ) ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html( $label_three_col ); ?></th>
					<td>
						<label>
							<input type="hidden" name="anishop_buy_box[three_column_layout]" value="0" />
							<input type="checkbox" name="anishop_buy_box[three_column_layout]" value="1" <?php checked( ! empty( $config['three_column_layout'] ) ); ?> />
							<?php echo esc_html( __( 'On desktop, place the Buy Box in its own column (the third column) beside the information column (60/40). When off, it appears below the product information. Requires "Desktop Buy Box".', 'anishop' ) ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html( $label_desktop ); ?></th>
					<td>
						<label>
							<input type="hidden" name="anishop_buy_box[desktop_buy_box]" value="0" />
							<input type="checkbox" name="anishop_buy_box[desktop_buy_box]" value="1" <?php checked( ! empty( $config['desktop_buy_box'] ) ); ?> />
							<?php echo esc_html( __( 'Enable the desktop Buy Box. Prerequisite for the desktop layout; hides the native price to avoid duplication. To use the third column, also enable "Three-column layout".', 'anishop' ) ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Desktop Sticky Buy Box', 'anishop' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="anishop_buy_box[desktop_sticky_buy_box]" value="0" />
							<input type="checkbox" name="anishop_buy_box[desktop_sticky_buy_box]" value="1" <?php checked( ! empty( $config['desktop_sticky_buy_box'] ) ); ?> />
							<?php echo esc_html( __( 'On desktop, keep the Buy Box visible while scrolling the product page (it sticks near the top of the viewport). Requires "Three-column layout" and "Desktop Buy Box".', 'anishop' ) ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mobile Sticky Buy Bar', 'anishop' ); ?></th>
					<td>
						<label>
							<input type="hidden" name="anishop_buy_box[mobile_sticky_buy_bar]" value="0" />
							<input type="checkbox" name="anishop_buy_box[mobile_sticky_buy_bar]" value="1" <?php checked( ! empty( $config['mobile_sticky_buy_bar'] ) ); ?> />
							<?php echo esc_html( __( 'On mobile, keep a compact price + add-to-cart bar pinned to the bottom of the screen while scrolling the product page. It reuses the native purchase flow and sits above the mobile bottom navigation when that is enabled.', 'anishop' ) ); ?>
						</label>
					</td>
				</tr>
			</table>

			<fieldset class="anishop-buy-box-admin__rows">
				<legend><?php esc_html_e( 'Global Buy Box rows', 'anishop' ); ?></legend>
				<p class="description"><?php echo esc_html( __( 'Reusable assurance rows shown at the BOTTOM of the Buy Box (below the add-to-cart trigger) on every product. Product-specific rows (set on each product) appear at the TOP, above the price.', 'anishop' ) ); ?></p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Row style', 'anishop' ); ?></th>
						<td>
							<select name="anishop_buy_box[row_style]">
								<option value="soft" <?php selected( $config['row_style'], 'soft' ); ?>><?php esc_html_e( 'Soft (tinted background)', 'anishop' ); ?></option>
								<option value="solid" <?php selected( $config['row_style'], 'solid' ); ?>><?php esc_html_e( 'Solid (filled background)', 'anishop' ); ?></option>
								<option value="outline" <?php selected( $config['row_style'], 'outline' ); ?>><?php esc_html_e( 'Outline (border only)', 'anishop' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Row color', 'anishop' ); ?></th>
						<td>
							<input type="color" name="anishop_buy_box[row_color]" value="<?php echo esc_attr( $config['row_color'] ); ?>">
							<p class="description"><?php echo esc_html( __( 'The background/accent color applied to all rows (global and product-specific).', 'anishop' ) ); ?></p>
						</td>
					</tr>
				</table>

				<?php anishop_buy_box_admin_rows_repeater( $config['rows'], 'anishop_buy_box[rows]' ); ?>
			</fieldset>

			<?php submit_button( __( 'Save Changes', 'anishop' ), 'primary', 'anishop_buy_box_save' ); ?>
		</form>
	</div>
	<?php
}

