<?php
/**
 * Anishop Search Box — admin.
 *
 * Phase 3.1 (module structure + dashboard integration):
 *  - Registers the "Search Box" tab in the Appearance → Anishop tab
 *    navigation via the `anishop_admin_tabs` filter (Buy Box precedent).
 *
 * Phase 3.2 (settings form, persistence & validation):
 *  - The settings form: all 20 approved settings (Phase 2 §4.2) grouped in
 *    General / Appearance / Live results, rendered with native inputs
 *    (no admin JS).
 *  - The nonce-protected save + reset handlers (POST → `edit_theme_options`
 *    → action field → `wp_verify_nonce` → config-layer save/reset →
 *    `wp_safe_redirect` with a notice).
 *
 * All submitted data is passed through `anishop_search_box_sanitize()` (the
 * single validation authority in config.php). The Theme Mod
 * `anishop_search_box` is written ONLY through `anishop_search_box_save_config()`
 * / `anishop_search_box_reset_config()`.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config.php';

/**
 * The admin page slug (Appearance → Anishop) — owned by the mobile-nav module.
 * The Search Box tab lives on this same page, so its handlers hook the same
 * `load-` action.
 */
define( 'ANISHOP_SEARCH_BOX_ADMIN_SLUG', 'anishop-mobile-nav' );

/**
 * Handle the Search Box save + reset POST actions (nonce-protected).
 *
 * Flow: POST → capability check → action field → nonce check →
 * configuration-layer save/reset (single sanitizer inside) → redirect back
 * with a notice.
 *
 * Hooked to the same `load-` action as the page owner (the mobile-nav
 * module), so it runs when the Appearance → Anishop page loads.
 *
 * @return void
 */
function anishop_search_box_admin_handle_actions() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$redirect = add_query_arg(
		array(
			'page' => ANISHOP_SEARCH_BOX_ADMIN_SLUG,
			'tab'  => ANISHOP_SEARCH_BOX_TAB,
		),
		admin_url( 'themes.php' )
	);

	// Reset to defaults (removes the Theme Mod via the configuration layer).
	if ( isset( $_POST['anishop_search_box_reset'] ) ) {
		$nonce = isset( $_POST['anishop_search_box_reset_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['anishop_search_box_reset_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'anishop_search_box_reset' ) ) {
			wp_die( esc_html__( 'The link you followed has expired.', 'anishop' ) );
		}
		anishop_search_box_reset_config();
		wp_safe_redirect( add_query_arg( 'anishop_sb_notice', 'reset', $redirect ) );
		exit;
	}

	// Save.
	if ( isset( $_POST['anishop_search_box_save'] ) ) {
		$nonce = isset( $_POST['anishop_search_box_save_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['anishop_search_box_save_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'anishop_search_box_save' ) ) {
			wp_die( esc_html__( 'The link you followed has expired.', 'anishop' ) );
		}

		$raw = ( isset( $_POST['anishop_search_box'] ) && is_array( $_POST['anishop_search_box'] ) )
			? wp_unslash( $_POST['anishop_search_box'] )
			: array();

		// The standard hidden(0) + checkbox(1) pair submits the last value
		// (checked → `1`, unchecked → `0`); the sanitizer's bool coercion
		// also handles a crafted duplicate-field array.
		anishop_search_box_save_config( $raw );

		wp_safe_redirect( add_query_arg( 'anishop_sb_notice', 'saved', $redirect ) );
		exit;
	}
}
add_action( 'load-appearance_page_' . ANISHOP_SEARCH_BOX_ADMIN_SLUG, 'anishop_search_box_admin_handle_actions' );

/**
 * Add the Search Box tab to the Anishop admin tab navigation.
 *
 * Hooked to the `anishop_admin_tabs` filter (applied in
 * anishop_iv_admin_render_tab_nav()). This keeps the tab list extensible
 * without modifying the existing tabs.
 *
 * @param array $tabs The existing tab map (tab id => label).
 * @return array
 */
function anishop_search_box_admin_add_tab( $tabs ) {
	$tabs[ ANISHOP_SEARCH_BOX_TAB ] = __( 'Search Box', 'anishop' );
	return $tabs;
}
add_filter( 'anishop_admin_tabs', 'anishop_search_box_admin_add_tab' );

