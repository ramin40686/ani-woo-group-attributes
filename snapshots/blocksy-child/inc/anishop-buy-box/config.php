<?php
/**
 * Anishop Buy Box — configuration layer (Stage 1 foundation).
 *
 * Three-Column Buy Box project, Stage 1: the settings FOUNDATION for the
 * future Buy Box feature. This file defines the configuration CONTRACT:
 *
 *  - the schema (every key a later stage will consume) + safe defaults,
 *  - the schema version,
 *  - the single Theme Mod that will store the configuration,
 *  - the sanitization/validation rules the admin code must pass data through.
 *
 * Stage 1 scope (important):
 *  - This file is READ-ONLY in Stage 1. It never renders the frontend and it
 *    is never invoked by any production code path in Stage 1.
 *  - The `anishop_buy_box` Theme Mod is NEVER written in Stage 1 (there is no
 *    save UI). It stays ABSENT, so `anishop_buy_box_get_config()` always
 *    returns the defaults and the site renders exactly as today.
 *  - The write path (`anishop_buy_box_save_config()` /
 *    `anishop_buy_box_reset_config()`) is defined now as part of the
 *    foundation, but it is only invoked once Stage 2 adds the save UI.
 *
 * The configuration layer is the single validation authority: all submitted
 * data (Stage 2 onward) is passed through `anishop_buy_box_sanitize()`.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single Theme Mod key for the Buy Box configuration.
 *
 * The configuration is theme-specific and stays isolated to the active child
 * theme (the Theme Mod API stores it inside the child theme's theme_mods
 * option). Never write that option directly.
 *
 * NOTE: in Stage 1 this key is never written — it remains ABSENT.
 */
define( 'ANISHOP_BUY_BOX_THEME_MOD', 'anishop_buy_box' );

/**
 * The Buy Box configuration schema version.
 *
 * Bumped only when the stored shape needs a migration. Stage 3 (rows) adds a
 * backward-compatible `rows` key under this versioning scheme.
 */
define( 'ANISHOP_BUY_BOX_VERSION', 1 );

/**
 * The post meta key that stores the product-specific Buy Box rows on a product.
 *
 * Stage 3: per-product rows are stored on the PARENT product (variation-level
 * rows are out of scope). Written only through the product-rows save handler
 * (nonce + capability protected) and read only through
 * `anishop_buy_box_get_product_rows()`.
 */
define( 'ANISHOP_BUY_BOX_ROWS_META_KEY', 'anishop_buy_box_rows' );

/**
 * The maximum number of rows (global or per-product) an administrator may save.
 *
 * Keeps the schema small and the rendered Buy Box compact.
 */
define( 'ANISHOP_BUY_BOX_MAX_ROWS', 12 );

/**
 * The default row background/accent color (a theme-consistent blue).
 *
 * Used by the `soft` / `solid` / `outline` row styles. The administrator can
 * override it in the «کانتینر Buy Box» tab.
 */
define( 'ANISHOP_BUY_BOX_DEFAULT_ROW_COLOR', '#2872fa' );

/**
 * The default Buy Box configuration (the schema with safe defaults).
 *
 * `enabled = false` means an untouched install (Theme Mod absent) renders the
 * product page exactly as today — the feature-off guarantee. The sub-feature
 * defaults are chosen so that when Stage 2 lands and the user turns `enabled`
 * on, the expected three-column + desktop Buy Box appears without extra
 * configuration; the sticky/mobile features default off (opt-in in their own
 * stages).
 *
 * Stage 3 adds the backward-compatible `rows` (global rows), `row_style` and
 * `row_color` keys under the SAME version 1 (a stored value without them simply
 * gets the defaults — no migration needed).
 *
 * @return array{version: int, enabled: bool, three_column_layout: bool, desktop_buy_box: bool, desktop_sticky_buy_box: bool, mobile_sticky_buy_bar: bool, rows: array, row_style: string, row_color: string}
 */
