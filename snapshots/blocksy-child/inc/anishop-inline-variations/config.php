<?php
/**
 * Anishop inline variations — configuration layer.
 *
 * Phase 4A (V1 architecture, corrected in 4A-R): the single authoritative
 * configuration/data model for the Inline WooCommerce Variation Display
 * feature. This file defines:
 *
 *  - the V1 attribute classifications (default / color — a classification,
 *    NOT a renderer),
 *  - the V1 renderers (dropdown / chip / radio for default + fallback
 *    attributes; `swatch` is FIXED for color attributes, not selectable),
 *  - Layer A: the `anishop_attribute_presentation` option (stores ONLY
 *    explicit `color` classifications, keyed by taxonomy slug; absence =
 *    default),
 *  - Layer B: the `anishop_inline_variations` Theme Mod (global renderer
 *    behavior),
 *  - the per-term color HEX metadata key + validation,
 *  - the deterministic legacy (pre-4A-R) data migration.
 *
 * Scope (important):
 *  - This file is the single validation authority. All admin code passes data
 *    through the sanitizers here; no other code validates these values.
 *  - Nothing in this file renders the frontend. Frontend rendering is Phase 4B.
 *  - The native WooCommerce Attribute model is NEVER touched: Anishop stores
 *    only its own classification metadata in the namespaced option / Theme
 *    Mod / term meta.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The single option that stores the per-global-attribute classification.
 *
 * Shape: { taxonomy_slug: 'color' } — ONLY explicit color classifications are
 * stored, e.g. [ 'pa_color' => 'color' ]. A taxonomy absent from the option
 * is `default`.
 *
 * Keys are `product_attribute` taxonomy slugs (the `pa_` prefix is part of the
 * key).
 */
define( 'ANISHOP_IV_OPTION', 'anishop_attribute_presentation' );

/**
 * The single Theme Mod that stores the global renderer behavior (Layer B).
 *
 * Written only through the Theme Mod API (get_theme_mod / set_theme_mod /
 * remove_theme_mod). Never write the `theme_mods_blocksy-child` option directly.
 */
define( 'ANISHOP_IV_THEME_MOD', 'anishop_inline_variations' );

/**
 * The per-term color HEX metadata key (only meaningful when the attribute's
 * classification is `color`).
 */
define( 'ANISHOP_IV_TERM_HEX_KEY', 'anishop_presentation_color_hex' );

/**
 * The data model version (bumped in 4A-R when the V1 model was corrected).
 */
define( 'ANISHOP_IV_VERSION', 2 );

/**
 * The V1 attribute classifications (allowlist). `color` is a classification,
 * not a renderer.
 *
 * @return string[]
 */
function anishop_iv_classifications() {
	return array( 'default', 'color' );
}

/**
 * Human-readable (Persian) admin labels for the V1 classifications.
 *
 * @return array<string, string>
 */
function anishop_iv_classification_labels() {
	return array(
		'default' => __( 'پیش‌فرض (Default)', 'anishop' ),
		'color'   => __( 'رنگ (Color)', 'anishop' ),
	);
}

/**
 * The allowed renderer identifiers for the default + fallback renderers.
 *
 * Color attributes always use the fixed `swatch` renderer (not selectable).
 *
 * @return string[]
 */
function anishop_iv_renderer_allowlist() {
	return array( 'dropdown', 'chip', 'radio' );
}

/**
 * Human-readable (Persian) admin labels for the renderers.
 *
 * @return array<string, string>
 */
function anishop_iv_renderer_labels() {
	return array(
		'dropdown' => __( 'Dropdown (native select)', 'anishop' ),
		'chip'     => __( 'Button / label chip', 'anishop' ),
		'radio'    => __( 'Radio group', 'anishop' ),
	);
}

/**
 * The fixed renderer for color-classified attributes.
 *
 * @return string
 */
function anishop_iv_color_renderer() {
	return 'swatch';
}

/**
 * The allowed color swatch shapes.
 *
 * @return string[]
 */
