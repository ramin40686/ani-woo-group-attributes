<?php
/**
 * Anishop mobile bottom navigation — configuration layer.
 *
 * B6-15 Part 2, Step 1: architecture for an admin-configurable mobile bottom
 * navigation. This file defines the configuration CONTRACT for the future
 * admin UI:
 *
 *  - the default configuration (exactly the current five-item navbar),
 *  - the supported item types and the icon whitelist,
 *  - the single Theme Mod that stores the configuration,
 *  - the sanitization/validation rules the admin code must pass data through.
 *
 * Step 1 scope (important):
 *  - The renderer (render.php) does NOT read this configuration yet. It keeps
 *    using the existing hardcoded items. The renderer migration is Step 3.
 *  - Nothing in this file writes the Theme Mod. The Theme Mod is only written
 *    by an explicit admin action through anishop_mobile_nav_save_config().
 *    Loading the frontend never writes it.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The sanitizer validates project-icon identifiers against the project icon
// assets, so the project-icon asset helpers must be available. (admin.php also
// requires this file; require_once makes the second load a no-op.)
require_once __DIR__ . '/icon-assets.php';

/**
 * The single Theme Mod key for the mobile bottom navigation configuration.
 *
 * The configuration is theme-specific and stays isolated to the active child
 * theme (the Theme Mod API stores it inside the child theme's theme_mods
 * option). Never write that option directly.
 */
define( 'ANISHOP_MOBILE_NAV_THEME_MOD', 'anishop_mobile_nav' );

/**
 * The protected runtime ID of the Cart item.
 *
 * The cart badge JavaScript targets
 * `.anishop-mobile-nav__item[data-anishop-nav="cart"]` and
 * `.anishop-mobile-nav__cart-count`. This ID must never change, and no second
 * cart item may ever exist.
 */
define( 'ANISHOP_MOBILE_NAV_CART_ID', 'cart' );

/**
 * Supported item types (MVP).
 *
 * `search`, `wishlist`, `orders` and arbitrary custom types are intentionally
 * NOT supported yet (future scope).
 *
 * @return string[]
 */
function anishop_mobile_nav_supported_types() {
	return array( 'home', 'shop', 'cart', 'account', 'blog', 'link' );
}

/**
 * Supported icon keys (MVP).
 *
 * Only the five inline SVG icons already rendered by render.php. No icon
 * fonts, no external icon libraries, no SVG uploads, no arbitrary SVG HTML,
 * no remote icon URLs.
 *
 * @return string[]
 */
function anishop_mobile_nav_supported_icons() {
	return array( 'home', 'grid', 'cart', 'blog', 'user' );
}

/**
 * Default icon per item type.
 *
 * Used when an item's icon is missing or not in the whitelist.
 *
 * @return array<string, string>
 */
function anishop_mobile_nav_default_icons() {
	return array(
		'home'    => 'home',
		'shop'    => 'grid',
		'cart'    => 'cart',
		'blog'    => 'blog',
		'account' => 'user',
		'link'    => 'grid',
	);
}

/**
 * The allowed appearance presets.
 *
 * A small, controlled preset system for the navbar's visual appearance. Three
 * independent dimensions, each a strict allowlist. No arbitrary colors, no raw
 * CSS, no freeform values — only these fixed preset identifiers may be stored.
 *
 * @return array{background: string[], active_color: string[], border: string[]}
 */
function anishop_mobile_nav_appearance_presets() {
	return array(
		'background'   => array( 'light', 'dark', 'soft-gray' ),
		'active_color' => array( 'blue', 'green', 'orange', 'purple' ),
		'border'       => array( 'none', 'subtle', 'elevated' ),
	);
}

/**
 * The default appearance.
 *
 * Reproduces the navbar's current visual behavior exactly, so an installation
 * with no stored appearance configuration renders identically to before this
 * feature existed. No runtime migration is performed to populate these values.
 *
 * @return array{background: string, active_color: string, border: string}
 */
