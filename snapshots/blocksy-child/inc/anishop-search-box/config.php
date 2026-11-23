<?php
/**
 * Anishop Search Box — configuration layer.
 *
 * Phase 3.1 (module structure + dashboard integration):
 *  - Theme Mod key and admin tab identifier.
 *  - Default settings (approved Phase 2 §4.2, decision D3).
 *
 * Phase 3.2 (settings form, persistence & validation):
 *  - The single sanitizer `anishop_search_box_sanitize()` — the single
 *    validation authority for all submitted/stored data.
 *  - The Theme Mod read/write path: `anishop_search_box_get_config()` /
 *    `anishop_search_box_save_config()` / `anishop_search_box_reset_config()`.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Theme Mod key for the Search Box settings.
 */
define( 'ANISHOP_SEARCH_BOX_THEME_MOD', 'anishop_search_box' );

/**
 * The tab identifier for the Search Box tab (Appearance → Anishop).
 */
define( 'ANISHOP_SEARCH_BOX_TAB', 'search-box' );

/**
 * The Search Box configuration schema version.
 *
 * Bumped only when the stored shape needs a migration. Pinned by the
 * sanitizer (never user-controlled) and stored alongside the settings
 * (versioned-schema convention, Buy Box precedent).
 */
define( 'ANISHOP_SEARCH_BOX_VERSION', 1 );

/**
 * Default settings (house style defaults, Phase 2 §4.2).
 *
 * Text fields default to an empty string, which means "use the Blocksy
 * engine's default text" — the scoped post-processing only replaces a
 * rendered text when a custom value is set.
 *
 * @return array<string, mixed>
 */
function anishop_search_box_defaults() {
	return array(
		// General.
		'enabled'     => true,
		'placeholder' => '',

		// Appearance (applied as CSS custom properties on the wrapper).
		'height'                      => 40,
		'max_width'                   => 320,
		'dropdown_width'              => 520,
		'mobile_dropdown_top'         => 134,
		'mobile_dropdown_side_margin' => 16,
		'bg_color'            => '#FFFFFF',
		'text_color'          => '#192A3D',
		'placeholder_color'   => '#3A4F66',
		'border_color'        => '#E1E8ED',
		'border_width'        => 1,
		'radius'              => 2,
		'focus_color'         => '#2872FA',
		'icon_size'           => 15,

		// Live results.
		'live_results'    => true,
		'show_thumbs'     => true,
		'show_price'      => true,
		'show_status'     => true,
		'instock_text'    => '',
		'outofstock_text' => '',
		'no_result_text'  => '',
		'error_text'      => '',
	);
}

/**
 * Sanitize a single boolean setting.
 *
 * Accepts `true`, `1`, and `'1'` as `true`; everything else is `false`. This
 * matches the native checkbox form contract (checked submits `1`, unchecked
 * submits `0`). An array (a crafted duplicate-field POST) resolves to its
 * last element.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function anishop_search_box_sanitize_bool( $value ) {
	if ( is_array( $value ) ) {
		$value = end( $value );
	}
	if ( true === $value || 1 === $value || '1' === $value ) {
		return true;
	}
	return false;
}

/**
 * Sanitize a pixel value (a non-negative integer clamped to [min, max]).
 *
 * Approved rule (Phase 2 §16): positive px numbers. The per-field caps are
 * conservative upper bounds that keep the box sane (the form exposes the
 * same min/max attributes); out-of-range or malformed input is clamped to
 * the nearest valid value.
 *
 * @param mixed $value Raw value.
 * @param int   $min   Minimum allowed value (inclusive).
 * @param int   $max   Maximum allowed value (inclusive).
 * @return int
 */
function anishop_search_box_sanitize_px( $value, $min, $max ) {
	$px = absint( $value );
	return min( max( $px, $min ), $max );
}

/**
 * Sanitize a pixel value, falling back to a default for invalid input.
 *
 * Unlike `anishop_search_box_sanitize_px()` (which coerces malformed input to
 * the range minimum via `absint()`), this variant returns the approved
 * default whenever the input is not numeric. Used for settings whose
 * dashboard contract is "missing or invalid → default" (Phase 4.3:
 * `mobile_dropdown_top`). Numeric input is still clamped to [min, max].
 *
 * @param mixed $value   Raw value.
 * @param int   $min     Minimum allowed value (inclusive).
 * @param int   $max     Maximum allowed value (inclusive).
 * @param int   $default Fallback for missing/non-numeric input.
 * @return int
 */