function anishop_buy_box_get_defaults() {
	return array(
		'version'                => ANISHOP_BUY_BOX_VERSION, // Schema version (not user-controlled).
		'enabled'                => false, // MASTER switch — default OFF = current behavior.
		'three_column_layout'    => true, // Consumed by Stage 2.
		'desktop_buy_box'        => true, // Consumed by Stage 2.
		'desktop_sticky_buy_box' => false, // Consumed by Stage 4.
		'mobile_sticky_buy_bar'  => false, // Consumed by Stage 5.
		'rows'                   => array(), // Stage 3: global rows (ordered list of row objects).
		'row_style'              => 'soft', // Stage 3: row visual state (soft/solid/outline).
		'row_color'              => ANISHOP_BUY_BOX_DEFAULT_ROW_COLOR, // Stage 3: row background/accent color.
	);
}

/**
 * Sanitize a single boolean setting.
 *
 * Accepts `true`, `1`, and `'1'` as `true`; everything else is `false`. This
 * matches the native checkbox form contract (checked submits `1`, unchecked
 * submits nothing / `0`).
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function anishop_buy_box_sanitize_bool( $value ) {
	if ( true === $value || 1 === $value || '1' === $value ) {
		return true;
	}
	return false;
}

/**
 * Sanitize the row visual state (the `row_style` setting).
 *
 * Whitelist: `soft` (light tinted background, colored text — default),
 * `solid` (solid background, white text), `outline` (transparent background,
 * border + colored text). Anything else falls back to `soft`.
 *
 * @param mixed $style Raw value.
 * @return string
 */
function anishop_buy_box_sanitize_row_style( $style ) {
	$allowed = array( 'soft', 'solid', 'outline' );
	$style   = is_scalar( $style ) ? sanitize_key( $style ) : '';
	return in_array( $style, $allowed, true ) ? $style : 'soft';
}

/**
 * Sanitize the row background/accent color (the `row_color` setting).
 *
 * Accepts a `#RRGGBB` hex color (6 hex digits, no alpha). Anything else falls
 * back to the default row color.
 *
 * @param mixed $color Raw value.
 * @return string
 */
function anishop_buy_box_sanitize_row_color( $color ) {
	$color = is_scalar( $color ) ? trim( (string) $color ) : '';
	if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
		return strtolower( $color );
	}
	return ANISHOP_BUY_BOX_DEFAULT_ROW_COLOR;
}

/**
 * Sanitize a single Buy Box row.
 *
 * A row is a compact, button-like assurance element. The schema is small and
 * extensible:
 *  - `enabled` (bool) — whether the row is shown;
 *  - `title` (string) — the main text (required for the row to render);
 *  - `text` (string) — optional secondary text;
 *  - `link` (string) — optional URL (if present, the row is a link);
 *  - `icon` (string) — a trusted icon key (from `anishop_buy_box_icon_keys()`).
 *
 * No arbitrary HTML/SVG is accepted: the icon is a whitelist key and the text
 * fields are plain text (escaped on output). A row with an empty `title` is
 * dropped (it would render as an empty element).
 *
 * @param mixed $row Raw row (e.g. from the admin form).
 * @return array{enabled: bool, title: string, text: string, link: string, icon: string}|null The sanitized row, or `null` when the row is empty/invalid.
 */
function anishop_buy_box_sanitize_row( $row ) {
	if ( ! is_array( $row ) ) {
		return null;
	}

	$title = isset( $row['title'] ) ? sanitize_text_field( wp_unslash( (string) $row['title'] ) ) : '';
	if ( '' === $title ) {
		return null;
	}

	$text = isset( $row['text'] ) ? sanitize_text_field( wp_unslash( (string) $row['text'] ) ) : '';

	$link = '';
	if ( isset( $row['link'] ) && is_scalar( $row['link'] ) ) {
		$link = esc_url_raw( wp_unslash( (string) $row['link'] ) );
	}

	$icon = isset( $row['icon'] ) && is_scalar( $row['icon'] ) ? sanitize_key( $row['icon'] ) : 'none';
	if ( ! in_array( $icon, anishop_buy_box_icon_keys(), true ) ) {
		$icon = 'none';
	}

	return array(
		'enabled' => anishop_buy_box_sanitize_bool( isset( $row['enabled'] ) ? $row['enabled'] : true ),
		'title'   => $title,
		'text'    => $text,
		'link'    => $link,
		'icon'    => $icon,
	);
}