function anishop_mobile_nav_appearance_defaults() {
	return array(
		'background'   => 'light',
		'active_color' => 'blue',
		'border'       => 'subtle',
	);
}

/**
 * Sanitize + validate the appearance configuration.
 *
 * Each of the three independent keys is validated against its explicit
 * allowlist (anishop_mobile_nav_appearance_presets()). Any value that is not a
 * string in the allowlist safely falls back to that key's default. No raw CSS,
 * arbitrary color string, URL, HTML, or JavaScript can reach the frontend from
 * this data — only the fixed preset identifiers.
 *
 * @param mixed $appearance Raw appearance configuration.
 * @return array{background: string, active_color: string, border: string}
 */
function anishop_mobile_nav_sanitize_appearance( $appearance ) {
	$defaults = anishop_mobile_nav_appearance_defaults();
	$presets  = anishop_mobile_nav_appearance_presets();
	$clean    = array();

	foreach ( $defaults as $key => $default ) {
		$value = ( is_array( $appearance ) && isset( $appearance[ $key ] ) && is_string( $appearance[ $key ] ) ) ? trim( $appearance[ $key ] ) : $default;
		$clean[ $key ] = in_array( $value, $presets[ $key ], true ) ? $value : $default;
	}

	return $clean;
}

/**
 * Map a validated appearance configuration to the CSS preset classes.
 *
 * The classes are the only appearance data that reaches the frontend markup;
 * the actual CSS values live in anishop-mobile-nav.css. Because the input is
 * validated against the allowlist, the class names are a fixed, known set (no
 * arbitrary CSS can be produced).
 *
 * @param array $appearance A validated appearance configuration.
 * @return string Space-separated CSS classes.
 */
function anishop_mobile_nav_appearance_classes( $appearance ) {
	$appearance = anishop_mobile_nav_sanitize_appearance( $appearance );
	return implode(
		' ',
		array(
			'anishop-mobile-nav--bg-' . $appearance['background'],
			'anishop-mobile-nav--active-' . $appearance['active_color'],
			'anishop-mobile-nav--border-' . $appearance['border'],
		)
	);
}

/**
 * The default configuration.
 *
 * Represents the current five-item hardcoded navbar exactly — same order,
 * same IDs, same labels, same icons:
 *
 *  1. home       (id `home`,       type `home`,    icon `home`)
 *  2. categories (id `categories`, type `shop`,    icon `grid`)
 *  3. cart       (id `cart`,       type `cart`,    icon `cart`)
 *  4. blog       (id `blog`,       type `blog`,    icon `blog`)
 *  5. account    (id `account`,    type `account`, icon `user`)
 *
 * Semantics of the empty strings:
 *  - `url` empty on a built-in type (home/shop/cart/blog/account) means
 *    "resolve the built-in URL for this type" — the same dynamic resolution
 *    render.php performs today (home_url('/'), the Shop page, the Cart page,
 *    the blog page, the My Account page). On a `link` item the URL is
 *    required.
 *  - `label` empty on the `account` item means "use the built-in dynamic
 *    label" (حساب کاربری when logged in, ورود when logged out).
 *
 * @return array{enabled: bool, items: array[], appearance: array{background: string, active_color: string, border: string}}
 */
function anishop_mobile_nav_get_defaults() {
	return array(
		'enabled'    => true,
		'items'      => array(
			array(
				'id'      => 'home',
				'type'    => 'home',
				'label'   => __( 'خانه', 'anishop' ),
				'url'     => '',
				'icon'    => 'home',
				'enabled' => true,
			),
			array(
				'id'      => 'categories',
				'type'    => 'shop',
				'label'   => __( 'دسته‌بندی‌ها', 'anishop' ),
				'url'     => '',
				'icon'    => 'grid',
				'enabled' => true,
			),
			array(
				'id'      => 'cart',
				'type'    => 'cart',
				'label'   => __( 'سبد خرید', 'anishop' ),
				'url'     => '',
				'icon'    => 'cart',
				'enabled' => true,
			),
			array(
				'id'      => 'blog',
				'type'    => 'blog',
				'label'   => __( 'بلاگ', 'anishop' ),
				'url'     => '',
				'icon'    => 'blog',
				'enabled' => true,
			),
			array(
				'id'      => 'account',
				'type'    => 'account',
				'label'   => '',
				'url'     => '',
				'icon'    => 'user',
				'enabled' => true,
			),
		),
		'appearance' => anishop_mobile_nav_appearance_defaults(),
	);
}