function anishop_iv_color_shape_allowlist() {
	return array( 'circle', 'square' );
}

/**
 * The set of valid `product_attribute` taxonomy slugs (with the `pa_` prefix).
 *
 * Built from wc_get_attribute_taxonomies(). Used to validate the keys of the
 * `anishop_attribute_presentation` option. Returns an empty set when
 * WooCommerce is not active (nothing is valid in that case).
 *
 * @return array<string, bool>
 */
function anishop_iv_valid_taxonomy_slugs() {
	$slugs = array();
	if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
		$taxonomies = wc_get_attribute_taxonomies();
		if ( is_array( $taxonomies ) ) {
			foreach ( $taxonomies as $tax ) {
				if ( ! empty( $tax->attribute_name ) ) {
					$slugs[ 'pa_' . $tax->attribute_name ] = true;
				}
			}
		}
	}
	return $slugs;
}

/**
 * Sanitize the `anishop_attribute_presentation` option value.
 *
 * V1 model: the option stores ONLY explicit `color` classifications, keyed by
 * taxonomy slug. Rules:
 *  - keys must be existing `product_attribute` taxonomy slugs;
 *  - only the `color` value is kept (a `default` value means "absence", so it
 *    is dropped; any other value is invalid and dropped);
 *  - unknown keys / invalid values are dropped;
 *  - non-array input → empty array.
 *
 * This also acts as the deterministic legacy migration: pre-4A-R entries
 * (`default` / `button` / `radio` values) are dropped, leaving only `color`.
 *
 * @param mixed $raw Raw option value.
 * @return array<string, string>
 */
function anishop_iv_sanitize_attribute_presentation( $raw ) {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$valid_slugs = anishop_iv_valid_taxonomy_slugs();
	$clean       = array();

	foreach ( $raw as $taxonomy_slug => $type ) {
		$taxonomy_slug = sanitize_key( (string) $taxonomy_slug );
		if ( '' === $taxonomy_slug || empty( $valid_slugs[ $taxonomy_slug ] ) ) {
			continue; // Unknown / invalid taxonomy slug: drop.
		}
		$type = sanitize_key( (string) $type );
		if ( 'color' !== $type ) {
			continue; // Only explicit `color` is stored; default/other → drop.
		}
		$clean[ $taxonomy_slug ] = $type;
	}

	return $clean;
}

/**
 * Get the current per-attribute Presentation Type map (READ-ONLY).
 *
 * Returns an empty array when the option is absent or not an array. A stored
 * value is always passed through the sanitizer, so the returned map always
 * satisfies the contract.
 *
 * @return array<string, string>
 */
function anishop_iv_get_attribute_presentation() {
	$stored = get_option( ANISHOP_IV_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		return array();
	}
	return anishop_iv_sanitize_attribute_presentation( $stored );
}

/**
 * Replace the entire per-attribute Presentation Type map (sanitized).
 *
 * This is the low-level writer. Prefer anishop_iv_set_attribute_presentation()
 * for a single-attribute merge. The raw input is sanitized before being stored.
 *
 * @param mixed $raw Raw map (taxonomy_slug => presentation_type).
 * @return array<string, string> The sanitized map that was stored.
 */
function anishop_iv_save_attribute_presentation( $raw ) {
	$clean = anishop_iv_sanitize_attribute_presentation( $raw );
	update_option( ANISHOP_IV_OPTION, $clean );
	return $clean;
}

/**
 * Set (or clear) a single attribute's classification in the existing map.
 *
 * V1 model: the option stores ONLY explicit `color` classifications.
 * Preserves all other attributes' values (never replaces unrelated mappings).
 * - A valid taxonomy slug + `color` → the entry is set.
 * - A valid taxonomy slug + `default` (or any other value) → the entry is
 *   removed (absence = default).
 * - An invalid taxonomy slug → no change (returns the current map).
 *
 * @param string $taxonomy_slug The `pa_`-prefixed taxonomy slug.
 * @param string $classification The classification (`color` or `default`).
 * @return array<string, string> The updated map.
 */
