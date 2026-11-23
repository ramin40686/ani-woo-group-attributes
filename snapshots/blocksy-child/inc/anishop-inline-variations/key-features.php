<?php
/**
 * Anishop inline variations — Key Features (Phase 4C).
 *
 * A per-product, admin-editable list of "key features" (label/value pairs)
 * rendered on the single product page immediately below the Buy Box
 * (add-to-cart layer). This is the Phase 4C "Key Features" feature of the
 * Display Variations Inline module.
 *
 * Data model (all Anishop-owned; the native WooCommerce attribute model is
 * NEVER touched):
 *  - Post meta `anishop_key_features` on the product: an ordered array of
 *    `{ label, value }` entries (max 12). This is the single source of truth
 *    for what is displayed.
 *  - Theme Mod `anishop_key_features_settings`: the global off-switch +
 *    optional title override. Shape `{ enabled: bool, title: string }`.
 *    Absent = `{ enabled: true, title: '' }` (feature on, default title).
 *
 * Architecture:
 *  - `anishop_key_features_sanitize()` is the SINGLE sanitization authority.
 *    Every write (admin save) and every read (meta reader) passes through it.
 *  - The admin UI is a dependency-free repeater (add / remove / move up-down)
 *    with a "pre-fill from product attributes" checklist. Prefilled rows are
 *    editable COPIES — they are never live references to the attribute.
 *  - The frontend layer is a Blocksy custom single-product layer inserted
 *    immediately after `product_add_to_cart` (located by ID, so it is robust
 *    to future layer reordering). It renders nothing when the feature is
 *    disabled or the product has no key features.
 *
 * Scope (important):
 *  - This file is self-contained. It does not modify the Phase 4A/4B config,
 *    admin, or renderer files.
 *  - No new plugins, libraries, external assets, or database tables.
 *  - Text domain: `anishop`.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The post meta key that stores the ordered key-features list on a product.
 */
define( 'ANISHOP_KF_META_KEY', 'anishop_key_features' );

/**
 * The Theme Mod that stores the global off-switch + optional title override.
 *
 * Written only through the Theme Mod API (get_theme_mod / set_theme_mod /
 * remove_theme_mod). Never write the `theme_mods_blocksy-child` option directly.
 */
define( 'ANISHOP_KF_THEME_MOD', 'anishop_key_features_settings' );

/**
 * The nonce action for the key-features save.
 */
define( 'ANISHOP_KF_NONCE_ACTION', 'anishop_key_features_save' );

/**
 * The nonce field name for the key-features save.
 */
define( 'ANISHOP_KF_NONCE_FIELD', 'anishop_key_features_nonce' );

/**
 * The maximum number of key-feature entries per product.
 */
define( 'ANISHOP_KF_MAX_ENTRIES', 12 );

/**
 * The Blocksy single-product custom layer ID.
 */
define( 'ANISHOP_KF_LAYER_ID', 'anishop_key_features' );

/**
 * The Blocksy layer ID that the key-features layer is inserted after.
 */
define( 'ANISHOP_KF_ANCHOR_LAYER_ID', 'product_add_to_cart' );

/**
 * The default (translated) layer title, used when the Theme Mod title is empty.
 *
 * @return string
 */
function anishop_key_features_default_title() {
	return __( 'ویژگی‌های کلیدی', 'anishop' );
}

/**
 * The single sanitization authority for the key-features list.
 *
 * Rules (Section 27.3 of the Phase 3B spec):
 *  - Non-array input → empty array.
 *  - Each entry must be an array with scalar `label` and `value`; each scalar
 *    is unslashed + passed through `sanitize_text_field()`.
 *  - Entries where BOTH label and value are empty are dropped.
 *  - At most `ANISHOP_KF_MAX_ENTRIES` entries are kept (extras dropped).
 *  - The submitted order is preserved.
 *  - No HTML is ever stored (text fields only); output is still escaped.
 *
 * This is used both on write (admin save) and on read (meta reader), so the
 * stored value is always clean and the read path is defensive.
 *
 * @param mixed $raw Raw input (possibly slashed, possibly malformed).
 * @return array[] Ordered list of `{ label: string, value: string }`.
 */
