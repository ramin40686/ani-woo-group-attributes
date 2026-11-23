<?php
/**
 * Anishop mobile bottom navigation — icon registry.
 *
 * Project-owned registry of the five existing Anishop mobile-nav icons
 * (home, grid, cart, blog, user). The inline SVG markup is copied exactly
 * from the existing render.php icon registry — no redesign, no optimization,
 * no reformatting.
 *
 * The registry maps icon identifiers to trusted project-owned SVG markup.
 * Theme Mod values continue to contain only icon identifiers; raw SVG is
 * never accepted from configuration.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the project-owned icon registry.
 *
 * Maps each supported icon identifier to its trusted project-owned inline
 * SVG markup (copied exactly from the existing render.php registry).
 *
 * @return array<string, string>
 */
function anishop_mobile_nav_icon_registry() {
	$common = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"';

	return array(
		'home' => '<svg ' . $common . '><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
		'grid' => '<svg ' . $common . '><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
		'cart' => '<svg ' . $common . '><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
		'blog' => '<svg ' . $common . '><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
		'user' => '<svg ' . $common . '><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
	);
}

/**
 * Return the list of supported icon identifiers.
 *
 * @return string[]
 */
function anishop_mobile_nav_icon_keys() {
	return array_keys( anishop_mobile_nav_icon_registry() );
}

/**
 * Return the trusted inline SVG markup for a supported icon identifier.
 *
 * Invalid (or non-string) identifiers safely return an empty string,
 * consistent with the existing render.php implementation.
 *
 * @param string $key Icon identifier.
 * @return string
 */
function anishop_mobile_nav_icon_svg( $key ) {
	if ( ! is_string( $key ) ) {
		return '';
	}

	$registry = anishop_mobile_nav_icon_registry();

	return isset( $registry[ $key ] ) ? $registry[ $key ] : '';
}