function anishop_iv_set_attribute_presentation( $taxonomy_slug, $classification ) {
	$current       = anishop_iv_get_attribute_presentation();
	$taxonomy_slug = sanitize_key( (string) $taxonomy_slug );
	$classification = sanitize_key( (string) $classification );

	if ( '' === $taxonomy_slug || empty( anishop_iv_valid_taxonomy_slugs()[ $taxonomy_slug ] ) ) {
		return $current; // Invalid taxonomy slug: no change.
	}

	if ( 'color' === $classification ) {
		$current[ $taxonomy_slug ] = 'color';
	} else {
		unset( $current[ $taxonomy_slug ] ); // default / invalid: drop the entry.
	}

	update_option( ANISHOP_IV_OPTION, $current );
	return $current;
}

/**
 * Remove a single attribute's Presentation Type mapping (e.g. on slug change).
 *
 * @param string $taxonomy_slug The `pa_`-prefixed taxonomy slug to remove.
 * @return array<string, string> The updated map.
 */
function anishop_iv_unset_attribute_presentation( $taxonomy_slug ) {
	$current       = anishop_iv_get_attribute_presentation();
	$taxonomy_slug = sanitize_key( (string) $taxonomy_slug );
	if ( '' === $taxonomy_slug || ! isset( $current[ $taxonomy_slug ] ) ) {
		return $current;
	}
	unset( $current[ $taxonomy_slug ] );
	update_option( ANISHOP_IV_OPTION, $current );
	return $current;
}

/**
 * Resolve the V1 classification for a taxonomy.
 *
 * Returns `color` when the taxonomy has an explicit color classification;
 * otherwise `default` (absence = default). This is a classification, NOT a
 * renderer — renderer resolution is Layer B's job.
 *
 * @param string $taxonomy_slug The `pa_`-prefixed taxonomy slug.
 * @return string `color` or `default`.
 */
function anishop_iv_get_classification_for_taxonomy( $taxonomy_slug ) {
	$map           = anishop_iv_get_attribute_presentation();
	$taxonomy_slug = sanitize_key( (string) $taxonomy_slug );
	if ( isset( $map[ $taxonomy_slug ] ) && 'color' === $map[ $taxonomy_slug ] ) {
		return 'color';
	}
	return 'default';
}

/**
 * The default Layer B configuration (V1).
 *
 * `enabled` defaults to false (the inline UI is off until explicitly enabled).
 * `default_renderer` defaults to `dropdown` (for Default attributes).
 * `fallback_renderer` defaults to `chip` (for Custom / Product-level
 * attributes). Color attributes always use the fixed `swatch` renderer (not
 * configurable).
 *
 * @return array
 */
function anishop_iv_get_defaults() {
	return array(
		'version'           => ANISHOP_IV_VERSION,
		'enabled'           => false,
		'default_renderer'  => 'dropdown',
		'fallback_renderer' => 'chip',
		'color_shape'       => 'circle',
		'color_size'        => 32,
		'spacing'           => 8,
		'show_selected'     => true,
	);
}

/**
 * Normalize a form value to an explicit boolean.
 *
 * The enable/disable controls use the standard hidden(0) + checkbox(1) pair, so
 * a checked box may submit "1" and an unchecked one "0".
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function anishop_iv_sanitize_bool( $value ) {
	return ( '1' === $value || 'true' === $value || true === $value || 1 === $value );
}

/**
 * Sanitize the Layer B configuration (single validation authority, V1).
 *
 * Every field is validated against its allowlist / range; an invalid value falls
 * back to its per-field default. Unknown fields are ignored. Non-array input →
 * full defaults.
 *
 * Backward-safe: a legacy (pre-4A-R) configuration carrying `renderer_map` /
 * `fallback_presentation` is mapped onto the V1 fields (`default_renderer` /
 * `fallback_renderer`) so reading old data never breaks.
 *
 * @param mixed $raw Raw configuration (e.g. from the admin form).
 * @return array
 */
