<?php
/**
 * Anishop Group Product Attributes — bootstrap (Phase 3.1 foundation).
 *
 * Wires up the GPA module foundation:
 *  - the `ani_gpa_group` CPT (hidden, no automatic menu),
 *  - the WooCommerce Products submenu (immediately after Attributes, D43),
 *  - the GPA admin page with two independently-saved sections (گروه‌های ویژگی
 *    placeholder + تنظیمات نمایش display settings, D46),
 *  - the display-settings save handler (`admin_post_ani_gpa_save_settings`, D46),
 *  - the Anishop Customizer navigation guide (Appearance → Customize → Anishop,
 *    D46 — the GPA Table Style control was removed from the Customizer),
 *  - the asset handle registration (registration only; no premature enqueue).
 *
 * Phase 3.1 scope (important):
 *  - No group CRUD UI (Phase 3.2). The group-management section is a placeholder.
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
 * Render the GPA admin page (two independently-saved sections, D46).
 *
 * The page contains two visually separated sections:
 *  - **گروه‌های ویژگی** (group management) — a Phase 3.1 placeholder; the full
 *    group list / CRUD UI is implemented in Phase 3.2.
 *  - **تنظیمات نمایش** (display settings) — the `table_style` select, saved via
 *    the dedicated `admin_post_ani_gpa_save_settings` handler.
 *
 * Each section has its own form and save behavior. The group-management section
 * has no form in Phase 3.1 (no CRUD). The display-settings section has its own
 * form and save button. Saving display settings never touches group-management
 * data, and (in Phase 3.2) group-management submissions will not touch display
 * settings.
 *
 * @return void
 */
