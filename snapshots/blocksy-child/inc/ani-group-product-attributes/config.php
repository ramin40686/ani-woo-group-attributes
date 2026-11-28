<?php
/**
 * Anishop Group Product Attributes — configuration layer (Phase 3.1 foundation).
 *
 * This file defines the configuration CONTRACT for the GPA module:
 *
 *  - the module constants (CPT, group-attribute meta key, Theme Mod key,
 *    settings schema version, REST namespace),
 *  - the settings schema (every key a later phase will consume) + safe defaults,
 *  - the single sanitizer that is the validation authority for the settings.
 *
 * Phase 3.1 scope (important):
 *  - This file is READ-ONLY in Phase 3.1. It never renders anything and is only
 *    invoked by the Customizer save path (which stores the Theme Mod).
 *  - The `anishop_gpa_settings` Theme Mod is written only through the Customizer
 *    (D44) and read only through `ani_gpa_get_settings()`.
 *
 * The configuration layer is the single validation authority: all submitted
 * data is passed through `ani_gpa_sanitize_settings()`.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The GPA group template post type (CPT).
 *
 * A GPA group is a reusable template of ordered Global Attribute definition
 * references. Registered with `show_in_menu => false` (no automatic menu); the
 * admin UI is the WooCommerce Products submenu (see bootstrap.php).
 */
define( 'ANI_GPA_CPT', 'ani_gpa_group' );

/**
 * The group meta key that stores the ordered list of Global Attribute
 * definition references (`pa_{slug}`) on a GPA group.
 *
 * Written only through the group save handler (Phase 3.2, nonce + capability
 * protected) and read only through the group editor.
 */
define( 'ANI_GPA_GROUP_ATTRIBUTES_META', '_ani_gpa_group_attributes' );

/**
 * The single Theme Mod key for the GPA settings.
 *
 * The settings are theme-specific and stay isolated to the active child theme
 * (the Theme Mod API stores them inside the child theme's theme_mods option).
 * Never write that option directly.
 */
define( 'ANI_GPA_THEME_MOD', 'anishop_gpa_settings' );

/**
 * The GPA settings schema version.
 *
 * Bumped only when the stored shape needs a migration.
 */
define( 'ANI_GPA_SETTINGS_VERSION', 1 );

/**
 * The REST namespace for the GPA endpoints (Phase 3.3).
 *
 * The Load endpoint is `anishop/v1/gpa/groups/{id}/attributes` (D45). The
 * namespace is defined here as part of the foundation; the route is registered
 * in Phase 3.3.
 */
define( 'ANI_GPA_REST_NAMESPACE', 'anishop/v1' );

/**
 * The allowed `table_style` values.
 *
 * `default` preserves the native WooCommerce/Blocksy appearance (no GPA CSS);
 * `minimal` and `boxed` are the two alternative presentations (D34, D42).
 *
 * @return string[]
 */
function ani_gpa_table_styles() {
	return array( 'default', 'minimal', 'boxed' );
}

/**
 * The default GPA settings (the schema with safe defaults).
 *
 * `table_style = default` means an untouched install (Theme Mod absent) renders
 * the product attributes exactly as native WooCommerce/Blocksy does — the
 * feature-off guarantee.
 *
 * @return array{version: int, table_style: string}
 */
function ani_gpa_get_defaults() {
	return array(
		'version'     => ANI_GPA_SETTINGS_VERSION, // Schema version (not user-controlled).
		'table_style' => 'default', // Native WooCommerce/Blocksy appearance.
	);
}

/**
 * Sanitize the GPA settings (the single validation authority).
 *
 * Rules:
 *  - `table_style` is validated against the allowlist
 *    `default` / `minimal` / `boxed`; any other value falls back to `default`;
 *  - `version` is always pinned to ANI_GPA_SETTINGS_VERSION (never
 *    user-controlled);
 *  - unknown fields are discarded;
 *  - a raw string input (e.g. a single Customizer control value) is treated as
 *    the `table_style` value;
 *  - non-array / non-scalar input returns the full defaults.
 *
 * @param mixed $input Raw settings (an array, or a single `table_style` string).
 * @return array{version: int, table_style: string}
 */
function ani_gpa_sanitize_settings( $input ) {
	// A single Customizer control submits its scalar value (the selected
	// `table_style`), not the full array. Normalize it into the array shape.
	if ( is_string( $input ) ) {
		$input = array( 'table_style' => $input );
	}

	if ( ! is_array( $input ) ) {
		return ani_gpa_get_defaults();
	}

	$table_style = isset( $input['table_style'] ) && is_scalar( $input['table_style'] ) ? sanitize_key( $input['table_style'] ) : 'default';
	if ( ! in_array( $table_style, ani_gpa_table_styles(), true ) ) {
		$table_style = 'default';
	}

	return array(
		'version'     => ANI_GPA_SETTINGS_VERSION,
		'table_style' => $table_style,
	);
}

/**
 * Get the current GPA settings.
 *
 * Returns the defaults whenever the Theme Mod does not exist (or is not an
 * array). A stored value is always passed through the sanitizer, so the
 * returned settings always satisfy the contract.
 *
 * READ-ONLY: this function never writes the Theme Mod.
 *
 * @return array{version: int, table_style: string}
 */
function ani_gpa_get_settings() {
	$stored = get_theme_mod( ANI_GPA_THEME_MOD );
	if ( ! is_array( $stored ) ) {
		return ani_gpa_get_defaults();
	}
	return ani_gpa_sanitize_settings( $stored );
}