function anishop_search_box_sanitize_px_fallback( $value, $min, $max, $default ) {
	if ( ! is_numeric( $value ) ) {
		return $default;
	}
	$number = (int) $value;

	return min( max( $number, $min ), $max );
}

/**
 * Sanitize a hex color setting.
 *
 * Accepts a `#RRGGBB` hex color (6 hex digits, no alpha) and stores it
 * lowercase. Anything else falls back to the given default (Buy Box
 * precedent).
 *
 * @param mixed  $color   Raw value.
 * @param string $default Fallback color.
 * @return string
 */
function anishop_search_box_sanitize_color( $color, $default ) {
	$color = is_scalar( $color ) ? trim( (string) $color ) : '';
	if ( preg_match( '/^#[0-9a-fA-F]{6}$/', $color ) ) {
		return strtolower( $color );
	}
	return $default;
}

/**
 * Sanitize a short UI text setting.
 *
 * `sanitize_text_field()` is Persian-safe (it strips tags and excess
 * whitespace without touching non-ASCII text). The result is capped at
 * `$max_length` characters (approved rule: text length caps).
 *
 * @param mixed $value      Raw value.
 * @param int   $max_length Maximum allowed length in characters.
 * @return string
 */
function anishop_search_box_sanitize_text( $value, $max_length ) {
	$text = is_scalar( $value ) ? sanitize_text_field( wp_unslash( (string) $value ) ) : '';
	return mb_substr( $text, 0, $max_length );
}

/**
 * Sanitize a full Search Box configuration (the single validation authority).
 *
 * Rules:
 *  - the five feature flags are coerced to explicit booleans;
 *  - the pixel values are clamped to their approved ranges;
 *  - the colors must be `#RRGGBB` (anything else falls back to the default);
 *  - the texts are sanitized and capped at 100 characters;
 *  - `version` is always pinned to ANISHOP_SEARCH_BOX_VERSION (never
 *    user-controlled);
 *  - unknown fields are ignored;
 *  - a missing key always receives its approved default.
 *
 * @param mixed $config Raw configuration (e.g. from the admin form).
 * @return array<string, mixed>
 */