function anishop_iv_sanitize( $raw ) {
	$defaults = anishop_iv_get_defaults();
	if ( ! is_array( $raw ) ) {
		return $defaults;
	}

	$enabled = anishop_iv_sanitize_bool( isset( $raw['enabled'] ) ? $raw['enabled'] : false );

	// default_renderer: allowlist {dropdown, chip, radio}.
	$default_renderer = '';
	if ( isset( $raw['default_renderer'] ) ) {
		$candidate = sanitize_key( (string) $raw['default_renderer'] );
		if ( in_array( $candidate, anishop_iv_renderer_allowlist(), true ) ) {
			$default_renderer = $candidate;
		}
	}
	if ( '' === $default_renderer ) {
		// Legacy safety net: fall back to the old renderer_map['default'].
		$legacy         = ( isset( $raw['renderer_map']['default'] ) && is_string( $raw['renderer_map']['default'] ) ) ? sanitize_key( $raw['renderer_map']['default'] ) : '';
		$default_renderer = in_array( $legacy, anishop_iv_renderer_allowlist(), true ) ? $legacy : $defaults['default_renderer'];
	}

	// fallback_renderer: allowlist {dropdown, chip, radio}.
	$fallback_renderer = '';
	if ( isset( $raw['fallback_renderer'] ) ) {
		$candidate = sanitize_key( (string) $raw['fallback_renderer'] );
		if ( in_array( $candidate, anishop_iv_renderer_allowlist(), true ) ) {
			$fallback_renderer = $candidate;
		}
	}
	if ( '' === $fallback_renderer ) {
		// Legacy safety net: map the old fallback_presentation (a presentation
		// type) onto a renderer.
		$legacy_map      = array( 'default' => 'dropdown', 'button' => 'chip', 'radio' => 'radio', 'color' => 'chip' );
		$legacy          = ( isset( $raw['fallback_presentation'] ) && is_string( $raw['fallback_presentation'] ) ) ? sanitize_key( $raw['fallback_presentation'] ) : '';
		$fallback_renderer = isset( $legacy_map[ $legacy ] ) ? $legacy_map[ $legacy ] : $defaults['fallback_renderer'];
	}

	// color_shape: allowlist {circle, square}.
	$color_shape = isset( $raw['color_shape'] ) ? sanitize_key( (string) $raw['color_shape'] ) : $defaults['color_shape'];
	if ( ! in_array( $color_shape, anishop_iv_color_shape_allowlist(), true ) ) {
		$color_shape = $defaults['color_shape'];
	}

	// color_size: absint, clamped 24–64.
	$color_size = isset( $raw['color_size'] ) ? absint( $raw['color_size'] ) : $defaults['color_size'];
	$color_size = max( 24, min( 64, $color_size ) );

	// spacing: absint, clamped 0–24.
	$spacing = isset( $raw['spacing'] ) ? absint( $raw['spacing'] ) : $defaults['spacing'];
	$spacing = max( 0, min( 24, $spacing ) );

	// show_selected: explicit bool (default ON).
	$show_selected = anishop_iv_sanitize_bool( isset( $raw['show_selected'] ) ? $raw['show_selected'] : true );

	return array(
		'version'           => ANISHOP_IV_VERSION,
		'enabled'           => $enabled,
		'default_renderer'  => $default_renderer,
		'fallback_renderer' => $fallback_renderer,
		'color_shape'       => $color_shape,
		'color_size'        => $color_size,
		'spacing'           => $spacing,
		'show_selected'     => $show_selected,
	);
}

/**
 * Get the current Layer B configuration (READ-ONLY).
 *
 * Returns the defaults whenever the Theme Mod does not exist (or is not an
 * array) → the inline UI is disabled and the site renders exactly as today.
 * A stored value is always passed through the sanitizer.
 *
 * @return array
 */
