<?php
/**
 * Anishop mobile bottom navigation — admin.
 *
 * B6-15 Part 2, Step 2: native WordPress admin UI for configuring the mobile
 * bottom navigation.
 *
 *  - Location: Appearance → Anishop (page title: "Mobile Bottom Navbar").
 *  - Enable/disable, item editor (label / type / icon / URL), ↑/↓ reordering,
 *    add/remove custom link items, static preview, save + reset.
 *
 * The Step-1 configuration layer (config.php) is the single authority:
 *  - all submitted data is passed through anishop_mobile_nav_sanitize(),
 *  - the Theme Mod `anishop_mobile_nav` is only written through
 *    anishop_mobile_nav_save_config() / anishop_mobile_nav_reset_config().
 *
 * Step 2 scope (important):
 *  - The renderer (render.php) is NOT modified and does NOT read this
 *    configuration yet. Runtime integration is Step 3.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// B6-15 Icon Assets Discovery PoC: read-only discovery of project SVG icons
// (admin-only; the discovery functions are used only by the icon picker below).
require_once __DIR__ . '/icon-assets.php';

/**
 * The admin page slug (Appearance → Anishop).
 */
define( 'ANISHOP_MOBILE_NAV_ADMIN_SLUG', 'anishop-mobile-nav' );

/**
 * The five system item IDs. Their IDs are fixed, they cannot be removed,
 * and their type is not editable in the UI.
 *
 * @return string[]
 */
function anishop_mobile_nav_admin_system_ids() {
	return array( 'home', 'categories', 'cart', 'blog', 'account' );
}

/**
 * Human-readable label for a supported item type.
 *
 * @param string $type Item type.
 * @return string
 */
function anishop_mobile_nav_admin_type_label( $type ) {
	$labels = array(
		'home'    => __( 'Home', 'anishop' ),
		'shop'    => __( 'Shop / Categories', 'anishop' ),
		'cart'    => __( 'Cart', 'anishop' ),
		'account' => __( 'Account', 'anishop' ),
		'blog'    => __( 'Blog', 'anishop' ),
		'link'    => __( 'Custom Link', 'anishop' ),
	);
	return isset( $labels[ $type ] ) ? $labels[ $type ] : $type;
}

/**
 * Register the admin page under Appearance.
 *
 * @return void
 */
function anishop_mobile_nav_admin_register_page() {
	$hook = add_submenu_page(
		'themes.php',
		__( 'Mobile Bottom Navbar', 'anishop' ),
		__( 'Anishop', 'anishop' ),
		'edit_theme_options',
		ANISHOP_MOBILE_NAV_ADMIN_SLUG,
		'anishop_mobile_nav_admin_page'
	);

	add_action( 'load-' . $hook, 'anishop_mobile_nav_admin_handle_actions' );
}
add_action( 'admin_menu', 'anishop_mobile_nav_admin_register_page' );

/**
 * Set the browser-tab title to match the active Anishop settings tab.
 *
 * The `add_submenu_page()` page title is fixed to "Mobile Bottom Navbar", so
 * the browser tab would otherwise stay on that title even when the Display
 * Variations Inline tab is active. This filter updates the `<title>` based on
 * the active tab (read from `$_GET['tab']`).
 *
 * @param string $admin_title The fully formatted admin title.
 * @param string $title       The original page title.
 * @return string
 */