function ani_gpa_render_group_list() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$settings = ani_gpa_get_settings();
	$notice   = isset( $_GET['ani_gpa_notice'] ) ? sanitize_key( wp_unslash( $_GET['ani_gpa_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, used only to display a notice.
	$can_save = current_user_can( 'edit_theme_options' );
	?>
	<div class="wrap ani-gpa-wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Anishop Attribute Groups', 'anishop' ); ?></h1>
		<hr class="wp-header-end" />

		<?php if ( 'saved' === $notice ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Display settings saved.', 'anishop' ); ?></p></div>
		<?php endif; ?>

		<!-- Section A: Group management (placeholder — Phase 3.2). -->
		<h2><?php esc_html_e( 'گروه‌های ویژگی', 'anishop' ); ?></h2>
		<div class="ani-gpa-section">
			<p><?php esc_html_e( 'The group management interface is under construction (Phase 3.2).', 'anishop' ); ?></p>
		</div>

		<!-- Section B: Display settings. -->
		<h2><?php esc_html_e( 'تنظیمات نمایش', 'anishop' ); ?></h2>
		<div class="ani-gpa-section">
			<?php if ( $can_save ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ani_gpa_save_settings">
					<?php wp_nonce_field( 'ani_gpa_save_settings', 'ani_gpa_settings_nonce' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="ani_gpa_table_style"><?php esc_html_e( 'سبک نمایش جدول ویژگی‌ها', 'anishop' ); ?></label>
							</th>
							<td>
								<select name="table_style" id="ani_gpa_table_style">
									<option value="default" <?php selected( $settings['table_style'], 'default' ); ?>><?php esc_html_e( 'Default', 'anishop' ); ?></option>
									<option value="minimal" <?php selected( $settings['table_style'], 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'anishop' ); ?></option>
									<option value="boxed" <?php selected( $settings['table_style'], 'boxed' ); ?>><?php esc_html_e( 'Boxed', 'anishop' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'استاندارد: ظاهر پیش‌فرض ووکامرس/بلاکسی. حداقلی: نمایش ساده و سبک‌تر. جعبه‌ای: نمایش با حاشیه و کادر مشخص.', 'anishop' ); ?></p>
							</td>
						</tr>
					</table>
					<p class="submit">
						<button type="submit" class="button button-primary"><?php esc_html_e( 'ذخیره تنظیمات نمایش', 'anishop' ); ?></button>
					</p>
				</form>
			<?php else : ?>
				<p><?php esc_html_e( 'You do not have permission to save display settings.', 'anishop' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Handle the display-settings save (D46).
 *
 * A dedicated `admin_post_` handler (POST-only) for the display-settings form.
 * Flow: POST → capability check (`edit_theme_options`) → nonce check → sanitize
 * (single authority: `ani_gpa_sanitize_settings()`) → preserve unrelated Theme Mod
 * keys → `set_theme_mod()` → safe redirect back to the GPA admin page with a
 * success notice.
 *
 * The display-settings form is independent of the group-management section: this
 * handler only writes the `table_style` key (and the schema `version`) and never
 * touches group-management data.
 *
 * @return void
 */
function ani_gpa_save_settings() {
	// POST-only processing.
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		wp_die( esc_html__( 'Invalid request.', 'anishop' ) );
	}

	// Require the theme-options capability (not just the GPA page's group-management capability).
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage theme options.', 'anishop' ) );
	}

	// Nonce verification.
	$nonce = isset( $_POST['ani_gpa_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ani_gpa_settings_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'ani_gpa_save_settings' ) ) {
		wp_die( esc_html__( 'The link you followed has expired.', 'anishop' ) );
	}

	// Sanitize the submitted value (single validation authority).
	$raw       = isset( $_POST['table_style'] ) ? sanitize_text_field( wp_unslash( $_POST['table_style'] ) ) : '';
	$sanitized = ani_gpa_sanitize_settings( $raw );

	// Preserve unrelated keys in the Theme Mod; update only `table_style` + `version`.
	$existing = get_theme_mod( ANI_GPA_THEME_MOD );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	$existing['table_style'] = $sanitized['table_style'];
	$existing['version']     = $sanitized['version'];

	set_theme_mod( ANI_GPA_THEME_MOD, $existing );

	// Safe redirect back to the GPA admin page with a success notice.
	$redirect = add_query_arg(
		array(
			'page'           => 'ani_gpa_group',
			'ani_gpa_notice' => 'saved',
		),
		admin_url( 'edit.php?post_type=product' )
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_ani_gpa_save_settings', 'ani_gpa_save_settings' );

/**
 * Register the Anishop Customizer navigation guide (Appearance → Customize →
 * Anishop, D46).
 *
 * The GPA Table Style control was removed from the Customizer (D46 supersedes
 * D44). The Anishop panel/section is preserved as a **navigation guide** (not a
 * settings container): it displays a concise Persian paragraph explaining where
 * the child-theme modules are configured, with clickable links to each module's
 * configuration page.
 *
 * The guide is rendered by a small custom control
 * (`Ani_GPA_Customizer_Nav_Control`) that has **no** setting — it does not
 * write any data to the database. The control class is declared inside this
 * callback (not at file-include time) because the child-theme bootstrap runs
 * before WordPress loads the Customizer classes; `WP_Customize_Control` is
 * guaranteed available at `customize_register` time. The `class_exists` guard
 * prevents redeclaration if the callback runs more than once (e.g. Customizer
 * AJAX requests).
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @return void
 */
function ani_gpa_customize_register( $wp_customize ) {
	// Declare the navigation-guide control class now that WP_Customize_Control is available.
	if ( ! class_exists( 'Ani_GPA_Customizer_Nav_Control' ) && class_exists( 'WP_Customize_Control' ) ) {
		/**
		 * A Customizer control that renders the Anishop navigation guide.
		 *
		 * This control has **no** setting: it displays static guidance text and
		 * clickable links to the child-theme modules' configuration pages. It does
		 * not write any data to the database.
		 *
		 * @package Anishop
		 */
		class Ani_GPA_Customizer_Nav_Control extends WP_Customize_Control {

			/**
			 * The control type.
			 *
			 * @var string
			 */
			public $type = 'anishop_nav_guide';

			/**
			 * Render the navigation guide (guidance text + clickable links).
			 *
			 * @return void
			 */
			public function render_content() {
				$base_url     = admin_url( 'themes.php?page=anishop-mobile-nav' );
				$destinations = array(
					array(
						'label' => __( 'نوار پیمایش پایین موبایل', 'anishop' ),
						'path'  => __( 'پیشخوان ← نمایش ← Anishop ← تب نوار پیمایش پایین موبایل', 'anishop' ),
						'url'   => $base_url,
					),
					array(
						'label' => __( 'نمایش درون‌خطی تغییرات', 'anishop' ),
						'path'  => __( 'پیشخوان ← نمایش ← Anishop ← تب نمایش درون‌خطی تغییرات', 'anishop' ),
						'url'   => add_query_arg( 'tab', 'inline-variations', $base_url ),
					),
					array(
						'label' => __( 'ویژگی‌های کلیدی', 'anishop' ),
						'path'  => __( 'پیشخوان ← نمایش ← Anishop ← تب ویژگی‌های کلیدی', 'anishop' ),
						'url'   => add_query_arg( 'tab', 'key-features', $base_url ),
					),
					array(
						'label' => __( 'کانتینر Buy Box', 'anishop' ),
						'path'  => __( 'پیشخوان ← نمایش ← Anishop ← تب کانتینر Buy Box', 'anishop' ),
						'url'   => add_query_arg( 'tab', 'buy-box', $base_url ),
					),
					array(
						'label' => __( 'باکس جستجو', 'anishop' ),
						'path'  => __( 'پیشخوان ← نمایش ← Anishop ← تب باکس جستجو', 'anishop' ),
						'url'   => add_query_arg( 'tab', 'search-box', $base_url ),
					),
					array(
						'label' => __( 'اسلایدر هیرو', 'anishop' ),
						'path'  => __( 'پیشخوان ← اسلایدر هیرو', 'anishop' ),
						'url'   => admin_url( 'edit.php?post_type=anishop_hero_slide' ),
					),
					array(
						'label' => __( 'گروه ویژگی‌های آنی', 'anishop' ),
						'path'  => __( 'پیشخوان ← محصولات ← گروه ویژگی‌های آنی', 'anishop' ),
						'url'   => admin_url( 'edit.php?post_type=product&page=ani_gpa_group' ),
					),
				);
				?>
				<span class="description ani-gpa-customizer-nav">
					<p><?php esc_html_e( 'برای سفارشی‌سازی ماژول‌های قالب فرزند بلاکسی، از مسیرهای زیر استفاده کنید:', 'anishop' ); ?></p>
					<ul>
						<?php foreach ( $destinations as $destination ) : ?>
							<li>
								<a href="<?php echo esc_url( $destination['url'] ); ?>"><?php echo esc_html( $destination['label'] ); ?></a>
								<span class="ani-gpa-customizer-nav__path"><?php echo esc_html( $destination['path'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</span>
				<?php
			}
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

	// The navigation-guide section (inside the Anishop panel).
	$wp_customize->add_section(
		'anishop_nav_guide',
		array(
			'title'    => __( 'راهنمای تنظیمات', 'anishop' ),
			'panel'    => 'anishop',
			'priority' => 10,
		)
	);

	// The navigation-guide control (no setting — static guidance only).
	$wp_customize->add_control(
		new Ani_GPA_Customizer_Nav_Control(
			$wp_customize,
			'anishop_nav_guide',
			array(
				'section' => 'anishop_nav_guide',
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
 * Enqueue the GPA admin CSS on the GPA admin page only.
 *
 * The admin CSS provides the minimal, functional styling for the two
 * independently-saved sections (D46). It is enqueued only on the GPA admin page
 * (no other admin screen, no frontend).
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function ani_gpa_enqueue_admin_assets( $hook ) {
	if ( 'product_page_ani_gpa_group' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'ani-group-product-attributes-admin',
		get_stylesheet_directory_uri() . '/assets/css/ani-group-product-attributes-admin.css',
		array(),
		ani_gpa_asset_version( get_stylesheet_directory() . '/assets/css/ani-group-product-attributes-admin.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'ani_gpa_enqueue_admin_assets' );

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