function anishop_key_features_sanitize( $raw ) {
	if ( ! is_array( $raw ) ) {
		return array();
	}

	$clean = array();
	foreach ( $raw as $entry ) {
		if ( count( $clean ) >= ANISHOP_KF_MAX_ENTRIES ) {
			break;
		}
		if ( ! is_array( $entry ) ) {
			continue;
		}

		$label_raw = isset( $entry['label'] ) && is_scalar( $entry['label'] ) ? (string) $entry['label'] : '';
		$value_raw = isset( $entry['value'] ) && is_scalar( $entry['value'] ) ? (string) $entry['value'] : '';

		$label = sanitize_text_field( wp_unslash( $label_raw ) );
		$value = sanitize_text_field( wp_unslash( $value_raw ) );

		if ( '' === $label && '' === $value ) {
			continue;
		}

		$clean[] = array(
			'label' => $label,
			'value' => $value,
		);
	}

	return $clean;
}

/**
 * Read a product's key-features list (always sanitized).
 *
 * @param int $post_id The product post ID.
 * @return array[] Ordered list of `{ label: string, value: string }`.
 */
function anishop_key_features_get( $post_id ) {
	$raw = get_post_meta( $post_id, ANISHOP_KF_META_KEY, true );
	return anishop_key_features_sanitize( $raw );
}

/**
 * Read the global key-features settings (off-switch + title override).
 *
 * Absent Theme Mod = `{ enabled: true, title: '' }` (feature on, default
 * title). The title is sanitized on read so a hand-edited Theme Mod can never
 * inject markup.
 *
 * @return array{ enabled: bool, title: string }
 */
function anishop_key_features_settings_get() {
	$settings = get_theme_mod( ANISHOP_KF_THEME_MOD, array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}

	$enabled = isset( $settings['enabled'] ) ? (bool) $settings['enabled'] : true;
	$title   = isset( $settings['title'] ) && is_scalar( $settings['title'] )
		? sanitize_text_field( wp_unslash( (string) $settings['title'] ) )
		: '';

	return array(
		'enabled' => $enabled,
		'title'   => $title,
	);
}

/**
 * Save the global key-features settings (off-switch + title override).
 *
 * The single write path for the `anishop_key_features_settings` Theme Mod. It
 * only ever writes through the Theme Mod API (set_theme_mod). The `enabled`
 * flag is the global display switch; the existing `title` override is
 * preserved (read back through the reader) so toggling the switch never loses
 * a saved title. Disabling the switch does NOT touch any product's
 * `anishop_key_features` meta — it only changes whether the section renders.
 *
 * @param bool $enabled Whether the Key Features section is displayed.
 * @return void
 */
function anishop_key_features_settings_save( $enabled ) {
	$settings        = anishop_key_features_settings_get();
	$settings['enabled'] = (bool) $enabled;

	set_theme_mod( ANISHOP_KF_THEME_MOD, $settings );
}

/**
 * Build the "pre-fill from product attributes" checklist data.
 *
 * Returns one `{ label, value }` entry per (non-variation attribute, value)
 * pair. Variation attributes are excluded (they are handled by the inline
 * variation display, not key features). Taxonomy attributes use the human-
 * readable WooCommerce label; custom attributes use their name directly.
 *
 * The returned entries are plain data — the admin UI turns a checked item into
 * an editable COPY of a row, never a live reference to the attribute.
 *
 * @param WC_Product|null $product The product.
 * @return array[] List of `{ label: string, value: string }`.
 */
function anishop_key_features_build_prefill( $product ) {
	$prefill = array();

	if ( ! is_object( $product ) || ! method_exists( $product, 'get_attributes' ) ) {
		return $prefill;
	}

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! is_object( $attribute ) || ! method_exists( $attribute, 'get_variation' ) ) {
			continue;
		}

		// Variation attributes are out of scope for key features.
		if ( $attribute->get_variation() ) {
			continue;
		}

		$name = method_exists( $attribute, 'get_name' ) ? (string) $attribute->get_name() : '';
		if ( '' === $name ) {
			continue;
		}

		$is_taxonomy = method_exists( $attribute, 'is_taxonomy' ) && $attribute->is_taxonomy();

		// Human-readable label: taxonomy attributes use the WC label; custom
		// attributes use their name directly.
		$label = ( $is_taxonomy && function_exists( 'wc_attribute_label' ) )
			? wc_attribute_label( $name )
			: $name;

		$values = array();
		if ( $is_taxonomy && method_exists( $attribute, 'get_terms' ) ) {
			$terms  = $attribute->get_terms();
			$values = is_array( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
		} elseif ( method_exists( $attribute, 'get_options' ) ) {
			$values = $attribute->get_options();
		}

		foreach ( (array) $values as $value ) {
			$value = (string) $value;
			if ( '' === $value ) {
				continue;
			}
			$prefill[] = array(
				'label' => $label,
				'value' => $value,
			);
		}
	}

	return $prefill;
}

