<?php
/**
 * Anishop Group Product Attributes — bootstrap (Phase 3.1 foundation).
 *
 * Wires up the GPA module foundation:
 *  - the `ani_gpa_group` CPT (hidden, no automatic menu),
 *  - the WooCommerce Products submenu (immediately after Attributes, D43),
 *  - the Customizer settings control (Appearance → Customize → Anishop →
 *    Group Attributes, D44),
 *  - the asset handle registration (registration only; no premature enqueue).
 *
 * Phase 3.1 scope (important):
 *  - No group CRUD UI (Phase 3.2). The submenu callback is a placeholder.
 *  - No product integration (Phase 3.3).
 *  - No frontend rendering / CSS (Phase 3.4).
 *  - Assets are registered (handles reserved) but NOT enqueued.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config.php';

/**
 * Register the `ani_gpa_group` CPT.
 *
 * A GPA group is a reusable template of ordered Global Attribute definition
 * references. The CPT is hidden from the admin menu (`show_in_menu => false`)
 * — the admin UI is the WooCommerce Products submenu registered below.
 *
 * @return void
 */
function ani_gpa_register_post_type() {
	register_post_type(
		ANI_GPA_CPT,
		array(
			'labels'             => array(
				'name'               => __( 'Attribute Groups', 'anishop' ),
				'singular_name'      => __( 'Attribute Group', 'anishop' ),
				'add_new'            => __( 'Add New', 'anishop' ),
				'add_new_item'       => __( 'Add New Attribute Group', 'anishop' ),
				'edit_item'          => __( 'Edit Attribute Group', 'anishop' ),
				'new_item'           => __( 'New Attribute Group', 'anishop' ),
				'view_item'          => __( 'View Attribute Group', 'anishop' ),
				'search_items'       => __( 'Search Attribute Groups', 'anishop' ),
				'not_found'          => __( 'No attribute groups found', 'anishop' ),
				'not_found_in_trash' => __( 'No attribute groups found in Trash', 'anishop' ),
				'all_items'          => __( 'All Attribute Groups', 'anishop' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'supports'            => array( 'title' ),
			'rewrite'             => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
		)
	);
}
add_action( 'init', 'ani_gpa_register_post_type' );

/**
 * Register the GPA submenu under WooCommerce Products.
 *
 * Placed immediately after the existing WooCommerce "Attributes" submenu
 * (D43). WooCommerce registers its Products submenus on `admin_menu` at
 * priority 9; this runs at priority 20 so the GPA item is appended after
 * them. A reorder callback (see `ani_gpa_reorder_products_submenu()`) then
 * guarantees the GPA item sits immediately after "Attributes" regardless of
 * any other plugin's submenu registration order.
 *
 * The exact Persian menu label is `گروه ویژگی‌های آنی` (D43). It is used
 * verbatim (not through a translation function) so the label is guaranteed to
 * display exactly as specified, matching the child theme's existing
 * hardcoded-Persian CPT label convention (see anishop-hero/post-type.php).
 *
 * @return void
 */
function ani_gpa_register_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=product',
		__( 'Anishop Attribute Groups', 'anishop' ),
		'گروه ویژگی‌های آنی',
		'edit_posts',
		'ani_gpa_group',
		'ani_gpa_render_group_list'
	);
}
add_action( 'admin_menu', 'ani_gpa_register_admin_menu', 20 );

/**
 * Ensure the GPA submenu sits immediately after the WooCommerce "Attributes"
 * submenu in the Products menu.
 *
 * This is a robustness net (D43): even if another plugin registers a Products
 * submenu between WooCommerce's "Attributes" and the GPA item, this callback
 * moves the GPA item to be immediately after "Attributes". It runs at a late
 * priority so the `$submenu` array is fully populated first.
 *
 * @return void
 */