function anishop_search_box_sanitize( $config ) {
	$defaults = anishop_search_box_defaults();
	if ( ! is_array( $config ) ) {
		$config = array();
	}

	$clean = array( 'version' => ANISHOP_SEARCH_BOX_VERSION );

	// General.
	$clean['enabled']     = anishop_search_box_sanitize_bool( isset( $config['enabled'] ) ? $config['enabled'] : $defaults['enabled'] );
	$clean['placeholder'] = anishop_search_box_sanitize_text( isset( $config['placeholder'] ) ? $config['placeholder'] : $defaults['placeholder'], 100 );

	// Appearance.
	$clean['height']                      = anishop_search_box_sanitize_px( isset( $config['height'] ) ? $config['height'] : $defaults['height'], 1, 100 );
	$clean['max_width']                   = anishop_search_box_sanitize_px( isset( $config['max_width'] ) ? $config['max_width'] : $defaults['max_width'], 1, 800 );
	$clean['dropdown_width']              = anishop_search_box_sanitize_px( isset( $config['dropdown_width'] ) ? $config['dropdown_width'] : $defaults['dropdown_width'], 320, 720 );
	$clean['mobile_dropdown_top']         = anishop_search_box_sanitize_px_fallback( isset( $config['mobile_dropdown_top'] ) ? $config['mobile_dropdown_top'] : $defaults['mobile_dropdown_top'], 0, 500, $defaults['mobile_dropdown_top'] );
	$clean['mobile_dropdown_side_margin'] = anishop_search_box_sanitize_px_fallback( isset( $config['mobile_dropdown_side_margin'] ) ? $config['mobile_dropdown_side_margin'] : $defaults['mobile_dropdown_side_margin'], 8, 24, $defaults['mobile_dropdown_side_margin'] );
	$clean['bg_color']            = anishop_search_box_sanitize_color( isset( $config['bg_color'] ) ? $config['bg_color'] : $defaults['bg_color'], $defaults['bg_color'] );
	$clean['text_color']          = anishop_search_box_sanitize_color( isset( $config['text_color'] ) ? $config['text_color'] : $defaults['text_color'], $defaults['text_color'] );
	$clean['placeholder_color']   = anishop_search_box_sanitize_color( isset( $config['placeholder_color'] ) ? $config['placeholder_color'] : $defaults['placeholder_color'], $defaults['placeholder_color'] );
	$clean['border_color']        = anishop_search_box_sanitize_color( isset( $config['border_color'] ) ? $config['border_color'] : $defaults['border_color'], $defaults['border_color'] );
	$clean['border_width']        = anishop_search_box_sanitize_px( isset( $config['border_width'] ) ? $config['border_width'] : $defaults['border_width'], 0, 10 );
	$clean['radius']              = anishop_search_box_sanitize_px( isset( $config['radius'] ) ? $config['radius'] : $defaults['radius'], 0, 50 );
	$clean['focus_color']         = anishop_search_box_sanitize_color( isset( $config['focus_color'] ) ? $config['focus_color'] : $defaults['focus_color'], $defaults['focus_color'] );
	$clean['icon_size']           = anishop_search_box_sanitize_px( isset( $config['icon_size'] ) ? $config['icon_size'] : $defaults['icon_size'], 1, 40 );

	// Live results.
	$clean['live_results']    = anishop_search_box_sanitize_bool( isset( $config['live_results'] ) ? $config['live_results'] : $defaults['live_results'] );
	$clean['show_thumbs']     = anishop_search_box_sanitize_bool( isset( $config['show_thumbs'] ) ? $config['show_thumbs'] : $defaults['show_thumbs'] );
	$clean['show_price']      = anishop_search_box_sanitize_bool( isset( $config['show_price'] ) ? $config['show_price'] : $defaults['show_price'] );
	$clean['show_status']     = anishop_search_box_sanitize_bool( isset( $config['show_status'] ) ? $config['show_status'] : $defaults['show_status'] );
	$clean['instock_text']    = anishop_search_box_sanitize_text( isset( $config['instock_text'] ) ? $config['instock_text'] : $defaults['instock_text'], 100 );
	$clean['outofstock_text'] = anishop_search_box_sanitize_text( isset( $config['outofstock_text'] ) ? $config['outofstock_text'] : $defaults['outofstock_text'], 100 );
	$clean['no_result_text']  = anishop_search_box_sanitize_text( isset( $config['no_result_text'] ) ? $config['no_result_text'] : $defaults['no_result_text'], 100 );
	$clean['error_text']      = anishop_search_box_sanitize_text( isset( $config['error_text'] ) ? $config['error_text'] : $defaults['error_text'], 100 );

	return $clean;
}

/**
 * Get the current Search Box configuration.
 *
 * Returns the defaults whenever the Theme Mod does not exist (or is not an
 * array). A stored value is always passed through the sanitizer, so the
 * returned configuration always satisfies the contract (missing keys
 * receive their approved defaults).
 *
 * READ-ONLY: this function never writes the Theme Mod.
 *
 * @return array<string, mixed>
 */
function anishop_search_box_get_config() {
	$stored = get_theme_mod( ANISHOP_SEARCH_BOX_THEME_MOD );
	if ( ! is_array( $stored ) ) {
		return anishop_search_box_defaults();
	}
	return anishop_search_box_sanitize( $stored );
}

/**
 * Save a Search Box configuration (write path).
 *
 * The raw input is sanitized before being stored, so the Theme Mod always
 * holds a valid configuration. This is the only code path that writes the
 * Theme Mod, and it must only be called from an explicit, nonce-protected
 * admin action.
 *
 * @param mixed $config Raw configuration (e.g. from the admin form).
 * @return array<string, mixed> The sanitized configuration that was stored.
 */
function anishop_search_box_save_config( $config ) {
	$clean = anishop_search_box_sanitize( $config );
	set_theme_mod( ANISHOP_SEARCH_BOX_THEME_MOD, $clean );
	return $clean;
}

/**
 * Remove the stored Search Box configuration (reset to defaults).
 *
 * Only the `anishop_search_box` Theme Mod is removed — no other Anishop
 * setting, Blocksy setting, or Header Builder data is touched. After the
 * reset, `anishop_search_box_get_config()` returns the approved defaults
 * (including `enabled = true`).
 *
 * @return void
 */
function anishop_search_box_reset_config() {
	remove_theme_mod( ANISHOP_SEARCH_BOX_THEME_MOD );
}