/**
 * Render the Search Box tab content (the Phase 3.2 settings form).
 *
 * Called from the shared Anishop admin page (inc/anishop-mobile-nav/admin.php).
 * All 20 approved settings (Phase 2 §4.2) are rendered with native inputs,
 * grouped in General / Appearance / Live results. All output is escaped.
 *
 * @return void
 */
function anishop_search_box_admin_render_tab() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$config = anishop_search_box_get_config();
	$notice = isset( $_GET['anishop_sb_notice'] ) ? sanitize_key( wp_unslash( $_GET['anishop_sb_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.
	?>
	<div class="anishop-search-box-admin">
		<?php if ( 'saved' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Search Box settings saved.', 'anishop' ); ?></p></div>
		<?php elseif ( 'reset' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Search Box reset to defaults.', 'anishop' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="">
			<?php
			wp_nonce_field( 'anishop_search_box_save', 'anishop_search_box_save_nonce' );
			wp_nonce_field( 'anishop_search_box_reset', 'anishop_search_box_reset_nonce' );
			?>

			<fieldset class="anishop-search-box-admin__fieldset">
				<legend><?php esc_html_e( 'General', 'anishop' ); ?></legend>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Search Box', 'anishop' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="anishop_search_box[enabled]" value="0" />
								<input type="checkbox" name="anishop_search_box[enabled]" value="1" <?php checked( ! empty( $config['enabled'] ) ); ?> />
								<?php esc_html_e( 'Enable the search box in the site header. This is the master switch — the settings below have no effect until this is on.', 'anishop' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Placeholder', 'anishop' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="anishop_search_box[placeholder]" value="<?php echo esc_attr( $config['placeholder'] ); ?>" />
							<p class="description"><?php esc_html_e( 'The text shown inside the empty search input. Leave empty to use the Blocksy default.', 'anishop' ); ?></p>
						</td>
					</tr>
				</table>
			</fieldset>

			<fieldset class="anishop-search-box-admin__fieldset">
				<legend><?php esc_html_e( 'Appearance', 'anishop' ); ?></legend>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Height (px)', 'anishop' ); ?></th>
						<td>
							<input type="number" name="anishop_search_box[height]" value="<?php echo esc_attr( $config['height'] ); ?>" min="1" max="100" step="1" />
							<p class="description"><?php esc_html_e( 'The height of the search input.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Max width (px)', 'anishop' ); ?></th>
						<td>
							<input type="number" name="anishop_search_box[max_width]" value="<?php echo esc_attr( $config['max_width'] ); ?>" min="1" max="800" step="1" />
							<p class="description"><?php esc_html_e( 'The maximum width of the search box. The box never overflows its container.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Dropdown width (px)', 'anishop' ); ?></th>
						<td>
							<input type="number" name="anishop_search_box[dropdown_width]" value="<?php echo esc_attr( $config['dropdown_width'] ); ?>" min="320" max="720" step="1" />
							<p class="description"><?php esc_html_e( 'The width of the live-results dropdown on desktop (two-column layout). It does not change the search input width. On mobile the dropdown stays one column and fits the screen.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Mobile dropdown top (px)', 'anishop' ); ?></th>
						<td>
							<input type="number" name="anishop_search_box[mobile_dropdown_top]" value="<?php echo esc_attr( $config['mobile_dropdown_top'] ); ?>" min="0" max="500" step="1" />
							<p class="description"><?php esc_html_e( 'The distance from the top of the page to the top of the search dropdown on mobile. Match it to the full header height so the dropdown opens exactly below the header.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Mobile dropdown side margin (px)', 'anishop' ); ?></th>
						<td>
							<input type="number" name="anishop_search_box[mobile_dropdown_side_margin]" value="<?php echo esc_attr( $config['mobile_dropdown_side_margin'] ); ?>" min="8" max="24" step="1" />
							<p class="description"><?php esc_html_e( 'The left and right margin of the search dropdown on mobile. Range: 8–24 px.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Background color', 'anishop' ); ?></th>
						<td>
							<input type="color" name="anishop_search_box[bg_color]" value="<?php echo esc_attr( $config['bg_color'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Text color', 'anishop' ); ?></th>
						<td>
							<input type="color" name="anishop_search_box[text_color]" value="<?php echo esc_attr( $config['text_color'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Placeholder color', 'anishop' ); ?></th>
						<td>
							<input type="color" name="anishop_search_box[placeholder_color]" value="<?php echo esc_attr( $config['placeholder_color'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Border color', 'anishop' ); ?></th>
						<td>
							<input type="color" name="anishop_search_box[border_color]" value="<?php echo esc_attr( $config['border_color'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Border width (px)', 'anishop' ); ?></th>
						<td>
							<input type="number" name="anishop_search_box[border_width]" value="<?php echo esc_attr( $config['border_width'] ); ?>" min="0" max="10" step="1" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Corner radius (px)', 'anishop' ); ?></th>
						<td>
							<input type="number" name="anishop_search_box[radius]" value="<?php echo esc_attr( $config['radius'] ); ?>" min="0" max="50" step="1" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Focus color', 'anishop' ); ?></th>
						<td>
							<input type="color" name="anishop_search_box[focus_color]" value="<?php echo esc_attr( $config['focus_color'] ); ?>" />
							<p class="description"><?php esc_html_e( 'The border color when the input is focused.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Icon size (px)', 'anishop' ); ?></th>
						<td>
							<input type="number" name="anishop_search_box[icon_size]" value="<?php echo esc_attr( $config['icon_size'] ); ?>" min="1" max="40" step="1" />
						</td>
					</tr>
				</table>
			</fieldset>

			<fieldset class="anishop-search-box-admin__fieldset">
				<legend><?php esc_html_e( 'Live results', 'anishop' ); ?></legend>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable live results', 'anishop' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="anishop_search_box[live_results]" value="0" />
								<input type="checkbox" name="anishop_search_box[live_results]" value="1" <?php checked( ! empty( $config['live_results'] ) ); ?> />
								<?php esc_html_e( 'Show live product results while typing.', 'anishop' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show thumbnails', 'anishop' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="anishop_search_box[show_thumbs]" value="0" />
								<input type="checkbox" name="anishop_search_box[show_thumbs]" value="1" <?php checked( ! empty( $config['show_thumbs'] ) ); ?> />
								<?php esc_html_e( 'Show the product thumbnail in the results.', 'anishop' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show price', 'anishop' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="anishop_search_box[show_price]" value="0" />
								<input type="checkbox" name="anishop_search_box[show_price]" value="1" <?php checked( ! empty( $config['show_price'] ) ); ?> />
								<?php esc_html_e( 'Show the product price (and sale price) in the results.', 'anishop' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Show stock status', 'anishop' ); ?></th>
						<td>
							<label>
								<input type="hidden" name="anishop_search_box[show_status]" value="0" />
								<input type="checkbox" name="anishop_search_box[show_status]" value="1" <?php checked( ! empty( $config['show_status'] ) ); ?> />
								<?php esc_html_e( 'Show the stock status in the results.', 'anishop' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'In stock text', 'anishop' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="anishop_search_box[instock_text]" value="<?php echo esc_attr( $config['instock_text'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Custom text for in-stock products. Leave empty to use the Blocksy default.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Out of stock text', 'anishop' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="anishop_search_box[outofstock_text]" value="<?php echo esc_attr( $config['outofstock_text'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Custom text for out-of-stock products. Leave empty to use the Blocksy default.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'No results text', 'anishop' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="anishop_search_box[no_result_text]" value="<?php echo esc_attr( $config['no_result_text'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Custom text when the search has no results. Leave empty to use the Blocksy default.', 'anishop' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Error text', 'anishop' ); ?></th>
						<td>
							<input type="text" class="regular-text" name="anishop_search_box[error_text]" value="<?php echo esc_attr( $config['error_text'] ); ?>" />
							<p class="description"><?php esc_html_e( 'Custom text shown when a search request fails. Leave empty to use the Blocksy default.', 'anishop' ); ?></p>
						</td>
					</tr>
				</table>
			</fieldset>

			<p class="submit">
				<button type="submit" name="anishop_search_box_save" value="1" class="button button-primary"><?php esc_html_e( 'Save Changes', 'anishop' ); ?></button>
				<button type="submit" name="anishop_search_box_reset" value="1" class="button button-link-delete"><?php esc_html_e( 'Reset to Defaults', 'anishop' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}
