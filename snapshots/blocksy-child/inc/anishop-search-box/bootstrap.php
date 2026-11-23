<?php
/**
 * Anishop Search Box — module bootstrap.
 *
 * Loads the module files and enqueues the frontend assets.
 *
 * Phase 3.3C (frontend):
 *  - The custom Header Builder element registration (Phase 3.3B, unchanged).
 *  - The conditional frontend enqueue: CSS + JS + the per-page text
 *    configuration (`wp_localize_script`), loaded only when the module is
 *    enabled AND the `anishop-header-search` element is placed in the Header
 *    Builder (the actual placement data — `header_placements` Theme Mod — is
 *    the presence check; no shortcode detection).
 *
 * The frontend form itself is rendered by the element view
 * (`inc/panel-builder/header/anishop-header-search/view.php`).
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin.php';

/**
 * The Header Builder element ID rendered by this module (item ID = the
 * subdirectory name under `inc/panel-builder/header/`).
 */
define( 'ANISHOP_SEARCH_BOX_ELEMENT_ID', 'anishop-header-search' );

/**
 * Whether the Search Box element is placed in the Header Builder.
 *
 * Reads the `header_placements` Theme Mod (the Header Builder's saved data)
 * and checks every section, device (desktop/mobile), row, and placement for
 * the element ID. READ-ONLY — the placement data is never modified.
 *
 * @return bool
 */
function anishop_search_box_in_header() {
	$placements = get_theme_mod( 'header_placements' );

	if ( ! is_array( $placements ) || empty( $placements['sections'] ) || ! is_array( $placements['sections'] ) ) {
		return false;
	}

	foreach ( $placements['sections'] as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}

		foreach ( array( 'desktop', 'mobile' ) as $device ) {
			if ( ! isset( $section[ $device ] ) ) {
				continue;
			}

			foreach ( (array) $section[ $device ] as $row ) {
				if ( ! is_array( $row ) || empty( $row['placements'] ) ) {
					continue;
				}

				foreach ( (array) $row['placements'] as $placement ) {
					if ( ! is_array( $placement ) || empty( $placement['items'] ) ) {
						continue;
					}

					foreach ( (array) $placement['items'] as $item ) {
						$item_id = is_array( $item ) && isset( $item['id'] ) ? $item['id'] : (string) $item;

						if ( ANISHOP_SEARCH_BOX_ELEMENT_ID === $item_id ) {
							return true;
						}
					}
				}
			}
		}
	}

	return false;
}

/**
 * Enqueue the frontend assets (conditional).
 *
 * Loaded only when BOTH conditions hold:
 *  1. `anishop_search_box.enabled` is true (the module is on);
 *  2. the `anishop-header-search` element is placed in the Header Builder.
 *
 * When the module is disabled, the element stays registered and placed, but
 * the view renders no visible output and no assets are enqueued.
 *
 * The script is dependency-free vanilla JS (house convention) and runs in
 * the footer; it self-guards (no form in the DOM → no-op).
 *
 * @return void
 */
function anishop_search_box_frontend_enqueue() {
	$config = anishop_search_box_get_config();

	if ( empty( $config['enabled'] ) ) {
		return;
	}

	if ( ! anishop_search_box_in_header() ) {
		return;
	}

	$theme = wp_get_theme();

	/**
	 * Asset suffix: minified in production, readable source in SCRIPT_DEBUG.
	 *
	 * The readable source files (`anishop-search-box.js` / `.css`) are the
	 * single source of truth and are preserved; the `.min` variants are
	 * generated build artifacts. `filemtime()` versioning is applied to the
	 * file actually enqueued, so cache invalidation works for both. If a
	 * `.min` file is missing, the readable source is used as a fallback.
	 */
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// CSS.
	$css_suffix = $suffix;
	$css_file   = get_stylesheet_directory() . '/assets/css/anishop-search-box' . $css_suffix . '.css';
	if ( $css_suffix !== '' && ! file_exists( $css_file ) ) {
		$css_suffix = '';
		$css_file   = get_stylesheet_directory() . '/assets/css/anishop-search-box.css';
	}
	$css_version = file_exists( $css_file ) ? (string) filemtime( $css_file ) : $theme->get( 'Version' );

	wp_enqueue_style(
		'anishop-search-box',
		get_stylesheet_directory_uri() . '/assets/css/anishop-search-box' . $css_suffix . '.css',
		array(),
		$css_version
	);

	// JS.
	$js_suffix = $suffix;
	$js_file   = get_stylesheet_directory() . '/assets/js/anishop-search-box' . $js_suffix . '.js';
	if ( $js_suffix !== '' && ! file_exists( $js_file ) ) {
		$js_suffix = '';
		$js_file   = get_stylesheet_directory() . '/assets/js/anishop-search-box.js';
	}
	$js_version = file_exists( $js_file ) ? (string) filemtime( $js_file ) : $theme->get( 'Version' );

	wp_enqueue_script(
		'anishop-search-box',
		get_stylesheet_directory_uri() . '/assets/js/anishop-search-box' . $js_suffix . '.js',
		array(),
		$js_version,
		true
	);

	/**
	 * Per-page configuration for the frontend script.
	 *
	 * The text settings are applied by the script via SCOPED DOM
	 * post-processing on the header box instances only (decision D5 — no
	 * global `ct_localizations` override). An empty dashboard value means
	 * "use the Blocksy engine's default text" (the script then leaves the
	 * rendered text untouched). `error` falls back to a module default
	 * because the Free engine has no native error state.
	 */
	wp_localize_script(
		'anishop-search-box',
		'anishopSearchBox',
		array(
			'texts'      => array(
				'instock'    => $config['instock_text'],
				'outofstock' => $config['outofstock_text'],
				'noResult'   => $config['no_result_text'],
				'error'      => '' !== $config['error_text']
					? $config['error_text']
					: __( 'There was a problem with the search. Please try again.', 'anishop' ),
				'waiting'    => __( 'We are still searching...', 'anishop' ),
			),
			'watchdogMs' => 8000,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'anishop_search_box_frontend_enqueue', 20 );

/**
 * Register the custom Header Builder element.
 *
 * Phase 3.3B: adds the child theme's `inc/panel-builder/header` directory to
 * the Header Builder's item paths. Each subdirectory becomes a header item
 * (item ID = subdirectory name), so `anishop-header-search` is discovered and
 * listed in the Customizer's available elements.
 *
 * Registration is unconditional: the element stays available in the Header
 * Builder regardless of the module's enabled state. Whether the element
 * renders output is decided by the view via `anishop_search_box.enabled`.
 *
 * @param array $paths Existing item paths.
 * @return array
 */
add_filter(
	'blocksy:header:items-paths',
	function ( $paths ) {
		$paths[] = get_stylesheet_directory() . '/inc/panel-builder/header';
		return $paths;
	}
);