/**
 * Normalize a value to an explicit boolean.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function anishop_mobile_nav_sanitize_bool( $value ) {
	if ( is_bool( $value ) ) {
		return $value;
	}
	if ( is_string( $value ) ) {
		return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'on' ), true );
	}
	if ( is_numeric( $value ) ) {
		return 0.0 !== (float) $value;
	}
	return false;
}

/**
 * Sanitize + validate a raw configuration (e.g. from the future admin form).
 *
 * Rules:
 *  - `enabled`   → explicit boolean normalization.
 *  - `items`     → must be a list; unknown per-item fields are ignored.
 *  - item `id`   → sanitize_key(); must be non-empty and unique. The ID
 *                  `cart` is reserved for the cart item.
 *  - item `type` → strict whitelist (anishop_mobile_nav_supported_types()).
 *  - item `label`→ sanitize_text_field().
 *  - item `url`  → esc_url_raw(); required for `link` items, optional
 *                  override for built-in types (empty = built-in URL).
 *  - item `icon` → a built-in icon (anishop_mobile_nav_supported_icons()) is
 *                  valid for every item; a project icon (`project:<id>`) is
 *                  valid only for custom items (id `custom-*`) while the
 *                  assets/icons/<id>.svg file exists; anything else (unknown
 *                  key, a project icon on a system item, or a project icon
 *                  whose file is missing) falls back to the type's default.
 *  - item `enabled`→ explicit boolean normalization.
 *
 * Cart safety:
 *  - at most one `cart` item; its ID is always forced to `cart`;
 *  - a second cart item is dropped;
 *  - a non-cart item may never take the ID `cart` (it is rejected).
 *
 * Malformed items (not an array, unsupported type, empty/duplicate/reserved
 * ID, link without URL) are rejected (skipped), never rendered. If no item
 * survives validation, the default items are used so a broken configuration
 * can never produce an empty bar.
 *
 * A completely non-array input yields the defaults.
 *
 *  - `appearance`→ each of background/active_color/border is validated against
 *                  its explicit allowlist; an invalid value falls back to the
 *                  default preset (anishop_mobile_nav_sanitize_appearance()).
 *
 * @param mixed $config Raw configuration.
 * @return array{enabled: bool, items: array[], appearance: array{background: string, active_color: string, border: string}}
 */
