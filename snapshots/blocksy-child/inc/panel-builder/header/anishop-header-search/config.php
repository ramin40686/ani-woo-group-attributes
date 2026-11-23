<?php
/**
 * Anishop Search Box — Header Builder element configuration.
 *
 * Phase 3.3B: configuration for the custom Header Builder element
 * `anishop-header-search` (display name «آنی باکس جستجو» / "Anishop Search Box").
 *
 * The element is always registered, independent of the module's enabled state;
 * the view decides whether to render output based on `anishop_search_box.enabled`.
 *
 * No `options.php` is provided on purpose: the element exposes no Header
 * Builder options. All Search Box settings live in the Anishop dashboard
 * (Appearance → Anishop → Search Box), stored in the `anishop_search_box`
 * Theme Mod — they are never duplicated here.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config = [
	'name' => __( 'Anishop Search Box', 'anishop' ),
];