function anishop_iv_get_config() {
	$stored = get_theme_mod( ANISHOP_IV_THEME_MOD );
	if ( ! is_array( $stored ) ) {
		return anishop_iv_get_defaults();
	}
	return anishop_iv_sanitize( $stored );
}

/**
 * Save the Layer B configuration (the only writer for the Theme Mod).
 *
 * The raw input is sanitized before being stored. Must only be called from an
 * explicit admin action.
 *
 * @param mixed $raw Raw configuration.
 * @return array The sanitized configuration that was stored.
 */
function anishop_iv_save_config( $raw ) {
	$clean = anishop_iv_sanitize( $raw );
	set_theme_mod( ANISHOP_IV_THEME_MOD, $clean );
	return $clean;
}

/**
 * Remove the stored Layer B configuration (reset to defaults).
 *
 * @return void
 */
function anishop_iv_reset_config() {
	remove_theme_mod( ANISHOP_IV_THEME_MOD );
}

/**
 * Migrate the legacy (pre-4A-R) `anishop_attribute_presentation` option to the
 * V1 model.
 *
 * The V1 option stores ONLY explicit `color` classifications. Any entry whose
 * value is not `color` (e.g. the legacy `default` / `button` / `radio`) or whose
 * taxonomy slug is no longer valid is removed. Deterministic + idempotent: a
 * clean option is left untouched.
 *
 * @return void
 */
function anishop_iv_migrate_option() {
	$raw = get_option( ANISHOP_IV_OPTION, false );
	if ( ! is_array( $raw ) || empty( $raw ) ) {
		return;
	}

	$valid_slugs = anishop_iv_valid_taxonomy_slugs();
	$cleaned     = array();
	$changed     = false;

	foreach ( $raw as $slug => $value ) {
		$slug = sanitize_key( (string) $slug );
		if ( 'color' === $value && isset( $valid_slugs[ $slug ] ) ) {
			$cleaned[ $slug ] = 'color';
		} else {
			$changed = true;
		}
	}

	if ( ! $changed ) {
		return;
	}

	if ( empty( $cleaned ) ) {
		delete_option( ANISHOP_IV_OPTION );
	} else {
		update_option( ANISHOP_IV_OPTION, $cleaned );
	}
}

/**
 * Migrate the legacy (pre-4A-R) `anishop_inline_variations` Theme Mod to the
 * V1 schema.
 *
 * Detects the old schema (carries `renderer_map` / `fallback_presentation`, or
 * `version` < 2) and maps it onto the V1 fields. Deterministic + idempotent: a
 * V1 Theme Mod is left untouched.
 *
 * @return void
 */