function anishop_mobile_nav_sanitize( $config ) {
	if ( ! is_array( $config ) ) {
		return anishop_mobile_nav_get_defaults();
	}

	$defaults     = anishop_mobile_nav_get_defaults();
	$types        = anishop_mobile_nav_supported_types();
	$icons        = anishop_mobile_nav_supported_icons();
	$default_icon = anishop_mobile_nav_default_icons();

	$enabled = anishop_mobile_nav_sanitize_bool( isset( $config['enabled'] ) ? $config['enabled'] : true );

	$raw_items = ( isset( $config['items'] ) && is_array( $config['items'] ) ) ? $config['items'] : $defaults['items'];

	$items    = array();
	$used_ids = array();
	$has_cart = false;

	foreach ( $raw_items as $raw ) {
		if ( ! is_array( $raw ) ) {
			continue; // Malformed item: reject.
		}

		$type = ( isset( $raw['type'] ) && is_string( $raw['type'] ) ) ? trim( $raw['type'] ) : '';
		if ( ! in_array( $type, $types, true ) ) {
			continue; // Unsupported type: reject.
		}

		// Cart safety: at most one cart item, ID always `cart`.
		if ( 'cart' === $type ) {
			if ( $has_cart ) {
				continue; // Second cart item: drop.
			}
			$has_cart = true;
			$id       = ANISHOP_MOBILE_NAV_CART_ID;
		} else {
			$id = isset( $raw['id'] ) ? sanitize_key( $raw['id'] ) : '';
			if ( '' === $id || ANISHOP_MOBILE_NAV_CART_ID === $id || isset( $used_ids[ $id ] ) ) {
				continue; // Empty / reserved / duplicate ID: reject.
			}
		}

		$label = isset( $raw['label'] ) ? sanitize_text_field( $raw['label'] ) : '';

		$url = isset( $raw['url'] ) ? esc_url_raw( $raw['url'] ) : '';
		if ( 'link' === $type && '' === $url ) {
			continue; // A link item without a URL is malformed: reject.
		}

		$icon = ( isset( $raw['icon'] ) && is_string( $raw['icon'] ) ) ? trim( $raw['icon'] ) : '';

		// Icon rules:
		//  - a built-in icon (home/grid/cart/blog/user) is valid for every item;
		//  - a project icon (`project:<id>`) is valid ONLY for custom items
		//    (id `custom-*`) AND only while the assets/icons/<id>.svg file exists;
		//  - anything else (unknown key, a project icon on a system item, or a
		//    project icon whose file is missing) falls back to the type default.
		$is_custom = 0 === strpos( $id, 'custom-' );
		if ( in_array( $icon, $icons, true ) ) {
			// Built-in icon: keep as-is.
		} elseif ( $is_custom && anishop_mobile_nav_is_valid_project_icon( $icon ) ) {
			// Valid project icon on a custom item: keep as-is.
		} else {
			$icon = $default_icon[ $type ];
		}

		$used_ids[ $id ] = true;
		$items[]         = array(
			'id'      => $id,
			'type'    => $type,
			'label'   => $label,
			'url'     => $url,
			'icon'    => $icon,
			'enabled' => anishop_mobile_nav_sanitize_bool( isset( $raw['enabled'] ) ? $raw['enabled'] : true ),
		);
	}

	if ( empty( $items ) ) {
		$items = $defaults['items'];
	}

	$appearance = anishop_mobile_nav_sanitize_appearance( isset( $config['appearance'] ) ? $config['appearance'] : array() );

	return array(
		'enabled'    => $enabled,
		'items'      => array_values( $items ),
		'appearance' => $appearance,
	);
}

/**
 * Get the current configuration.
 *
 * Returns the defaults whenever the Theme Mod does not exist (or is not an
 * array). A stored value is always passed through the sanitizer, so the
 * returned configuration always satisfies the contract.
 *
 * READ-ONLY: this function never writes the Theme Mod.
 *
 * @return array{enabled: bool, items: array[], appearance: array{background: string, active_color: string, border: string}}
 */
function anishop_mobile_nav_get_config() {
	$stored = get_theme_mod( ANISHOP_MOBILE_NAV_THEME_MOD );
	if ( ! is_array( $stored ) ) {
		return anishop_mobile_nav_get_defaults();
	}
	return anishop_mobile_nav_sanitize( $stored );
}

/**
 * Save a configuration (for the future admin UI).
 *
 * The raw input is sanitized before being stored, so the Theme Mod always
 * holds a valid configuration. This is the only code path that writes the
 * Theme Mod, and it must only be called from an explicit admin action.
 *
 * @param mixed $config Raw configuration (e.g. from the admin form).
 * @return array{enabled: bool, items: array[], appearance: array{background: string, active_color: string, border: string}} The sanitized configuration that was stored.
 */
function anishop_mobile_nav_save_config( $config ) {
	$clean = anishop_mobile_nav_sanitize( $config );
	set_theme_mod( ANISHOP_MOBILE_NAV_THEME_MOD, $clean );
	return $clean;
}

/**
 * Remove the stored configuration (reset to defaults).
 *
 * @return void
 */
function anishop_mobile_nav_reset_config() {
	remove_theme_mod( ANISHOP_MOBILE_NAV_THEME_MOD );
}