/**
 * Sanitize a list of Buy Box rows (global or per-product).
 *
 * Rules:
 *  - non-array input → empty array;
 *  - each entry is passed through `anishop_buy_box_sanitize_row()`;
 *  - empty/invalid entries are dropped;
 *  - the list is re-indexed (order = submission order) and capped at
 *    ANISHOP_BUY_BOX_MAX_ROWS.
 *
 * @param mixed $rows Raw rows (e.g. from the admin form).
 * @return array[] The sanitized, ordered list of rows.
 */
function anishop_buy_box_sanitize_rows( $rows ) {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$clean = array();
	foreach ( $rows as $row ) {
		$sanitized = anishop_buy_box_sanitize_row( $row );
		if ( null !== $sanitized ) {
			$clean[] = $sanitized;
		}
		if ( count( $clean ) >= ANISHOP_BUY_BOX_MAX_ROWS ) {
			break;
		}
	}

	return array_values( $clean );
}

/**
 * Sanitize a full Buy Box configuration (the single validation authority).
 *
 * Rules:
 *  - the five feature flags are coerced to explicit booleans;
 *  - `version` is always pinned to ANISHOP_BUY_BOX_VERSION (never
 *    user-controlled);
 *  - `rows` is passed through `anishop_buy_box_sanitize_rows()`;
 *  - `row_style` / `row_color` are passed through their sanitizers;
 *  - unknown fields are ignored;
 *  - non-array input returns the full defaults.
 *
 * @param mixed $config Raw configuration (e.g. from the admin form).
 * @return array{version: int, enabled: bool, three_column_layout: bool, desktop_buy_box: bool, desktop_sticky_buy_box: bool, mobile_sticky_buy_bar: bool, rows: array, row_style: string, row_color: string}
 */
function anishop_buy_box_sanitize( $config ) {
	$defaults = anishop_buy_box_get_defaults();

	if ( ! is_array( $config ) ) {
		return $defaults;
	}

	$bool_keys = array(
		'enabled',
		'three_column_layout',
		'desktop_buy_box',
		'desktop_sticky_buy_box',
		'mobile_sticky_buy_bar',
	);

	$clean = array( 'version' => ANISHOP_BUY_BOX_VERSION );
	foreach ( $bool_keys as $key ) {
		$clean[ $key ] = anishop_buy_box_sanitize_bool( isset( $config[ $key ] ) ? $config[ $key ] : $defaults[ $key ] );
	}

	$clean['rows']      = anishop_buy_box_sanitize_rows( isset( $config['rows'] ) ? $config['rows'] : array() );
	$clean['row_style'] = anishop_buy_box_sanitize_row_style( isset( $config['row_style'] ) ? $config['row_style'] : $defaults['row_style'] );
	$clean['row_color'] = anishop_buy_box_sanitize_row_color( isset( $config['row_color'] ) ? $config['row_color'] : $defaults['row_color'] );

	return $clean;
}

/**
 * Get the current Buy Box configuration.
 *
 * Returns the defaults whenever the Theme Mod does not exist (or is not an
 * array). A stored value is always passed through the sanitizer, so the
 * returned configuration always satisfies the contract.
 *
 * READ-ONLY: this function never writes the Theme Mod. In Stage 1 the Theme
 * Mod is always absent, so this always returns the defaults.
 *
 * @return array{version: int, enabled: bool, three_column_layout: bool, desktop_buy_box: bool, desktop_sticky_buy_box: bool, mobile_sticky_buy_bar: bool}
 */