function anishop_mobile_nav_admin_title( $admin_title, $title ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'appearance_page_' . ANISHOP_MOBILE_NAV_ADMIN_SLUG !== $screen->id ) {
		return $admin_title;
	}

	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, used only to set the title.
	if ( 'inline-variations' === $tab ) {
		$new_title = __( 'Display Variations Inline', 'anishop' );
	} elseif ( defined( 'ANISHOP_KF_TAB' ) && ANISHOP_KF_TAB === $tab ) {
		$new_title = __( 'Key Features', 'anishop' );
	} elseif ( defined( 'ANISHOP_BUY_BOX_TAB' ) && ANISHOP_BUY_BOX_TAB === $tab ) {
		$new_title = __( 'Buy Box Container', 'anishop' );
	} elseif ( defined( 'ANISHOP_SEARCH_BOX_TAB' ) && ANISHOP_SEARCH_BOX_TAB === $tab ) {
		$new_title = __( 'Search Box', 'anishop' );
	} else {
		$new_title = __( 'Mobile Bottom Navbar', 'anishop' );
	}

	if ( '' !== $title && $title !== $new_title && false !== strpos( $admin_title, $title ) ) {
		$admin_title = str_replace( $title, $new_title, $admin_title );
	}

	return $admin_title;
}
add_filter( 'admin_title', 'anishop_mobile_nav_admin_title', 10, 2 );

/**
 * Enqueue the admin assets — only on this page.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function anishop_mobile_nav_admin_enqueue( $hook ) {
	if ( 'appearance_page_' . ANISHOP_MOBILE_NAV_ADMIN_SLUG !== $hook ) {
		return;
	}

	$theme = wp_get_theme();
	$css   = get_stylesheet_directory() . '/assets/css/anishop-mobile-nav-admin.css';
	$js    = get_stylesheet_directory() . '/assets/js/anishop-mobile-nav-admin.js';

	wp_enqueue_style(
		'anishop-mobile-nav-admin',
		get_stylesheet_directory_uri() . '/assets/css/anishop-mobile-nav-admin.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : $theme->get( 'Version' )
	);

	wp_enqueue_script(
		'anishop-mobile-nav-admin',
		get_stylesheet_directory_uri() . '/assets/js/anishop-mobile-nav-admin.js',
		array(),
		file_exists( $js ) ? (string) filemtime( $js ) : $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'anishop-mobile-nav-admin',
		'anishopMobileNavAdmin',
		array(
			'i18n' => array(
				'confirmRemove' => __( 'Remove this item?', 'anishop' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'anishop_mobile_nav_admin_enqueue' );

/**
 * Handle the save and reset POST actions for this page.
 *
 * Flow: POST → capability check → nonce check → configuration-layer
 * validation (inside save_config) → redirect back with a notice.
 *
 * @return void
 */
function anishop_mobile_nav_admin_handle_actions() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$redirect = add_query_arg(
		array( 'page' => ANISHOP_MOBILE_NAV_ADMIN_SLUG ),
		admin_url( 'themes.php' )
	);

	// Reset to defaults (removes the Theme Mod via the configuration layer).
	if ( isset( $_POST['anishop_mobile_nav_reset'] ) ) {
		$nonce = isset( $_POST['anishop_mnav_reset_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['anishop_mnav_reset_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'anishop_mobile_nav_reset' ) ) {
			wp_die( esc_html__( 'The link you followed has expired.', 'anishop' ) );
		}
		anishop_mobile_nav_reset_config();
		wp_safe_redirect( add_query_arg( 'anishop_mnav_notice', 'reset', $redirect ) );
		exit;
	}

	// Save.
	if ( isset( $_POST['anishop_mobile_nav_save'] ) ) {
		$nonce = isset( $_POST['anishop_mnav_save_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['anishop_mnav_save_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'anishop_mobile_nav_save' ) ) {
			wp_die( esc_html__( 'The link you followed has expired.', 'anishop' ) );
		}

		$raw = ( isset( $_POST['anishop_mobile_nav'] ) && is_array( $_POST['anishop_mobile_nav'] ) )
			? wp_unslash( $_POST['anishop_mobile_nav'] )
			: array();

		$raw = anishop_mobile_nav_admin_normalize_post( $raw );

		$submitted_items = ( isset( $raw['items'] ) && is_array( $raw['items'] ) ) ? count( $raw['items'] ) : 0;
		$saved           = anishop_mobile_nav_save_config( $raw );

		// If the configuration layer rejected items, tell the admin.
		$notice = ( $submitted_items > count( $saved['items'] ) ) ? 'saved_invalid' : 'saved';
		wp_safe_redirect( add_query_arg( 'anishop_mnav_notice', $notice, $redirect ) );
		exit;
	}
}

