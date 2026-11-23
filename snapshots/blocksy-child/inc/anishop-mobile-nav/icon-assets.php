<?php
/**
 * Anishop mobile bottom navigation — project icon assets.
 *
 * B6-15: project-owned SVG icons physically present in the child theme's
 * `assets/icons/` directory. This file is the single source of truth for:
 *
 *  1. READ-ONLY discovery of the available project icons (admin Icon Picker),
 *  2. validation of a stored project-icon identifier (used by the sanitizer),
 *  3. safe resolution of a stored project-icon identifier to sanitized inline
 *     SVG markup (used by the frontend renderer and the admin preview).
 *
 * Storage contract: a project icon is stored in the Theme Mod ONLY as a safe,
 * namespaced identifier of the form `project:<id>` (e.g. `project:test-search`),
 * where `<id>` is the `sanitize_key()` of the SVG file's basename without its
 * extension. No filesystem path, no URL, no raw SVG markup is ever stored.
 *
 * Security model (read-only, no execution):
 *  - Scans a single fixed, project-owned directory
 *    (get_stylesheet_directory() . '/assets/icons/'). It never reads an
 *    arbitrary filesystem path supplied by the browser or any request input.
 *  - Only regular files with a `.svg` extension are considered; directories,
 *    symlinks-to-directories and non-SVG files are ignored.
 *  - The identifier is derived from the file basename only (via pathinfo on a
 *    scandir() entry), so `../` or absolute paths cannot escape the directory.
 *  - When resolving an identifier to a file, the id must round-trip through
 *    sanitize_key() unchanged AND the realpath() of the resolved file must stay
 *    inside the icons directory — so path traversal is impossible.
 *  - The SVG content is read only to be sanitized (scripts, event handlers and
 *    dangerous URL schemes are stripped) before being inlined. It is never
 *    executed, and no raw markup from the Theme Mod is ever echoed.
 *  - No upload, delete, rename, or any other write operation is performed.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The fixed directory that is scanned for project SVG icons.
 *
 * @return string Absolute path to the icons directory (which may not exist).
 */
function anishop_mobile_nav_icon_assets_dir() {
	return get_stylesheet_directory() . '/assets/icons';
}

/**
 * Discover project-owned SVG icon files in the icons directory.
 *
 * READ-ONLY: this function only lists files; it never reads, writes, deletes,
 * renames, or executes anything. The returned list is deterministic (sorted by
 * filename). Each entry contains:
 *
 *  - id:    a safe identifier derived from the filename (sanitize_key of the
 *           basename without its extension), e.g. "test-search.svg" -> "test-search",
 *  - file:  the basename as it exists on disk (e.g. "test-search.svg"),
 *  - url:   the public URL for an <img> preview,
 *  - label: a human-readable label derived from the filename.
 *
 * @return array[] List of discovered icons (empty when the directory is absent
 *                 or contains no valid SVG files).
 */
function anishop_mobile_nav_discover_icon_assets() {
	$dir = anishop_mobile_nav_icon_assets_dir();

	// The directory must exist and be a real directory.
	if ( ! is_dir( $dir ) ) {
		return array();
	}

	$entries = scandir( $dir );
	if ( false === $entries ) {
		return array();
	}

	// Deterministic order: sort by filename.
	sort( $entries, SORT_STRING );

	$icons    = array();
	$seen_ids = array();

	foreach ( $entries as $entry ) {
		// Ignore the standard directory entries.
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}

		$full_path = $dir . '/' . $entry;

		// Only regular files (ignore directories and anything that is not a file).
		if ( ! is_file( $full_path ) ) {
			continue;
		}

		// Only `.svg` files (case-insensitive).
		$ext = strtolower( pathinfo( $entry, PATHINFO_EXTENSION ) );
		if ( 'svg' !== $ext ) {
			continue;
		}

		// The identifier is derived from the basename only — never from any
		// user-supplied path. pathinfo() on a scandir() basename cannot traverse.
		$base = pathinfo( $entry, PATHINFO_FILENAME );
		$id   = sanitize_key( $base );
		if ( '' === $id ) {
			continue; // No safe identifier can be derived: skip.
		}

		// Deduplicate: if two files map to the same id, keep the first (sorted).
		if ( isset( $seen_ids[ $id ] ) ) {
			continue;
		}
		$seen_ids[ $id ] = true;

		$icons[] = array(
			'id'    => $id,
			'file'  => $entry,
			'url'   => get_stylesheet_directory_uri() . '/assets/icons/' . rawurlencode( $entry ),
			'label' => anishop_mobile_nav_icon_asset_label( $base ),
		);
	}

	return $icons;
}

/**
 * Build a human-readable label from a filename stem.
 *
 * "test-search" -> "Test Search".
 *
 * @param string $base Filename without extension.
 * @return string
 */
function anishop_mobile_nav_icon_asset_label( $base ) {
	$label = str_replace( array( '-', '_' ), ' ', (string) $base );
	$label = trim( $label );
	$label = ucwords( $label );
	return '' !== $label ? $label : (string) $base;
}