function anishop_buy_box_get_config() {
	$stored = get_theme_mod( ANISHOP_BUY_BOX_THEME_MOD );
	if ( ! is_array( $stored ) ) {
		return anishop_buy_box_get_defaults();
	}
	return anishop_buy_box_sanitize( $stored );
}

/**
 * Save a Buy Box configuration (write path — Stage 2 onward).
 *
 * The raw input is sanitized before being stored, so the Theme Mod always
 * holds a valid configuration. This is the only code path that writes the
 * Theme Mod, and it must only be called from an explicit, nonce-protected
 * admin action (added in Stage 2).
 *
 * NOT invoked in Stage 1 (there is no save UI).
 *
 * @param mixed $config Raw configuration (e.g. from the admin form).
 * @return array{version: int, enabled: bool, three_column_layout: bool, desktop_buy_box: bool, desktop_sticky_buy_box: bool, mobile_sticky_buy_bar: bool} The sanitized configuration that was stored.
 */
function anishop_buy_box_save_config( $config ) {
	$clean = anishop_buy_box_sanitize( $config );
	set_theme_mod( ANISHOP_BUY_BOX_THEME_MOD, $clean );
	return $clean;
}

/**
 * Remove the stored Buy Box configuration (reset to defaults — Stage 2 onward).
 *
 * NOT invoked in Stage 1 (there is no reset UI).
 *
 * @return void
 */
function anishop_buy_box_reset_config() {
	remove_theme_mod( ANISHOP_BUY_BOX_THEME_MOD );
}

/**
 * Get the global Buy Box rows (sanitized, enabled only, in order).
 *
 * Reads the `rows` key of the `anishop_buy_box` Theme Mod through the config
 * layer (so the value always satisfies the contract) and returns only the
 * enabled rows. Disabled rows are kept in storage (so re-enabling restores
 * them) but are not returned for rendering.
 *
 * @return array[] The ordered list of enabled global rows.
 */
function anishop_buy_box_get_global_rows() {
	$config = anishop_buy_box_get_config();
	$rows   = array();
	foreach ( $config['rows'] as $row ) {
		if ( ! empty( $row['enabled'] ) ) {
			$rows[] = $row;
		}
	}
	return $rows;
}

/**
 * Get the product-specific Buy Box rows for a product (sanitized, enabled only).
 *
 * Reads the `anishop_buy_box_rows` post meta on the PARENT product through the
 * single sanitization authority (so a hand-edited / legacy value is always
 * normalized). Returns only the enabled rows, in order.
 *
 * @param int $product_id The (parent) product ID.
 * @return array[] The ordered list of enabled product-specific rows.
 */
function anishop_buy_box_get_product_rows( $product_id ) {
	$rows    = anishop_buy_box_get_product_rows_all( $product_id );
	$enabled = array();
	foreach ( $rows as $row ) {
		if ( ! empty( $row['enabled'] ) ) {
			$enabled[] = $row;
		}
	}
	return $enabled;
}

/**
 * Get ALL product-specific Buy Box rows for a product (sanitized, in order).
 *
 * Unlike `anishop_buy_box_get_product_rows()` (which returns only the enabled
 * rows for rendering), this returns every stored row — including disabled
 * ones — so the admin editor can show and re-enable them.
 *
 * @param int $product_id The (parent) product ID.
 * @return array[] The ordered list of all product-specific rows.
 */
function anishop_buy_box_get_product_rows_all( $product_id ) {
	if ( ! $product_id ) {
		return array();
	}
	$stored = get_post_meta( $product_id, ANISHOP_BUY_BOX_ROWS_META_KEY, true );
	return anishop_buy_box_sanitize_rows( $stored );
}

/**
 * Get the row appearance (the global `row_style` + `row_color`).
 *
 * @return array{style: string, color: string}
 */
function anishop_buy_box_get_row_appearance() {
	$config = anishop_buy_box_get_config();
	return array(
		'style' => $config['row_style'],
		'color' => $config['row_color'],
	);
}