/**
 * Normalize the raw POST shape before handing it to the configuration layer.
 *
 * The enable/disable controls use the standard hidden(0) + checkbox(1) pair,
 * so a checked box submits the same field twice (an array). This only
 * reshapes the form data (last value wins); all validation/sanitization is
 * still performed exclusively by anishop_mobile_nav_sanitize().
 *
 * @param array $raw Raw `anishop_mobile_nav` POST array.
 * @return array
 */
function anishop_mobile_nav_admin_normalize_post( $raw ) {
	if ( isset( $raw['enabled'] ) && is_array( $raw['enabled'] ) ) {
		$raw['enabled'] = end( $raw['enabled'] );
	}

	if ( isset( $raw['items'] ) && is_array( $raw['items'] ) ) {
		foreach ( $raw['items'] as $key => $item ) {
			if ( is_array( $item ) && isset( $item['enabled'] ) && is_array( $item['enabled'] ) ) {
				$raw['items'][ $key ]['enabled'] = end( $item['enabled'] );
			}
		}
	}

	return $raw;
}



/**
 * Render the admin page.
 *
 * @return void
 */
function anishop_mobile_nav_admin_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage theme options.', 'anishop' ) );
	}

	$config = anishop_mobile_nav_get_config();
	$notice = isset( $_GET['anishop_mnav_notice'] ) ? sanitize_key( wp_unslash( $_GET['anishop_mnav_notice'] ) ) : '';
	$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
	$is_inline_tab  = ( 'inline-variations' === $tab );
	$is_kf_tab      = ( defined( 'ANISHOP_KF_TAB' ) && ANISHOP_KF_TAB === $tab );
	$is_buy_box_tab = ( defined( 'ANISHOP_BUY_BOX_TAB' ) && ANISHOP_BUY_BOX_TAB === $tab );
	$is_search_box_tab = ( defined( 'ANISHOP_SEARCH_BOX_TAB' ) && ANISHOP_SEARCH_BOX_TAB === $tab );

	$page_title = $is_inline_tab
		? __( 'Display Variations Inline', 'anishop' )
		: ( $is_kf_tab
			? __( 'Key Features', 'anishop' )
			: ( $is_buy_box_tab
				? __( 'Buy Box Container', 'anishop' )
				: ( $is_search_box_tab
					? __( 'Search Box', 'anishop' )
					: __( 'Mobile Bottom Navbar', 'anishop' ) ) ) );

	$page_description = $is_inline_tab
		? __( 'Configure how approved presentation types are displayed on the product page (Anishop inline variations).', 'anishop' )
		: ( $is_kf_tab
			? __( 'Configure the Key Features section shown below the add-to-cart area on single product pages.', 'anishop' )
			: ( $is_buy_box_tab
				? __( 'Configure the Buy Box container shown on single product pages.', 'anishop' )
				: ( $is_search_box_tab
					? __( 'Configure the live product search box shown in the site header.', 'anishop' )
					: __( 'Configure the mobile bottom navigation bar shown on small screens.', 'anishop' ) ) ) );
	?>
	<div class="wrap anishop-mnav-admin">
		<h1><?php echo esc_html( $page_title ); ?></h1>
		<p class="description"><?php echo esc_html( $page_description ); ?></p>

		<?php if ( function_exists( 'anishop_iv_admin_render_tab_nav' ) ) { anishop_iv_admin_render_tab_nav( $tab ); } ?>

		<?php if ( $is_inline_tab && function_exists( 'anishop_iv_admin_render_inline_variations_tab' ) ) { anishop_iv_admin_render_inline_variations_tab(); echo '</div>'; return; } ?>

		<?php if ( $is_kf_tab && function_exists( 'anishop_kf_admin_render_tab' ) ) { anishop_kf_admin_render_tab(); echo '</div>'; return; } ?>

		<?php if ( $is_buy_box_tab && function_exists( 'anishop_buy_box_admin_render_tab' ) ) { anishop_buy_box_admin_render_tab(); echo '</div>'; return; } ?>

		<?php if ( $is_search_box_tab && function_exists( 'anishop_search_box_admin_render_tab' ) ) { anishop_search_box_admin_render_tab(); echo '</div>'; return; } ?>

		<?php if ( 'saved' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Navbar settings saved.', 'anishop' ); ?></p></div>
		<?php elseif ( 'saved_invalid' === $notice ) : ?>
			<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Navbar settings saved. Some items were not saved because they were invalid (for example a link item without a URL).', 'anishop' ); ?></p></div>
		<?php elseif ( 'reset' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Navbar reset to defaults.', 'anishop' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="">
			<?php
			wp_nonce_field( 'anishop_mobile_nav_save', 'anishop_mnav_save_nonce' );
			wp_nonce_field( 'anishop_mobile_nav_reset', 'anishop_mnav_reset_nonce' );
			?>
			<div class="anishop-mnav-admin__layout">
				<div class="anishop-mnav-admin__main">
					<fieldset class="anishop-mnav-admin__fieldset">
						<legend><?php esc_html_e( 'Settings', 'anishop' ); ?></legend>
						<label class="anishop-mnav-admin__enabled">
							<input type="hidden" name="anishop_mobile_nav[enabled]" value="0">
							<input type="checkbox" name="anishop_mobile_nav[enabled]" value="1" <?php checked( $config['enabled'] ); ?>>
							<?php esc_html_e( 'Enable navbar', 'anishop' ); ?>
						</label>
					</fieldset>

					<?php anishop_mobile_nav_admin_appearance(); ?>

					<fieldset class="anishop-mnav-admin__fieldset">
						<legend><?php esc_html_e( 'Items', 'anishop' ); ?></legend>
						<?php anishop_mobile_nav_admin_icon_help(); ?>
						<ul class="anishop-mnav-admin__items" id="anishop-mnav-items">
							<?php
							foreach ( $config['items'] as $index => $item ) {
								anishop_mobile_nav_admin_item_row( $index, $item );
							}
							?>
						</ul>
						<p>
							<button type="button" class="button" id="anishop-mnav-add-item">+ <?php esc_html_e( 'Add Item', 'anishop' ); ?></button>
						</p>
						<?php anishop_mobile_nav_admin_item_template(); ?>
					</fieldset>
				</div>

				<div class="anishop-mnav-admin__side">
					<?php anishop_mobile_nav_admin_preview( $config ); ?>
				</div>
			</div>

			<p class="submit">
				<button type="submit" name="anishop_mobile_nav_save" value="1" class="button button-primary"><?php esc_html_e( 'Save Changes', 'anishop' ); ?></button>
				<button type="submit" name="anishop_mobile_nav_reset" value="1" class="button button-link-delete"><?php esc_html_e( 'Reset to Defaults', 'anishop' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}


/**
 * Render one item row of the item editor.
 *
 * System items (home/categories/cart/blog/account) keep their fixed ID and
 * type (hidden inputs, no ID/type editing, no remove). The cart item has no
 * enable/disable control. Custom items get a type select (all supported
 * types except cart), a URL field (active only for `link`) and a remove
 * button.
 *
 * @param int   $index  Position of the item in the form.
 * @param array $item   Sanitized item (id/type/label/url/icon/enabled).
 * @param bool  $is_template When true, render the "Add Item" template with
 *                           __ID__ / __INDEX__ placeholders.
 * @return void
 */
function anishop_mobile_nav_admin_item_row( $index, $item, $is_template = false ) {
	if ( $is_template ) {
		$item = array(
			'id'      => '__ID__',
			'type'    => 'link',
			'label'   => '',
			'url'     => '',
			'icon'    => 'grid',
			'enabled' => true,
		);
	}

	$id      = isset( $item['id'] ) ? $item['id'] : '';
	$type    = isset( $item['type'] ) ? $item['type'] : '';
	$label   = isset( $item['label'] ) ? $item['label'] : '';
	$url     = isset( $item['url'] ) ? $item['url'] : '';
	$icon    = isset( $item['icon'] ) ? $item['icon'] : '';
	$enabled = ! empty( $item['enabled'] );

	$is_system = ( ! $is_template ) && in_array( $id, anishop_mobile_nav_admin_system_ids(), true );
	$is_cart   = ( ANISHOP_MOBILE_NAV_CART_ID === $id );
	// Custom items (id `custom-*`, or the "Add Item" template which always
	// creates a custom item) may use project icons; system items may not.
	$is_custom = $is_template || 0 === strpos( $id, 'custom-' );
	$name      = 'anishop_mobile_nav[items][' . ( $is_template ? '__INDEX__' : (int) $index ) . ']';

	$display_name = '' !== $label
		? $label
		: ( 'account' === $type ? ( is_user_logged_in() ? __( 'My Account', 'anishop' ) : __( 'Log In', 'anishop' ) ) : anishop_mobile_nav_admin_type_label( $type ) );
	?>
	<li class="anishop-mnav-admin__item<?php echo $is_system ? ' is-system' : ' is-custom'; ?>" data-anishop-mnav-item>
		<div class="anishop-mnav-admin__item-head">
			<span class="anishop-mnav-admin__item-icon" aria-hidden="true"><?php echo anishop_mobile_nav_icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG from the theme registry. ?></span>
			<span class="anishop-mnav-admin__item-title"><?php echo esc_html( $display_name ); ?></span>
			<?php if ( $is_system ) : ?>
				<span class="anishop-mnav-admin__item-badge"><?php esc_html_e( 'System', 'anishop' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="anishop-mnav-admin__item-fields">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>[id]" value="<?php echo esc_attr( $id ); ?>">

			<?php if ( $is_system ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[type]" value="<?php echo esc_attr( $type ); ?>">
			<?php else : ?>
				<label>
					<span><?php esc_html_e( 'Type', 'anishop' ); ?></span>
					<select name="<?php echo esc_attr( $name ); ?>[type]" class="anishop-mnav-admin__type">
						<?php foreach ( anishop_mobile_nav_supported_types() as $option_type ) : ?>
							<?php if ( 'cart' === $option_type ) { continue; } ?>
							<option value="<?php echo esc_attr( $option_type ); ?>" <?php selected( $type, $option_type ); ?>><?php echo esc_html( anishop_mobile_nav_admin_type_label( $option_type ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>

			<label>
				<span><?php esc_html_e( 'Label', 'anishop' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $label ); ?>" class="regular-text" placeholder="<?php echo esc_attr( 'account' === $type ? __( 'Leave empty for the dynamic account label', 'anishop' ) : '' ); ?>">
			</label>

			<div class="anishop-mnav-admin__icon-picker">
				<span class="anishop-mnav-admin__icon-picker-label"><?php esc_html_e( 'Icon', 'anishop' ); ?></span>
				<?php anishop_mobile_nav_admin_icon_picker( $name, $icon, $is_custom ); ?>
			</div>

			<?php if ( ! $is_system ) : ?>
				<label class="anishop-mnav-admin__url-field<?php echo 'link' === $type ? '' : ' is-hidden'; ?>">
					<span><?php esc_html_e( 'URL', 'anishop' ); ?></span>
					<input type="url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $url ); ?>" class="regular-text" placeholder="https://" <?php disabled( 'link' !== $type ); ?>>
				</label>
			<?php endif; ?>

			<?php if ( $is_cart ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1">
			<?php else : ?>
				<label class="anishop-mnav-admin__enabled">
					<input type="hidden" name="<?php echo esc_attr( $name ); ?>[enabled]" value="0">
					<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[enabled]" value="1" <?php checked( $enabled ); ?>>
					<?php esc_html_e( 'Enabled', 'anishop' ); ?>
				</label>
			<?php endif; ?>
		</div>

		<div class="anishop-mnav-admin__item-actions">
			<button type="button" class="button anishop-mnav-admin__move" data-direction="up" aria-label="<?php esc_attr_e( 'Move item up', 'anishop' ); ?>">&#8593;</button>
			<button type="button" class="button anishop-mnav-admin__move" data-direction="down" aria-label="<?php esc_attr_e( 'Move item down', 'anishop' ); ?>">&#8595;</button>
			<?php if ( ! $is_system ) : ?>
				<button type="button" class="button anishop-mnav-admin__remove"><?php esc_html_e( 'Remove', 'anishop' ); ?></button>
			<?php endif; ?>
		</div>
	</li>
	<?php
}


/**
 * Render the "Project Icons" help block (B6-15 Icon Assets Discovery PoC).
 *
 * Documentation only: it explains that a developer can place a lightweight,
 * monochrome SVG file in the child theme's assets/icons/ directory and that the
 * page must be refreshed for it to appear. It deliberately does NOT mention or
 * imply any upload feature (none exists in this proof of concept).
 *
 * @return void
 */
function anishop_mobile_nav_admin_icon_help() {
	?>
	<div class="anishop-mnav-admin__icon-help">
		<h4><?php esc_html_e( 'If you want a custom icon', 'anishop' ); ?></h4>
		<p><?php esc_html_e( 'You can prepare a lightweight, monochrome SVG file and place it in the path below:', 'anishop' ); ?></p>
		<p><code>assets/icons/</code></p>
		<p><?php esc_html_e( 'Recommended features:', 'anishop' ); ?></p>
		<ul>
			<li>SVG</li>
			<li><?php esc_html_e( 'Monochrome (preferred)', 'anishop' ); ?></li>
			<li><code>viewBox</code> <?php esc_html_e( 'valid', 'anishop' ); ?></li>
			<li><?php esc_html_e( 'No JavaScript', 'anishop' ); ?></li>
			<li><?php esc_html_e( 'No', 'anishop' ); ?> <code>&lt;script&gt;</code></li>
			<li><?php esc_html_e( 'No external resources', 'anishop' ); ?></li>
			<li><?php esc_html_e( 'Preferably', 'anishop' ); ?> <code>fill="currentColor"</code></li>
			<li><?php esc_html_e( 'Small size', 'anishop' ); ?></li>
		</ul>
		<p><?php esc_html_e( 'After adding the file, refresh the settings page.', 'anishop' ); ?></p>
	</div>
	<?php
}

/**
 * Render the Appearance fieldset (B6-15 appearance presets).
 *
 * A compact, controlled preset system for the navbar's visual appearance:
 * three independent radio groups (Navbar Background, Active Tab Color,
 * Border/Frame). Each group offers only the fixed presets from
 * anishop_mobile_nav_appearance_presets(); no freeform color or CSS input.
 * The values are submitted as anishop_mobile_nav[appearance][...] and validated
 * by the configuration layer (anishop_mobile_nav_sanitize_appearance()).
 *
 * @return void
 */
function anishop_mobile_nav_admin_appearance() {
	$appearance = anishop_mobile_nav_get_config()['appearance'];
	$presets    = anishop_mobile_nav_appearance_presets();

	$background_labels = array(
		'light'     => __( 'Light', 'anishop' ),
		'dark'      => __( 'Dark', 'anishop' ),
		'soft-gray' => __( 'Soft Gray', 'anishop' ),
	);
	$active_labels = array(
		'blue'   => __( 'Blue', 'anishop' ),
		'green'  => __( 'Green', 'anishop' ),
		'orange' => __( 'Orange', 'anishop' ),
		'purple' => __( 'Purple', 'anishop' ),
	);
	$border_labels = array(
		'none'     => __( 'No Border', 'anishop' ),
		'subtle'   => __( 'Subtle', 'anishop' ),
		'elevated' => __( 'Elevated', 'anishop' ),
	);
	?>
	<fieldset class="anishop-mnav-admin__fieldset">
		<legend><?php esc_html_e( 'Appearance', 'anishop' ); ?></legend>
		<div class="anishop-mnav-admin__appearance">
			<div class="anishop-mnav-admin__appearance-group">
				<span class="anishop-mnav-admin__appearance-label"><?php esc_html_e( 'Navbar Background', 'anishop' ); ?></span>
				<div class="anishop-mnav-admin__appearance-options" role="radiogroup" aria-label="<?php esc_attr_e( 'Navbar Background', 'anishop' ); ?>">
					<?php foreach ( $presets['background'] as $value ) : ?>
						<label class="anishop-mnav-admin__appearance-option">
							<input type="radio" name="anishop_mobile_nav[appearance][background]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $appearance['background'], $value ); ?>>
							<?php echo esc_html( $background_labels[ $value ] ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="anishop-mnav-admin__appearance-group">
				<span class="anishop-mnav-admin__appearance-label"><?php esc_html_e( 'Active Item Color', 'anishop' ); ?></span>
				<div class="anishop-mnav-admin__appearance-options" role="radiogroup" aria-label="<?php esc_attr_e( 'Active Item Color', 'anishop' ); ?>">
					<?php foreach ( $presets['active_color'] as $value ) : ?>
						<label class="anishop-mnav-admin__appearance-option">
							<input type="radio" name="anishop_mobile_nav[appearance][active_color]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $appearance['active_color'], $value ); ?>>
							<?php echo esc_html( $active_labels[ $value ] ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="anishop-mnav-admin__appearance-group">
				<span class="anishop-mnav-admin__appearance-label"><?php esc_html_e( 'Border / Frame', 'anishop' ); ?></span>
				<div class="anishop-mnav-admin__appearance-options" role="radiogroup" aria-label="<?php esc_attr_e( 'Border / Frame', 'anishop' ); ?>">
					<?php foreach ( $presets['border'] as $value ) : ?>
						<label class="anishop-mnav-admin__appearance-option">
							<input type="radio" name="anishop_mobile_nav[appearance][border]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $appearance['border'], $value ); ?>>
							<?php echo esc_html( $border_labels[ $value ] ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</fieldset>
	<?php
}

/**
 * Render the icon picker.
 *
 * Two visual groups:
 *  1. "Anishop Icons" — the existing five built-in icons, rendered exactly as
 *     before (radio group bound to the item's [icon] field, using the inline
 *     SVG registry anishop_mobile_nav_icon() in render.php).
 *  2. "Project Icons" — SVG files discovered read-only in assets/icons/ (see
 *     icon-assets.php). Shown only when $allow_project is true (custom items).
 *     Each project icon is a real radio in the SAME [icon] group as the
 *     built-ins, with a `project:<id>` value, so selecting one is a genuine,
 *     persisted selection (validated by the sanitizer). The preview is a safe
 *     <img> (never injected as markup).
 *
 * System items pass $allow_project = false, so they only ever offer the
 * built-in icons; a project icon can never be selected for them.
 *
 * @param string $name          Form field name prefix (without the [icon] part).
 * @param string $selected      Selected icon key (built-in key or `project:<id>`).
 * @param bool   $allow_project Whether project icons are offered (custom items).
 * @return void
 */
function anishop_mobile_nav_admin_icon_picker( $name, $selected, $allow_project = false ) {
	$project_icons = $allow_project ? anishop_mobile_nav_discover_icon_assets() : array();
	$prefix        = anishop_mobile_nav_project_icon_prefix();
	?>
	<div class="anishop-mnav-admin__icon-groups">
		<div class="anishop-mnav-admin__icon-group">
			<span class="anishop-mnav-admin__icon-group-label"><?php esc_html_e( 'Anishop Icons', 'anishop' ); ?></span>
			<div class="anishop-mnav-admin__icons" role="radiogroup" aria-label="<?php esc_attr_e( 'Anishop Icons', 'anishop' ); ?>">
				<?php foreach ( anishop_mobile_nav_supported_icons() as $icon ) : ?>
					<?php $svg = anishop_mobile_nav_icon( $icon ); ?>
					<label class="anishop-mnav-admin__icon-option<?php echo $selected === $icon ? ' is-selected' : ''; ?>">
						<input type="radio" name="<?php echo esc_attr( $name ); ?>[icon]" value="<?php echo esc_attr( $icon ); ?>" <?php checked( $selected, $icon ); ?>>
						<span class="anishop-mnav-admin__icon-svg" aria-hidden="true"><?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG from the theme registry. ?></span>
						<span class="screen-reader-text"><?php echo esc_html( $icon ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php if ( ! empty( $project_icons ) ) : ?>
		<div class="anishop-mnav-admin__icon-group">
			<span class="anishop-mnav-admin__icon-group-label"><?php esc_html_e( 'Project Icons', 'anishop' ); ?></span>
			<div class="anishop-mnav-admin__icons anishop-mnav-admin__icons--project" role="radiogroup" aria-label="<?php esc_attr_e( 'Project Icons', 'anishop' ); ?>">
				<?php foreach ( $project_icons as $project_icon ) : ?>
					<?php $project_value = $prefix . $project_icon['id']; ?>
					<label class="anishop-mnav-admin__icon-option anishop-mnav-admin__icon-option--project<?php echo $selected === $project_value ? ' is-selected' : ''; ?>" title="<?php echo esc_attr( $project_icon['label'] ); ?>">
						<input type="radio" name="<?php echo esc_attr( $name ); ?>[icon]" value="<?php echo esc_attr( $project_value ); ?>" <?php checked( $selected, $project_value ); ?>>
						<img class="anishop-mnav-admin__icon-img" src="<?php echo esc_url( $project_icon['url'] ); ?>" alt="<?php echo esc_attr( $project_icon['label'] ); ?>" width="18" height="18">
						<span class="screen-reader-text"><?php echo esc_html( $project_icon['label'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render the hidden <template> used by the "Add Item" button.
 *
 * @return void
 */
function anishop_mobile_nav_admin_item_template() {
	echo '<template id="anishop-mnav-item-template">';
	anishop_mobile_nav_admin_item_row( 0, array(), true );
	echo '</template>';
}

/**
 * Render the static admin preview of the navbar (order, icons, labels,
 * cart badge). Not interactive; not a live frontend preview.
 *
 * @param array $config Sanitized configuration.
 * @return void
 */
function anishop_mobile_nav_admin_preview( $config ) {
	$cart_count = 0;
	if ( function_exists( 'WC' ) && null !== WC() && null !== WC()->cart ) {
		$cart_count = (int) WC()->cart->get_cart_contents_count();
	}
	?>
	<div class="anishop-mnav-admin__preview">
		<h2><?php esc_html_e( 'Preview', 'anishop' ); ?></h2>
		<?php if ( ! $config['enabled'] ) : ?>
			<p class="anishop-mnav-admin__preview-disabled"><?php esc_html_e( 'The navbar is currently disabled.', 'anishop' ); ?></p>
		<?php endif; ?>
		<div class="anishop-mnav-admin__preview-bar<?php echo $config['enabled'] ? '' : ' is-disabled'; ?>" aria-hidden="true">
			<?php foreach ( $config['items'] as $item ) : ?>
				<?php if ( empty( $item['enabled'] ) ) { continue; } ?>
				<span class="anishop-mnav-admin__preview-item">
					<span class="anishop-mnav-admin__preview-icon"><?php echo anishop_mobile_nav_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG from the theme registry. ?></span>
					<span class="anishop-mnav-admin__preview-label"><?php echo esc_html( '' !== $item['label'] ? $item['label'] : ( 'account' === $item['type'] ? ( is_user_logged_in() ? __( 'My Account', 'anishop' ) : __( 'Log In', 'anishop' ) ) : anishop_mobile_nav_admin_type_label( $item['type'] ) ) ); ?></span>
					<?php if ( ANISHOP_MOBILE_NAV_CART_ID === $item['id'] ) : ?>
						<span class="anishop-mnav-admin__preview-badge"><?php echo esc_html( number_format_i18n( $cart_count ) ); ?></span>
					<?php endif; ?>
				</span>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