function ani_gpa_reorder_products_submenu() {
	global $submenu;

	$parent = 'edit.php?post_type=product';
	if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
		return;
	}

	$gpa_slug = 'ani_gpa_group';
	$attributes = 'product_attributes';

	$items        = array_values( $submenu[ $parent ] );
	$gpa_item     = null;
	$attributes_idx = null;

	foreach ( $items as $index => $item ) {
		if ( $gpa_slug === $item[2] ) {
			$gpa_item = $item;
		}
		if ( $attributes === $item[2] ) {
			$attributes_idx = $index;
		}
	}

	// Nothing to do if either item is missing.
	if ( null === $gpa_item || null === $attributes_idx ) {
		return;
	}

	// Remove the GPA item from its current position.
	$items = array_values(
		array_filter(
			$items,
			function ( $item ) use ( $gpa_slug ) {
				return $gpa_slug !== $item[2];
			}
		)
	);

	// Rebuild the submenu with the GPA item inserted immediately after Attributes.
	$new_items = array();
	foreach ( $items as $item ) {
		$new_items[] = $item;
		if ( $attributes === $item[2] ) {
			$new_items[] = $gpa_item;
		}
	}

	$submenu[ $parent ] = $new_items;
}
add_action( 'admin_menu', 'ani_gpa_reorder_products_submenu', 99 );
/**
 * Render the GPA group list page (placeholder — Phase 3.2).
 *
 * Phase 3.1 registers the submenu so it appears in the correct position in
 * the admin sidebar. The full group list / CRUD UI is implemented in Phase
 * 3.2. This placeholder renders a minimal, non-interactive page (no CRUD).
 *
 * @return void
 */
function ani_gpa_render_group_list() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	?>
	<div class="wrap ani-gpa-wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Anishop Attribute Groups', 'anishop' ); ?></h1>
		<hr class="wp-header-end" />
		<p><?php esc_html_e( 'The group management interface is under construction (Phase 3.2).', 'anishop' ); ?></p>
	</div>
	<?php
}

/**
 * Register the GPA Customizer settings (Appearance → Customize → Anishop →
 * Group Attributes, D44).
 *
 * Both the `Ani_GPA_Table_Style_Setting` and `Ani_GPA_Table_Style_Control`
 * classes are declared inside this callback (not at file-include time) because
 * the child-theme bootstrap runs before WordPress loads the Customizer classes.
 * `WP_Customize_Setting` and `WP_Customize_Control` are guaranteed to be
 * available here — the `WP_Customize_Manager` constructor (which runs before
 * `customize_register` fires) loads them — so the classes can safely extend
 * them. The `class_exists` guards prevent redeclaration if the callback runs
 * more than once (e.g. Customizer AJAX requests).
 *
 * The `table_style` value lives inside the `anishop_gpa_settings` Theme Mod
 * array. The Customizer setting exposes that single key as a scalar: the
 * custom setting's `value()` extracts the key from the array (so the select
 * control displays the correct current value) and its `update()` writes the
 * full sanitized array back to the Theme Mod. This keeps the single approved
 * write path (the Customizer) and the single Theme Mod key intact.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function ani_gpa_customize_register( $wp_customize ) {
	// Declare the setting class now that WP_Customize_Setting is available.
	if ( ! class_exists( 'Ani_GPA_Table_Style_Setting' ) && class_exists( 'WP_Customize_Setting' ) ) {
		/**
		 * A Customizer setting for the `table_style` key within the
		 * `anishop_gpa_settings` Theme Mod array.
		 *
		 * The Theme Mod stores the full settings array, but this setting
		 * exposes a single scalar key (`table_style`) to the Customizer
		 * control. `value()` extracts the key from the array; `update()`
		 * saves the full sanitized array back to the Theme Mod.
		 *
		 * @package Anishop
		 */
		class Ani_GPA_Table_Style_Setting extends WP_Customize_Setting {

			/**
			 * The key within the `anishop_gpa_settings` Theme Mod array.
			 *
			 * @var string
			 */
			protected $setting_key = 'table_style';

			/**
			 * Get the current value of the `table_style` key.
			 *
			 * @return string The current `table_style` value, or `default` if absent.
			 */
			public function value() {
				$stored = get_theme_mod( ANI_GPA_THEME_MOD );
				if ( is_array( $stored ) && isset( $stored[ $this->setting_key ] ) ) {
					return $stored[ $this->setting_key ];
				}
				return 'default';
			}

			/**
			 * Save the full sanitized settings array to the Theme Mod.
			 *
			 * The `$value` is the output of `ani_gpa_sanitize_settings()`
			 * (the full array), applied by the Customizer save path before
			 * `update()` is called.
			 *
			 * @param array $value The sanitized settings array.
			 * @return void
			 */
			protected function update( $value ) {
				set_theme_mod( ANI_GPA_THEME_MOD, $value );
			}
		}
	}

	// Declare the control class now that WP_Customize_Control is available.
	if ( ! class_exists( 'Ani_GPA_Table_Style_Control' ) && class_exists( 'WP_Customize_Control' ) ) {
		/**
		 * A select control for the `table_style` key within the
		 * `anishop_gpa_settings` Theme Mod array.
		 *
		 * The current value is supplied by the `Ani_GPA_Table_Style_Setting`
		 * (which extracts the `table_style` key from the Theme Mod array), so
		 * this control is a plain select control.
		 *
		 * @package Anishop
		 */
		class Ani_GPA_Table_Style_Control extends WP_Customize_Control {

			/**
			 * The control type.
			 *
			 * @var string
			 */
			public $type = 'select';
		}
	}

	// The "Anishop" panel (top-level in the Customizer sidebar).
	if ( ! $wp_customize->get_panel( 'anishop' ) ) {
		$wp_customize->add_panel(
			'anishop',
			array(
				'title'       => __( 'Anishop', 'anishop' ),
				'description' => __( 'Anishop child theme settings.', 'anishop' ),
				'priority'    => 200,
			)
		);
	}

	// The "Group Attributes" section (inside the Anishop panel).
	$wp_customize->add_section(
		'anishop_gpa',
		array(
			'title'    => __( 'Group Attributes', 'anishop' ),
			'panel'    => 'anishop',
			'priority' => 10,
		)
	);

	// The setting (the `table_style` key of the single Theme Mod, as a scalar).
	$wp_customize->add_setting(
		new Ani_GPA_Table_Style_Setting(
			$wp_customize,
			'anishop_gpa_table_style',
			array(
				'type'              => 'theme_mod',
				'theme_mod'         => ANI_GPA_THEME_MOD,
				'default'           => 'default',
				'sanitize_callback' => 'ani_gpa_sanitize_settings',
				'transport'         => 'refresh',
			)
		)
	);

	// The table style select control.
	$wp_customize->add_control(
		new Ani_GPA_Table_Style_Control(
			$wp_customize,
			'anishop_gpa_table_style',
			array(
				'label'    => __( 'Table style', 'anishop' ),
				'section'  => 'anishop_gpa',
				'settings' => 'anishop_gpa_table_style',
				'choices'  => array(
					'default' => __( 'Default', 'anishop' ),
					'minimal' => __( 'Minimal', 'anishop' ),
					'boxed'   => __( 'Boxed', 'anishop' ),
				),
			)
		)
	);
}
add_action( 'customize_register', 'ani_gpa_customize_register' );