/**
 * Mix two `#RRGGBB` hex colors.
 *
 * @param string $hex1   First color (weight `$weight`).
 * @param string $hex2   Second color (weight `1 - $weight`).
 * @param float  $weight The weight of `$hex1` (0–1).
 * @return string The mixed `#rrggbb` color.
 */
function anishop_buy_box_mix_hex( $hex1, $hex2, $weight ) {
	$weight = max( 0, min( 1, (float) $weight ) );
	$parse  = static function ( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	};
	list( $r1, $g1, $b1 ) = $parse( $hex1 );
	list( $r2, $g2, $b2 ) = $parse( $hex2 );
	$r = (int) round( $r1 * $weight + $r2 * ( 1 - $weight ) );
	$g = (int) round( $g1 * $weight + $g2 * ( 1 - $weight ) );
	$b = (int) round( $b1 * $weight + $b2 * ( 1 - $weight ) );
	return '#' . str_pad( strtolower( dechex( $r ) ), 2, '0', STR_PAD_LEFT )
		. str_pad( strtolower( dechex( $g ) ), 2, '0', STR_PAD_LEFT )
		. str_pad( strtolower( dechex( $b ) ), 2, '0', STR_PAD_LEFT );
}

/**
 * Compute the concrete row colors (background / foreground / border) for a
 * given row style + base color.
 *
 *  - `soft`    — a light tint of the base color as the background, the base
 *                color as the text (the default; matches the design reference).
 *  - `solid`   — the base color as the background, white text.
 *  - `outline` — transparent background, the base color as the border + text.
 *
 * @param string $style The row style (soft/solid/outline).
 * @param string $color The base `#RRGGBB` color.
 * @return array{bg: string, fg: string, border: string}
 */
function anishop_buy_box_row_colors( $style, $color ) {
	$style = anishop_buy_box_sanitize_row_style( $style );
	$color = anishop_buy_box_sanitize_row_color( $color );

	if ( 'solid' === $style ) {
		return array(
			'bg'     => $color,
			'fg'     => '#ffffff',
			'border' => $color,
		);
	}

	if ( 'outline' === $style ) {
		return array(
			'bg'     => 'transparent',
			'fg'     => $color,
			'border' => $color,
		);
	}

	// `soft` (default): a light tint of the base color.
	return array(
		'bg'     => anishop_buy_box_mix_hex( $color, '#ffffff', 0.12 ),
		'fg'     => $color,
		'border' => 'transparent',
	);
}

/**
 * The trusted icon keys available for a Buy Box row.
 *
 * Rows never accept raw SVG/HTML — the administrator picks one of these keys
 * and the matching trusted inline SVG is rendered by
 * `anishop_buy_box_icon_svg()`.
 *
 * @return string[]
 */
function anishop_buy_box_icon_keys() {
	return array( 'none', 'check', 'shield', 'truck', 'card', 'return', 'store', 'phone' );
}

/**
 * Get the trusted inline SVG for a row icon key.
 *
 * The SVGs are stroke-based, use `currentColor` (so they inherit the row text
 * color), and are `aria-hidden` (decorative — the row text carries the meaning).
 *
 * @param string $icon_key One of `anishop_buy_box_icon_keys()`.
 * @return string The SVG markup, or an empty string for `none` / unknown keys.
 */
function anishop_buy_box_icon_svg( $icon_key ) {
	$icons = array(
		'check'  => '<path d="M20 6L9 17l-5-5"/>',
		'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
		'truck'  => '<rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
		'card'   => '<rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>',
		'return' => '<polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/>',
		'store'  => '<path d="M3 9l1.5-5h15L21 9"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/>',
		'phone'  => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
	);

	if ( ! isset( $icons[ $icon_key ] ) ) {
		return '';
	}

	return '<svg class="anishop-buy-box__row-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[ $icon_key ] . '</svg>';
}