function anishop_iv_migrate_theme_mod() {
	$raw = get_theme_mod( ANISHOP_IV_THEME_MOD, false );
	if ( ! is_array( $raw ) ) {
		return;
	}

	$is_old = isset( $raw['renderer_map'] )
		|| isset( $raw['fallback_presentation'] )
		|| ( isset( $raw['version'] ) && (int) $raw['version'] < ANISHOP_IV_VERSION );
	if ( ! $is_old ) {
		return;
	}

	$defaults        = anishop_iv_get_defaults();
	$renderer_allow  = anishop_iv_renderer_allowlist();
	$shape_allow     = anishop_iv_color_shape_allowlist();
	$legacy_renderer = array( 'default' => 'dropdown', 'button' => 'chip', 'radio' => 'radio', 'color' => 'chip' );

	$new = array(
		'version'           => ANISHOP_IV_VERSION,
		'enabled'           => anishop_iv_sanitize_bool( isset( $raw['enabled'] ) ? $raw['enabled'] : false ),
		'default_renderer'  => $defaults['default_renderer'],
		'fallback_renderer' => $defaults['fallback_renderer'],
		'color_shape'       => $defaults['color_shape'],
		'color_size'        => $defaults['color_size'],
		'spacing'           => $defaults['spacing'],
		'show_selected'     => anishop_iv_sanitize_bool( isset( $raw['show_selected'] ) ? $raw['show_selected'] : true ),
	);

	// default_renderer ← old renderer_map['default'].
	if ( isset( $raw['renderer_map']['default'] ) && is_string( $raw['renderer_map']['default'] ) ) {
		$dr = sanitize_key( $raw['renderer_map']['default'] );
		if ( in_array( $dr, $renderer_allow, true ) ) {
			$new['default_renderer'] = $dr;
		}
	}

	// fallback_renderer ← old fallback_presentation (presentation → renderer).
	if ( isset( $raw['fallback_presentation'] ) && is_string( $raw['fallback_presentation'] ) ) {
		$fp = sanitize_key( $raw['fallback_presentation'] );
		if ( isset( $legacy_renderer[ $fp ] ) ) {
			$new['fallback_renderer'] = $legacy_renderer[ $fp ];
		}
	}

	// color_shape.
	if ( isset( $raw['color_shape'] ) && is_string( $raw['color_shape'] ) ) {
		$cs = sanitize_key( $raw['color_shape'] );
		if ( in_array( $cs, $shape_allow, true ) ) {
			$new['color_shape'] = $cs;
		}
	}

	// color_size (clamped).
	if ( isset( $raw['color_size'] ) ) {
		$new['color_size'] = max( 24, min( 64, absint( $raw['color_size'] ) ) );
	}

	// spacing (clamped).
	if ( isset( $raw['spacing'] ) ) {
		$new['spacing'] = max( 0, min( 24, absint( $raw['spacing'] ) ) );
	}

	set_theme_mod( ANISHOP_IV_THEME_MOD, $new );
}

/**
 * Run the deterministic legacy → V1 data migration (idempotent).
 *
 * Hooked to `init`. Each sub-migration is a no-op once the data is already on
 * the V1 model, so this is safe to run on every request.
 *
 * @return void
 */
function anishop_iv_run_migration() {
	anishop_iv_migrate_option();
	anishop_iv_migrate_theme_mod();
}
add_action( 'init', 'anishop_iv_run_migration' );

/**
 * Sanitize a color HEX value.
 *
 * Accepts only `#RRGGBB` (exactly six hexadecimal digits, case-insensitive).
 * Alpha (`#RRGGBBAA`) is NOT accepted. The value is normalized to uppercase.
 * An invalid or empty value returns an empty string (absence).
 *
 * @param mixed $raw Raw value.
 * @return string The normalized `#RRGGBB` value, or '' when invalid/empty.
 */
function anishop_iv_sanitize_hex( $raw ) {
	$value = is_string( $raw ) ? trim( $raw ) : '';
	if ( preg_match( '/^#[0-9A-Fa-f]{6}$/', $value ) ) {
		return strtoupper( $value );
	}
	return '';
}

/**
 * Get a term's color HEX (READ-ONLY). Returns '' when absent/invalid.
 *
 * @param int $term_id Term ID.
 * @return string
 */
function anishop_iv_get_term_hex( $term_id ) {
	$value = get_term_meta( (int) $term_id, ANISHOP_IV_TERM_HEX_KEY, true );
	return anishop_iv_sanitize_hex( $value );
}

/**
 * Save a term's color HEX (the only writer for the term meta).
 *
 * An empty/invalid value removes the meta (absence). Must only be called from
 * an explicit admin action.
 *
 * @param int   $term_id Term ID.
 * @param mixed $raw     Raw HEX value.
 * @return string The normalized value that was stored, or '' when removed.
 */
function anishop_iv_save_term_hex( $term_id, $raw ) {
	$clean   = anishop_iv_sanitize_hex( $raw );
	$term_id = (int) $term_id;
	if ( '' === $clean ) {
		delete_term_meta( $term_id, ANISHOP_IV_TERM_HEX_KEY );
	} else {
		update_term_meta( $term_id, ANISHOP_IV_TERM_HEX_KEY, $clean );
	}
	return $clean;
}