/**
 * The namespace prefix that marks a stored icon value as a project icon.
 *
 * A project icon is stored as `project:<id>`; a built-in icon is stored as a
 * bare key (home/grid/cart/blog/user). The prefix keeps the two unambiguous
 * and prevents a project file from ever colliding with a built-in icon name.
 *
 * @return string
 */
function anishop_mobile_nav_project_icon_prefix() {
	return 'project:';
}

/**
 * Extract and validate the id from a `project:<id>` icon value.
 *
 * Returns the id when `$icon` is a well-formed project-icon identifier, or
 * `null` otherwise. The id must round-trip through sanitize_key() unchanged,
 * which rejects empty values, path separators, `..`, absolute paths and any
 * other character that could escape the icons directory.
 *
 * @param mixed $icon A stored icon value.
 * @return string|null
 */
function anishop_mobile_nav_project_icon_id( $icon ) {
	$prefix = anishop_mobile_nav_project_icon_prefix();
	if ( ! is_string( $icon ) || 0 !== strpos( $icon, $prefix ) ) {
		return null;
	}
	$id = substr( $icon, strlen( $prefix ) );
	if ( '' === $id || sanitize_key( $id ) !== $id ) {
		return null;
	}
	return $id;
}

/**
 * Whether a stored icon value is a valid, resolvable project icon.
 *
 * True only when the value is a well-formed `project:<id>` identifier AND the
 * corresponding `assets/icons/<id>.svg` file currently exists. A missing file
 * (e.g. the SVG was removed) makes the identifier invalid, so the sanitizer
 * falls back to the type's default icon.
 *
 * @param mixed $icon A stored icon value.
 * @return bool
 */
function anishop_mobile_nav_is_valid_project_icon( $icon ) {
	$id = anishop_mobile_nav_project_icon_id( $icon );
	if ( null === $id ) {
		return false;
	}
	return is_file( anishop_mobile_nav_icon_assets_dir() . '/' . $id . '.svg' );
}

/**
 * Resolve a `project:<id>` icon value to sanitized inline SVG markup.
 *
 * Returns '' for any value that is not a valid, resolvable project icon, so a
 * missing or malformed identifier can never produce a warning, a fatal, or a
 * path outside the icons directory. The SVG is read from the validated file
 * and sanitized before being returned (see anishop_mobile_nav_sanitize_project_svg()).
 *
 * @param mixed $icon A stored icon value.
 * @return string
 */
function anishop_mobile_nav_project_icon_svg( $icon ) {
	$id = anishop_mobile_nav_project_icon_id( $icon );
	if ( null === $id ) {
		return '';
	}

	$dir  = anishop_mobile_nav_icon_assets_dir();
	$file = $dir . '/' . $id . '.svg';

	// Defense in depth: the resolved file must be a real file that stays inside
	// the icons directory (realpath also resolves any symlink).
	$real_dir  = realpath( $dir );
	$real_file = realpath( $file );
	if ( false === $real_dir || false === $real_file ) {
		return '';
	}
	if ( 0 !== strpos( $real_file, $real_dir . DIRECTORY_SEPARATOR ) ) {
		return '';
	}
	if ( ! is_file( $real_file ) ) {
		return '';
	}

	$svg = file_get_contents( $real_file );
	if ( false === $svg ) {
		return '';
	}

	return anishop_mobile_nav_sanitize_project_svg( $svg );
}

/**
 * Sanitize project-owned SVG markup before it is inlined into the page.
 *
 * The SVG files are project-owned (placed by a developer in assets/icons/),
 * but they are still sanitized as a safety net: script elements, event-handler
 * attributes and dangerous URL schemes are removed, and elements that can embed
 * active content are dropped. A value that is not a non-empty string containing
 * an <svg> element yields ''.
 *
 * @param string $svg Raw SVG markup.
 * @return string
 */
function anishop_mobile_nav_sanitize_project_svg( $svg ) {
	if ( ! is_string( $svg ) || '' === trim( $svg ) ) {
		return '';
	}
	if ( false === stripos( $svg, '<svg' ) ) {
		return '';
	}

	// Remove <script> elements (and their content), plus any stray script tags.
	$svg = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $svg );
	$svg = preg_replace( '#</?script\b[^>]*>#i', '', $svg );

	// Remove inline event-handler attributes (onload, onerror, onclick, ...).
	$svg = preg_replace( '/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg );

	// Neutralize dangerous URL schemes in href / xlink:href / src.
	$svg = preg_replace( '/\b(href|xlink:href|src)\s*=\s*(["\']?)\s*(?:javascript|data|vbscript)\s*:[^"\'>\s]*\2/i', '$1=""', $svg );

	// Drop elements that can embed active or external content.
	$svg = preg_replace( '#</?(?:foreignObject|iframe|object|embed)\b[^>]*>#i', '', $svg );

	return $svg;
}