/**
 * Get the version for a GPA asset file.
 *
 * Uses `filemtime` (the established child theme convention) when the file
 * exists. In Phase 3.1 the asset files do not exist yet (they are created in
 * Phases 3.2/3.4), so a stable fallback version is used until then. Because
 * this runs on every page load, the version automatically becomes the real
 * `filemtime` as soon as the file is created.
 *
 * @param string $file Absolute path to the asset file.
 * @return string The asset version.
 */
function ani_gpa_asset_version( $file ) {
	if ( file_exists( $file ) ) {
		return (string) filemtime( $file );
	}
	return '1.0.0';
}

/**
 * Register the GPA admin asset handles (registration only — no enqueue).
 *
 * The handles are reserved now so that Phases 3.2/3.3 can enqueue them without
 * re-registering. The asset files are created in those phases; until then the
 * version falls back to a stable value.
 *
 * @return void
 */
function ani_gpa_register_admin_assets() {
	$dir = get_stylesheet_directory();

	wp_register_script(
		'ani-group-product-attributes-admin',
		get_stylesheet_directory_uri() . '/assets/js/ani-group-product-attributes-admin.js',
		array(),
		ani_gpa_asset_version( $dir . '/assets/js/ani-group-product-attributes-admin.js' ),
		true
	);

	wp_register_style(
		'ani-group-product-attributes-admin',
		get_stylesheet_directory_uri() . '/assets/css/ani-group-product-attributes-admin.css',
		array(),
		ani_gpa_asset_version( $dir . '/assets/css/ani-group-product-attributes-admin.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'ani_gpa_register_admin_assets' );

/**
 * Register the GPA frontend asset handle (registration only — no enqueue).
 *
 * The handle is reserved now so that Phase 3.4 can enqueue it on single product
 * pages with a non-default table style. The asset file is created in Phase 3.4.
 *
 * @return void
 */
function ani_gpa_register_frontend_assets() {
	$dir = get_stylesheet_directory();

	wp_register_style(
		'ani-group-product-attributes-styles',
		get_stylesheet_directory_uri() . '/assets/css/ani-group-product-attributes-styles.css',
		array(),
		ani_gpa_asset_version( $dir . '/assets/css/ani-group-product-attributes-styles.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'ani_gpa_register_frontend_assets' );