/**
 * Register the "Key Features" meta box on the product edit screen.
 *
 * Shown for both simple and variable products (the `product` post type).
 *
 * @return void
 */
function anishop_key_features_register_meta_box() {
	add_meta_box(
		'anishop_key_features',
		anishop_key_features_default_title(),
		'anishop_key_features_render_meta_box',
		'product',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'anishop_key_features_register_meta_box' );

/**
 * Render a single key-feature row (used for existing rows and the template).
 *
 * @param int    $index      The row index (ignored for the template).
 * @param array  $entry      The `{ label, value }` entry.
 * @param bool   $is_template Whether this is the hidden <template> row.
 * @return void
 */
function anishop_key_features_admin_row( $index, $entry, $is_template = false ) {
	$label = isset( $entry['label'] ) ? $entry['label'] : '';
	$value = isset( $entry['value'] ) ? $entry['value'] : '';
	$name  = 'anishop_key_features[' . ( $is_template ? '__INDEX__' : (int) $index ) . ']';
	?>
	<li class="anishop-kf-admin__row" data-anishop-kf-item>
		<div class="anishop-kf-admin__row-fields">
			<label>
				<span><?php esc_html_e( 'برچسب', 'anishop' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[label]" value="<?php echo esc_attr( $label ); ?>" class="regular-text">
			</label>
			<label>
				<span><?php esc_html_e( 'مقدار', 'anishop' ); ?></span>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[value]" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
			</label>
		</div>
		<div class="anishop-kf-admin__row-actions">
			<button type="button" class="button anishop-kf-admin__move" data-direction="up" aria-label="<?php esc_attr_e( 'Move up', 'anishop' ); ?>">&#8593;</button>
			<button type="button" class="button anishop-kf-admin__move" data-direction="down" aria-label="<?php esc_attr_e( 'Move down', 'anishop' ); ?>">&#8595;</button>
			<button type="button" class="button anishop-kf-admin__remove"><?php esc_html_e( 'حذف', 'anishop' ); ?></button>
		</div>
	</li>
	<?php
}

/**
 * Render the hidden <template> used by the "Add row" button.
 *
 * @return void
 */
function anishop_key_features_admin_template() {
	echo '<template id="anishop-kf-item-template">';
	anishop_key_features_admin_row( 0, array(), true );
	echo '</template>';
}

/**
 * Render the "Key Features" meta box.
 *
 * @param WP_Post $post The current product post.
 * @return void
 */
function anishop_key_features_render_meta_box( $post ) {
	wp_nonce_field( ANISHOP_KF_NONCE_ACTION, ANISHOP_KF_NONCE_FIELD );

	$features = anishop_key_features_get( $post->ID );
	$product  = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : null;
	$prefill  = anishop_key_features_build_prefill( $product );
	?>
	<div class="anishop-kf-admin">
		<p class="description">
			<?php esc_html_e( 'ویژگی‌های کلیدی محصول را به‌صورت جفت برچسب/مقدار وارد کنید. حداکثر ۱۲ مورد. این لیست بلافاصله زیر دکمهٔ افزودن به سبد نمایش داده می‌شود.', 'anishop' ); ?>
		</p>

		<ul class="anishop-kf-admin__list" id="anishop-kf-items">
			<?php if ( empty( $features ) ) : ?>
				<li class="anishop-kf-admin__empty"><?php esc_html_e( 'هنوز ویژگی‌ای ثبت نشده است.', 'anishop' ); ?></li>
			<?php else : ?>
				<?php foreach ( $features as $index => $feature ) : ?>
					<?php anishop_key_features_admin_row( $index, $feature ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>

		<p class="anishop-kf-admin__actions">
			<button type="button" class="button anishop-kf-admin__add" id="anishop-kf-add"><?php esc_html_e( 'افزودن ردیف', 'anishop' ); ?></button>
			<button type="button" class="button anishop-kf-admin__prefill-toggle" id="anishop-kf-prefill-toggle"><?php esc_html_e( 'دریافت از ویژگی‌های محصول', 'anishop' ); ?></button>
		</p>

		<div class="anishop-kf-admin__prefill" id="anishop-kf-prefill" hidden>
			<h4><?php esc_html_e( 'دریافت از ویژگی‌های محصول', 'anishop' ); ?></h4>
			<?php if ( ! empty( $prefill ) ) : ?>
				<p class="description"><?php esc_html_e( 'موردی را انتخاب کنید تا به‌عنوان یک ردیف جدید (قابل ویرایش) افزوده شود. این عملیات به ویژگی‌های اصلی محصول دست نمی‌زند.', 'anishop' ); ?></p>
				<ul class="anishop-kf-admin__prefill-list">
					<?php foreach ( $prefill as $item ) : ?>
						<li>
							<label>
								<input type="checkbox" class="anishop-kf-admin__prefill-check" data-label="<?php echo esc_attr( $item['label'] ); ?>" data-value="<?php echo esc_attr( $item['value'] ); ?>">
								<?php echo esc_html( $item['label'] ); ?>: <?php echo esc_html( $item['value'] ); ?>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="description anishop-kf-admin__prefill-empty"><?php esc_html_e( 'ویژگی غیرمتغیری برای دریافت وجود ندارد.', 'anishop' ); ?></p>
			<?php endif; ?>
		</div>

		<?php anishop_key_features_admin_template(); ?>
	</div>
	<?php
}

/**
 * Save the key-features list on product save.
 *
 * Guards (in order): nonce, autosave, revision, capability. The submitted
 * value is passed through the single sanitization authority. An empty result
 * deletes the meta (so the product has no key features); a non-empty result
 * updates it.
 *
 * @param int $post_id The product post ID.
 * @return void
 */
function anishop_key_features_save( $post_id ) {
	if ( ! isset( $_POST[ ANISHOP_KF_NONCE_FIELD ] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ ANISHOP_KF_NONCE_FIELD ] ) ), ANISHOP_KF_NONCE_ACTION ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the raw value is passed to the single sanitization authority below.
	$raw   = isset( $_POST['anishop_key_features'] ) ? $_POST['anishop_key_features'] : array();
	$clean = anishop_key_features_sanitize( $raw );

	if ( empty( $clean ) ) {
		delete_post_meta( $post_id, ANISHOP_KF_META_KEY );
	} else {
		update_post_meta( $post_id, ANISHOP_KF_META_KEY, $clean );
	}
}
add_action( 'save_post_product', 'anishop_key_features_save', 10, 1 );

/**
 * Enqueue the admin assets — only on the product edit / new screen.
 *
 * @param string $hook The current admin page hook suffix.
 * @return void
 */
function anishop_key_features_admin_enqueue( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$post_type = '';
	if ( 'post.php' === $hook && isset( $_GET['post'] ) ) {
		$post_type = get_post_type( (int) $_GET['post'] );
	} elseif ( 'post-new.php' === $hook && isset( $_GET['post_type'] ) ) {
		$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
	}
	if ( 'product' !== $post_type ) {
		return;
	}

	$theme = wp_get_theme();
	$css   = get_stylesheet_directory() . '/assets/css/anishop-key-features-admin.css';
	$js    = get_stylesheet_directory() . '/assets/js/anishop-key-features-admin.js';

	wp_enqueue_style(
		'anishop-key-features-admin',
		get_stylesheet_directory_uri() . '/assets/css/anishop-key-features-admin.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : $theme->get( 'Version' )
	);

	wp_enqueue_script(
		'anishop-key-features-admin',
		get_stylesheet_directory_uri() . '/assets/js/anishop-key-features-admin.js',
		array(),
		file_exists( $js ) ? (string) filemtime( $js ) : $theme->get( 'Version' ),
		true
	);

	wp_localize_script(
		'anishop-key-features-admin',
		'anishopKeyFeaturesAdmin',
		array(
			'i18n' => array(
				'confirmRemove' => __( 'Remove this row?', 'anishop' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'anishop_key_features_admin_enqueue' );

/**
 * Enqueue the frontend layer styles — only on single product pages when the
 * feature is enabled.
 *
 * @return void
 */
function anishop_key_features_frontend_enqueue() {
	$settings = anishop_key_features_settings_get();
	if ( empty( $settings['enabled'] ) ) {
		return;
	}

	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$version = wp_get_theme( get_stylesheet_directory() )->get( 'Version' );

	wp_enqueue_style(
		'anishop-key-features',
		get_stylesheet_directory_uri() . '/assets/css/anishop-key-features.css',
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'anishop_key_features_frontend_enqueue' );

/**
 * Insert the key-features layer into the single product layout.
 *
 * The layer is spliced immediately after `product_add_to_cart` (located by ID,
 * so it is robust to future layer reordering). It is only inserted when the
 * global off-switch is on, and never more than once (idempotent).
 *
 * @param array $layout The normalized single product layout layers.
 * @return array
 */
function anishop_key_features_insert_layer( $layout ) {
	$settings = anishop_key_features_settings_get();
	if ( empty( $settings['enabled'] ) ) {
		return $layout;
	}

	if ( ! is_array( $layout ) ) {
		return $layout;
	}

	// Idempotency: do not insert twice.
	foreach ( $layout as $layer ) {
		if ( is_array( $layer ) && ANISHOP_KF_LAYER_ID === $layer['id'] ) {
			return $layout;
		}
	}

	// Locate the anchor layer by ID.
	$anchor_index = null;
	foreach ( $layout as $index => $layer ) {
		if ( is_array( $layer ) && ANISHOP_KF_ANCHOR_LAYER_ID === $layer['id'] ) {
			$anchor_index = $index;
			break;
		}
	}
	if ( null === $anchor_index ) {
		return $layout;
	}

	$new_layer = array(
		'id'      => ANISHOP_KF_LAYER_ID,
		'enabled' => true,
	);

	array_splice( $layout, $anchor_index + 1, 0, array( $new_layer ) );

	return $layout;
}
add_filter( 'blocksy:woocommerce:product-single:layout', 'anishop_key_features_insert_layer', 20 );

/**
 * Render the key-features layer (Blocksy custom layer action).
 *
 * Fires for every custom layer; this handler only acts on the key-features
 * layer ID. It renders nothing when the feature is disabled or the product has
 * no key features (no empty markup).
 *
 * @param array $layer The single product layout layer descriptor.
 * @return void
 */
function anishop_key_features_render_layer( $layer ) {
	if ( ! is_array( $layer ) || ANISHOP_KF_LAYER_ID !== $layer['id'] ) {
		return;
	}

	$settings = anishop_key_features_settings_get();
	if ( empty( $settings['enabled'] ) ) {
		return;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}

	$features = anishop_key_features_get( $post_id );
	if ( empty( $features ) ) {
		return;
	}

	$title = '' !== $settings['title'] ? $settings['title'] : anishop_key_features_default_title();
	?>
	<div class="ct-product-anishop-key-features">
		<span class="ct-module-title"><?php echo esc_html( $title ); ?></span>
		<ul class="anishop-kf-list">
			<?php foreach ( $features as $feature ) : ?>
				<li class="anishop-kf-item">
					<?php if ( '' !== $feature['label'] ) : ?>
						<span class="anishop-kf-label"><?php echo esc_html( $feature['label'] ); ?></span>
					<?php endif; ?>
					<span class="anishop-kf-value"><?php echo esc_html( $feature['value'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
add_action( 'blocksy:woocommerce:product:custom:layer', 'anishop_key_features_render_layer', 20, 1 );

/* =========================================================================
 * GLOBAL DISPLAY SWITCH — Appearance → Anishop → «Key Features» tab
 * =========================================================================
 *
 * A global enable/disable control for the Key Features section, placed in the
 * EXISTING Anishop admin interface (the Appearance → Anishop page that already
 * hosts the Mobile Bottom Navbar and Display Variations Inline tabs). It
 * controls ONLY whether the section is displayed on single-product pages; it
 * never touches any product's `anishop_key_features` meta.
 *
 * The setting is stored in the SAME `anishop_key_features_settings` Theme Mod
 * used by the frontend (the `enabled` flag), so there is no second settings
 * system. It is independent of the Display Variations Inline switch.
 */

/**
 * The tab identifier for the Key Features tab.
 */
define( 'ANISHOP_KF_TAB', 'key-features' );

/**
 * The Appearance → Anishop page hook (shared with the other Anishop tabs).
 */
define( 'ANISHOP_KF_PAGE_HOOK', 'appearance_page_anishop-mobile-nav' );

/**
 * The nonce action for the Key Features admin save.
 */
define( 'ANISHOP_KF_ADMIN_SAVE_NONCE_ACTION', 'anishop_key_features_admin_save' );

/**
 * The nonce field name for the Key Features admin save.
 */
define( 'ANISHOP_KF_ADMIN_SAVE_NONCE_FIELD', 'anishop_kf_admin_save_nonce' );

/**
 * The POST field name that triggers the Key Features admin save.
 */
define( 'ANISHOP_KF_ADMIN_SAVE_FIELD', 'anishop_key_features_admin_save' );

/**
 * Add the Key Features tab to the Anishop admin tab navigation.
 *
 * Hooked to the `anishop_admin_tabs` filter (applied in
 * anishop_iv_admin_render_tab_nav()). This keeps the tab list extensible
 * without modifying the existing Mobile Bottom Navbar / Display Variations
 * Inline tabs.
 *
 * @param array $tabs The existing tab map (tab id => label).
 * @return array
 */
function anishop_kf_admin_add_tab( $tabs ) {
	$tabs[ ANISHOP_KF_TAB ] = __( 'Key Features', 'anishop' );
	return $tabs;
}
add_filter( 'anishop_admin_tabs', 'anishop_kf_admin_add_tab' );

/**
 * Render the Key Features settings tab (global display switch).
 *
 * @return void
 */
function anishop_kf_admin_render_tab() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$settings = anishop_key_features_settings_get();
	$notice   = isset( $_GET['anishop_kf_notice'] ) ? sanitize_key( wp_unslash( $_GET['anishop_kf_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.
	?>
	<?php if ( 'saved' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Key Features settings saved.', 'anishop' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( ANISHOP_KF_ADMIN_SAVE_NONCE_ACTION, ANISHOP_KF_ADMIN_SAVE_NONCE_FIELD ); ?>
		<fieldset class="anishop-iv-admin__fieldset">
			<legend><?php esc_html_e( 'Key Features', 'anishop' ); ?></legend>
			<label class="anishop-iv-admin__checkbox">
				<input type="hidden" name="anishop_key_features[enabled]" value="0">
				<input type="checkbox" name="anishop_key_features[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>>
				<?php esc_html_e( 'Enable Key Features display', 'anishop' ); ?>
			</label>
			<p class="description"><?php esc_html_e( 'When disabled, the Key Features section is hidden on single product pages. Saved Key Features are not deleted and will reappear when the switch is turned back on.', 'anishop' ); ?></p>
		</fieldset>

		<p class="submit">
			<button type="submit" name="<?php echo esc_attr( ANISHOP_KF_ADMIN_SAVE_FIELD ); ?>" value="1" class="button button-primary"><?php esc_html_e( 'Save Changes', 'anishop' ); ?></button>
		</p>
	</form>
	<?php
}

/**
 * Handle the Key Features admin save POST action.
 *
 * Hooked to `load-appearance_page_anishop-mobile-nav` (the shared Anishop
 * page). Flow: POST → capability check → nonce check → write the Theme Mod
 * through the settings save function → redirect back with a notice.
 *
 * Independent of the Mobile Bottom Navbar and Display Variations Inline save
 * handlers (different POST field + nonce); the three coexist on the same page.
 *
 * @return void
 */
function anishop_kf_admin_handle_save() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( ! isset( $_POST[ ANISHOP_KF_ADMIN_SAVE_FIELD ] ) ) {
		return;
	}

	$nonce = isset( $_POST[ ANISHOP_KF_ADMIN_SAVE_NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ ANISHOP_KF_ADMIN_SAVE_NONCE_FIELD ] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, ANISHOP_KF_ADMIN_SAVE_NONCE_ACTION ) ) {
		wp_die( esc_html__( 'The link you followed has expired.', 'anishop' ) );
	}

	$raw = ( isset( $_POST['anishop_key_features'] ) && is_array( $_POST['anishop_key_features'] ) )
		? wp_unslash( $_POST['anishop_key_features'] )
		: array();

	$enabled = ! empty( $raw['enabled'] );
	anishop_key_features_settings_save( $enabled );

	$redirect = add_query_arg(
		array( 'page' => 'anishop-mobile-nav', 'tab' => ANISHOP_KF_TAB, 'anishop_kf_notice' => 'saved' ),
		admin_url( 'themes.php' )
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'load-' . ANISHOP_KF_PAGE_HOOK, 'anishop_kf_admin_handle_save' );
