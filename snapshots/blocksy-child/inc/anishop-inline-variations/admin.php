<?php
/**
 * Anishop inline variations — admin.
 *
 * Phase 4A: the admin surface for the Inline WooCommerce Variation Display
 * feature. This file hosts:
 *
 *  - Layer A: the Presentation Type field on WooCommerce's native
 *    Products → Attributes → Add / Edit Attribute screens, plus its
 *    nonce-protected save handler (merged into the
 *    `anishop_attribute_presentation` option).
 *  - Layer B: the Appearance → Anishop → «Display Variations Inline» settings
 *    tab (added in sub-stage 4A-4).
 *  - The per-term color HEX field on color-term edit/create screens (added in
 *    sub-stage 4A-5).
 *
 * The configuration layer (config.php) is the single validation authority: all
 * submitted data is passed through its sanitizers. The native WooCommerce
 * Attribute model is NEVER touched — Anishop stores only its own Presentation
 * Metadata.
 *
 * @package Anishop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/config.php';

/* =========================================================================
 * LAYER A — Presentation Type on Products → Attributes (Add / Edit)
 * ========================================================================= */

/**
 * The nonce action for the Layer A Presentation Type save.
 */
define( 'ANISHOP_IV_ATTR_NONCE_ACTION', 'anishop_attribute_presentation_save' );

/**
 * The POST field name for the Presentation Type select.
 */
define( 'ANISHOP_IV_ATTR_FIELD', 'anishop_presentation_type' );

/**
 * The nonce field name for the Layer A save.
 */
define( 'ANISHOP_IV_ATTR_NONCE_FIELD', 'anishop_iv_presentation_nonce' );

/**
 * Render the Presentation Type field on the Add Attribute screen.
 *
 * Hooked to `woocommerce_after_add_attribute_fields` (inside the add form).
 * A new attribute has no existing value, so the select defaults to `default`.
 *
 * @return void
 */
function anishop_iv_admin_render_add_presentation_field() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	anishop_iv_admin_render_presentation_field( 'default' );
}
add_action( 'woocommerce_after_add_attribute_fields', 'anishop_iv_admin_render_add_presentation_field' );

/**
 * Render the Presentation Type field on the Edit Attribute screen.
 *
 * Hooked to `woocommerce_after_edit_attribute_fields` (inside the edit form).
 * Shows the attribute's current Presentation Type (or `default` when unset).
 *
 * @return void
 */
function anishop_iv_admin_render_edit_presentation_field() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$edit      = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, value is re-validated on save.
	$attribute = $edit ? wc_get_attribute( $edit ) : null;
	$taxonomy  = ( $attribute && ! empty( $attribute->slug ) ) ? $attribute->slug : '';

	$current  = anishop_iv_get_attribute_presentation();
	$selected = ( '' !== $taxonomy && isset( $current[ $taxonomy ] ) ) ? $current[ $taxonomy ] : 'default';

	anishop_iv_admin_render_presentation_field( $selected );
}
add_action( 'woocommerce_after_edit_attribute_fields', 'anishop_iv_admin_render_edit_presentation_field' );

/**
 * Render the Presentation Type select (shared by the add + edit forms).
 *
 * The markup matches the surrounding WooCommerce form-field structure. The
 * nonce field is included here so the save handler can verify it.
 *
 * @param string $selected The currently selected presentation type.
 * @return void
 */
function anishop_iv_admin_render_presentation_field( $selected ) {
	$labels = anishop_iv_classification_labels();
	$types  = anishop_iv_classifications();
	?>
	<div class="form-field anishop-iv-presentation-field">
		<label for="<?php echo esc_attr( ANISHOP_IV_ATTR_FIELD ); ?>"><?php esc_html_e( 'Attribute Type', 'anishop' ); ?></label>
		<select name="<?php echo esc_attr( ANISHOP_IV_ATTR_FIELD ); ?>" id="<?php echo esc_attr( ANISHOP_IV_ATTR_FIELD ); ?>">
			<?php foreach ( $types as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $selected, $type ); ?>><?php echo esc_html( $labels[ $type ] ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'The logical type of this attribute (Anishop inline variations). "Color" marks it as a color attribute; this does not change the native WooCommerce attribute type.', 'anishop' ); ?></p>
		<?php wp_nonce_field( ANISHOP_IV_ATTR_NONCE_ACTION, ANISHOP_IV_ATTR_NONCE_FIELD ); ?>
	</div>
	<?php
}

/**
 * Layer A save handler — runs after the attribute is created/updated.
 *
 * Hooked to `woocommerce_attribute_added` ($id, $data) and
 * `woocommerce_attribute_updated` ($id, $data, $old_slug). These official
 * actions fire AFTER the attribute exists, so the taxonomy is available.
 *
 * Gated on: POST-only + `manage_woocommerce` + a valid nonce. This ensures only
 * the admin form save triggers the write (programmatic attribute creation is a
 * no-op). WooCommerce's own attribute save flow is untouched.
 *
 * @param int    $id       Attribute ID.
 * @param array  $data     Attribute data (includes `attribute_name` = slug).
 * @param string $old_slug Previous slug (update only; empty for add).
 * @return void
 */
function anishop_iv_admin_handle_presentation_save( $id, $data, $old_slug = '' ) {
	// POST-only: the admin attribute form is a POST.
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	// Capability.
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	// Nonce (only the Anishop form carries it).
	$nonce = isset( $_POST[ ANISHOP_IV_ATTR_NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ ANISHOP_IV_ATTR_NONCE_FIELD ] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, ANISHOP_IV_ATTR_NONCE_ACTION ) ) {
		return;
	}

	// The attribute slug (no `pa_` prefix) → taxonomy slug.
	$slug = isset( $data['attribute_name'] ) ? sanitize_key( (string) $data['attribute_name'] ) : '';
	if ( '' === $slug ) {
		return;
	}
	$taxonomy = 'pa_' . $slug;

	// On a slug change, drop the stale mapping for the old taxonomy.
	if ( '' !== $old_slug && $old_slug !== $slug ) {
		$old_taxonomy = 'pa_' . sanitize_key( (string) $old_slug );
		if ( $old_taxonomy !== $taxonomy ) {
			anishop_iv_unset_attribute_presentation( $old_taxonomy );
		}
	}

	// Read + merge the submitted presentation type.
	$type = isset( $_POST[ ANISHOP_IV_ATTR_FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ ANISHOP_IV_ATTR_FIELD ] ) ) : 'default';
	anishop_iv_set_attribute_presentation( $taxonomy, $type );
}
add_action( 'woocommerce_attribute_added', 'anishop_iv_admin_handle_presentation_save', 10, 2 );
add_action( 'woocommerce_attribute_updated', 'anishop_iv_admin_handle_presentation_save', 10, 3 );

/* =========================================================================
 * LAYER B — Appearance → Anishop → «Display Variations Inline» tab
 * ========================================================================= */

/**
 * The nonce action for the Layer B save.
 */
define( 'ANISHOP_IV_SAVE_NONCE_ACTION', 'anishop_inline_variations_save' );

/**
 * The POST field name for the Layer B save button.
 */
define( 'ANISHOP_IV_SAVE_FIELD', 'anishop_inline_variations_save' );

/**
 * The nonce field name for the Layer B save.
 */
define( 'ANISHOP_IV_SAVE_NONCE_FIELD', 'anishop_iv_save_nonce' );

/**
 * The tab identifier for the Display Variations Inline tab.
 */
define( 'ANISHOP_IV_TAB', 'inline-variations' );

/**
 * The Appearance → Anishop page hook (shared with the Mobile Bottom Navbar tab).
 */
define( 'ANISHOP_IV_PAGE_HOOK', 'appearance_page_anishop-mobile-nav' );

/**
 * Render the tab navigation bar (Mobile Bottom Navbar | Display Variations Inline).
 *
 * Called from the existing Anishop admin page (inc/anishop-mobile-nav/admin.php).
 *
 * @param string $active_tab The currently active tab ('' = mobile nav).
 * @return void
 */
function anishop_iv_admin_render_tab_nav( $active_tab ) {
	$base_url = admin_url( 'themes.php?page=anishop-mobile-nav' );
	$tabs     = array(
		''                => __( 'Mobile Bottom Navbar', 'anishop' ),
		ANISHOP_IV_TAB    => __( 'Display Variations Inline', 'anishop' ),
	);
	// Extensible: other Anishop features (e.g. Key Features) add their tab here.
	$tabs = apply_filters( 'anishop_admin_tabs', $tabs );
	?>
	<nav class="nav-tab-wrapper anishop-iv-admin__tabs" aria-label="<?php esc_attr_e( 'Anishop settings', 'anishop' ); ?>">
		<?php foreach ( $tabs as $tab_id => $label ) : ?>
			<?php
			$url    = ( '' === $tab_id ) ? $base_url : add_query_arg( 'tab', $tab_id, $base_url );
			$is_active = ( $active_tab === $tab_id );
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="nav-tab<?php echo $is_active ? ' nav-tab-active' : ''; ?>" <?php echo $is_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php
}

/**
 * Human-readable labels for the V1 renderer values.
 *
 * Delegates to the configuration layer (the single source of truth). The fixed
 * `swatch` renderer for color attributes is NOT listed here (it is not
 * selectable).
 *
 * @return array<string, string>
 */
function anishop_iv_admin_renderer_labels() {
	return anishop_iv_renderer_labels();
}

/**
 * Render the Display Variations Inline settings tab (Layer B).
 *
 * Configures GLOBAL renderer behavior only — it does NOT contain per-attribute
 * Presentation Type assignment (that is Layer A, on the WooCommerce attribute
 * edit screen).
 *
 * @return void
 */
function anishop_iv_admin_render_inline_variations_tab() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$config          = anishop_iv_get_config();
	$notice          = isset( $_GET['anishop_iv_notice'] ) ? sanitize_key( wp_unslash( $_GET['anishop_iv_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.
	$renderer_labels = anishop_iv_admin_renderer_labels();
	?>
	<style>
		/* Stack the swatch-shape radio options vertically so each radio is clearly
		 * aligned with its label (prevents selecting the wrong shape). */
		.anishop-iv-admin__radio {
			display: block;
			margin-bottom: 4px;
		}
		.anishop-iv-admin__radio input[type="radio"] {
			margin-inline-end: 6px;
		}
	</style>
	<?php if ( 'saved' === $notice ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Display Variations Inline settings saved.', 'anishop' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="">
		<?php wp_nonce_field( ANISHOP_IV_SAVE_NONCE_ACTION, ANISHOP_IV_SAVE_NONCE_FIELD ); ?>
		<div class="anishop-iv-admin__layout">
			<div class="anishop-iv-admin__main">

				<fieldset class="anishop-iv-admin__fieldset">
					<legend><?php esc_html_e( 'General', 'anishop' ); ?></legend>
					<label class="anishop-iv-admin__checkbox">
						<input type="hidden" name="anishop_inline_variations[enabled]" value="0">
						<input type="checkbox" name="anishop_inline_variations[enabled]" value="1" <?php checked( $config['enabled'] ); ?>>
						<?php esc_html_e( 'Enable Display Variations Inline', 'anishop' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When disabled, the native WooCommerce variation dropdowns are shown exactly as today.', 'anishop' ); ?></p>
				</fieldset>

				<fieldset class="anishop-iv-admin__fieldset">
					<legend><?php esc_html_e( 'Default Attributes', 'anishop' ); ?></legend>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="anishop_iv_default_renderer"><?php esc_html_e( 'Renderer', 'anishop' ); ?></label></th>
								<td>
									<select name="anishop_inline_variations[default_renderer]" id="anishop_iv_default_renderer">
										<?php foreach ( $renderer_labels as $renderer_value => $renderer_label ) : ?>
											<option value="<?php echo esc_attr( $renderer_value ); ?>" <?php selected( $config['default_renderer'], $renderer_value ); ?>><?php echo esc_html( $renderer_label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'How attributes without an explicit type are rendered.', 'anishop' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</fieldset>

				<fieldset class="anishop-iv-admin__fieldset">
					<legend><?php esc_html_e( 'Color Attributes', 'anishop' ); ?></legend>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Renderer', 'anishop' ); ?></th>
								<td>
									<span class="anishop-iv-admin__fixed-renderer"><?php esc_html_e( 'Color Swatch', 'anishop' ); ?></span>
									<p class="description"><?php esc_html_e( 'Color attributes always use the color swatch renderer (fixed).', 'anishop' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Swatch shape', 'anishop' ); ?></th>
								<td>
									<?php foreach ( anishop_iv_color_shape_allowlist() as $shape ) : ?>
										<label class="anishop-iv-admin__radio">
											<input type="radio" name="anishop_inline_variations[color_shape]" value="<?php echo esc_attr( $shape ); ?>" <?php checked( $config['color_shape'], $shape ); ?>>
											<?php echo esc_html( ucfirst( $shape ) ); ?>
										</label>
									<?php endforeach; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="anishop_iv_color_size"><?php esc_html_e( 'Swatch size (px)', 'anishop' ); ?></label></th>
								<td>
									<input type="number" name="anishop_inline_variations[color_size]" id="anishop_iv_color_size" value="<?php echo esc_attr( $config['color_size'] ); ?>" min="24" max="64" step="1">
									<p class="description"><?php esc_html_e( 'Between 24 and 64 pixels.', 'anishop' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</fieldset>

				<fieldset class="anishop-iv-admin__fieldset">
					<legend><?php esc_html_e( 'Custom / Product-level Attributes', 'anishop' ); ?></legend>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="anishop_iv_fallback_renderer"><?php esc_html_e( 'Fallback Renderer', 'anishop' ); ?></label></th>
								<td>
									<select name="anishop_inline_variations[fallback_renderer]" id="anishop_iv_fallback_renderer">
										<?php foreach ( $renderer_labels as $renderer_value => $renderer_label ) : ?>
											<option value="<?php echo esc_attr( $renderer_value ); ?>" <?php selected( $config['fallback_renderer'], $renderer_value ); ?>><?php echo esc_html( $renderer_label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Used for product-level / custom attributes.', 'anishop' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</fieldset>

				<fieldset class="anishop-iv-admin__fieldset">
					<legend><?php esc_html_e( 'Layout', 'anishop' ); ?></legend>
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label for="anishop_iv_spacing"><?php esc_html_e( 'Spacing between controls (px)', 'anishop' ); ?></label></th>
								<td>
									<input type="number" name="anishop_inline_variations[spacing]" id="anishop_iv_spacing" value="<?php echo esc_attr( $config['spacing'] ); ?>" min="0" max="24" step="1">
									<p class="description"><?php esc_html_e( 'Between 0 and 24 pixels.', 'anishop' ); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
				</fieldset>

				<fieldset class="anishop-iv-admin__fieldset">
					<legend><?php esc_html_e( 'Selected value', 'anishop' ); ?></legend>
					<label class="anishop-iv-admin__checkbox">
						<input type="hidden" name="anishop_inline_variations[show_selected]" value="0">
						<input type="checkbox" name="anishop_inline_variations[show_selected]" value="1" <?php checked( $config['show_selected'] ); ?>>
						<?php esc_html_e( 'Show the selected value below the controls', 'anishop' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'A global policy (not per-product). The native selection remains authoritative.', 'anishop' ); ?></p>
				</fieldset>

			</div>
		</div>

		<p class="submit">
			<button type="submit" name="<?php echo esc_attr( ANISHOP_IV_SAVE_FIELD ); ?>" value="1" class="button button-primary"><?php esc_html_e( 'Save Changes', 'anishop' ); ?></button>
		</p>
	</form>
	<?php
}

/**
 * Handle the Layer B save POST action.
 *
 * Hooked to `load-appearance_page_anishop-mobile-nav` (the shared Anishop page).
 * Flow: POST → capability check → nonce check → configuration-layer
 * validation (inside save_config) → redirect back with a notice.
 *
 * Independent of the Mobile Bottom Navbar save handler (different POST field +
 * nonce); the two coexist on the same page.
 *
 * @return void
 */
function anishop_iv_admin_handle_save() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( ! isset( $_POST[ ANISHOP_IV_SAVE_FIELD ] ) ) {
		return;
	}

	$nonce = isset( $_POST[ ANISHOP_IV_SAVE_NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ ANISHOP_IV_SAVE_NONCE_FIELD ] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, ANISHOP_IV_SAVE_NONCE_ACTION ) ) {
		wp_die( esc_html__( 'The link you followed has expired.', 'anishop' ) );
	}

	$raw = ( isset( $_POST['anishop_inline_variations'] ) && is_array( $_POST['anishop_inline_variations'] ) )
		? wp_unslash( $_POST['anishop_inline_variations'] )
		: array();

	// Normalize the hidden(0) + checkbox(1) pairs (last value wins).
	$raw = anishop_iv_admin_normalize_post( $raw );

	anishop_iv_save_config( $raw );

	$redirect = add_query_arg(
		array( 'page' => 'anishop-mobile-nav', 'tab' => ANISHOP_IV_TAB, 'anishop_iv_notice' => 'saved' ),
		admin_url( 'themes.php' )
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'load-' . ANISHOP_IV_PAGE_HOOK, 'anishop_iv_admin_handle_save' );

/**
 * Normalize the raw Layer B POST shape before handing it to the config layer.
 *
 * The enable/disable controls use the standard hidden(0) + checkbox(1) pair, so
 * a checked box submits the same field twice (an array). This only reshapes the
 * form data (last value wins); all validation is done by anishop_iv_sanitize().
 *
 * @param array $raw Raw `anishop_inline_variations` POST array.
 * @return array
 */
function anishop_iv_admin_normalize_post( $raw ) {
	foreach ( array( 'enabled', 'show_selected' ) as $key ) {
		if ( isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ) {
			$raw[ $key ] = end( $raw[ $key ] );
		}
	}
	return $raw;
}

/* =========================================================================
 * TERM HEX — per-term color HEX on color-term edit/create screens
 * ========================================================================= */

/**
 * The nonce action for the term HEX save.
 */
define( 'ANISHOP_IV_TERM_HEX_NONCE_ACTION', 'anishop_term_hex_save' );

/**
 * The POST field name for the term HEX input.
 */
define( 'ANISHOP_IV_TERM_HEX_FIELD', 'anishop_presentation_color_hex' );

/**
 * The nonce field name for the term HEX save.
 */
define( 'ANISHOP_IV_TERM_HEX_NONCE_FIELD', 'anishop_iv_term_hex_nonce' );

/**
 * The slugs of all product attribute taxonomies (`pa_*`).
 *
 * @return string[]
 */
function anishop_iv_admin_attribute_taxonomies() {
	$taxonomies = array();
	$attributes = function_exists( 'wc_get_attribute_taxonomies' ) ? wc_get_attribute_taxonomies() : array();
	foreach ( $attributes as $attribute ) {
		if ( ! empty( $attribute->attribute_name ) ) {
			$taxonomies[] = 'pa_' . $attribute->attribute_name;
		}
	}
	return $taxonomies;
}

/**
 * Whether a taxonomy's resolved Presentation Type is `color`.
 *
 * Checked at render/save time, so a Presentation Type change takes effect
 * immediately (no re-registration needed).
 *
 * @param string $taxonomy The `pa_`-prefixed taxonomy slug.
 * @return bool
 */
function anishop_iv_admin_taxonomy_is_color( $taxonomy ) {
	return 'color' === anishop_iv_get_classification_for_taxonomy( $taxonomy );
}

/**
 * Register the term HEX hooks for every product attribute taxonomy.
 *
 * The field is only rendered (and saved) for taxonomies whose resolved
 * Presentation Type is `color`. The hooks are (re)registered on every
 * request, so a newly added attribute is picked up on the next load.
 *
 * @return void
 */
function anishop_iv_admin_register_term_hex_hooks() {
	foreach ( anishop_iv_admin_attribute_taxonomies() as $taxonomy ) {
		add_action(
			"{$taxonomy}_edit_form_fields",
			function ( $term ) use ( $taxonomy ) {
				anishop_iv_admin_render_term_hex_field( $term, $taxonomy );
			},
			10,
			1
		);
		add_action(
			"{$taxonomy}_add_form_fields",
			function () use ( $taxonomy ) {
				anishop_iv_admin_render_term_hex_field( null, $taxonomy, 'add' );
			}
		);
		add_action(
			"edited_{$taxonomy}",
			function ( $term_id ) use ( $taxonomy ) {
				anishop_iv_admin_handle_term_hex_save( $term_id, $taxonomy );
			},
			10,
			1
		);
		add_action(
			"create_{$taxonomy}",
			function ( $term_id ) use ( $taxonomy ) {
				anishop_iv_admin_handle_term_hex_save( $term_id, $taxonomy );
			},
			10,
			1
		);
	}
}
add_action( 'init', 'anishop_iv_admin_register_term_hex_hooks' );

/**
 * Resolve the capability required to edit a term of the given taxonomy.
 *
 * WooCommerce product attribute taxonomies (`pa_*`) use custom capabilities
 * and do NOT define `cap->edit_term` (it is null), so the core `edit_term`
 * capability is the wrong check here. This returns the taxonomy's own
 * `edit_term` cap when it is set, and otherwise falls back to the WooCommerce
 * `edit_product_terms` cap (which the site's administrators hold).
 *
 * @param string $taxonomy The `pa_`-prefixed taxonomy slug.
 * @return string
 */
function anishop_iv_admin_term_edit_cap( $taxonomy ) {
	$tax = get_taxonomy( $taxonomy );
	if ( $tax && ! empty( $tax->cap->edit_term ) ) {
		return $tax->cap->edit_term;
	}
	return 'edit_product_terms';
}

/**
 * Render the color HEX field on a color-term edit/create form.
 *
 * Only rendered for taxonomies whose resolved Presentation Type is `color`
 * and for users with the term-edit capability (see
 * {@see anishop_iv_admin_term_edit_cap()}).
 *
 * The wrapper markup depends on the form context: the term edit form
 * (`term.php`) is a `form-table`, so the field is a `<tr>` row; the inline
 * "Add New" form on the term list screen (`edit-tags.php`) uses
 * `<div class="form-field">` wrappers, so the field is a `<div>`.
 *
 * @param WP_Term|null $term     The term being edited (null on the create form).
 * @param string       $taxonomy The `pa_`-prefixed taxonomy slug.
 * @param string       $context  Form context: `edit` (table row) or `add` (div).
 * @return void
 */
function anishop_iv_admin_render_term_hex_field( $term, $taxonomy, $context = 'edit' ) {
	if ( ! current_user_can( anishop_iv_admin_term_edit_cap( $taxonomy ) ) ) {
		return;
	}
	if ( ! anishop_iv_admin_taxonomy_is_color( $taxonomy ) ) {
		return;
	}

	$current = ( $term instanceof WP_Term ) ? anishop_iv_get_term_hex( $term->term_id ) : '';
	$add     = ( 'add' === $context );
	?>
	<?php if ( $add ) : ?>
	<div class="form-field anishop-iv-term-hex-field">
		<label for="<?php echo esc_attr( ANISHOP_IV_TERM_HEX_FIELD ); ?>"><?php esc_html_e( 'Color', 'anishop' ); ?></label>
	<?php else : ?>
	<tr class="form-field anishop-iv-term-hex-field">
		<th scope="row">
			<label for="<?php echo esc_attr( ANISHOP_IV_TERM_HEX_FIELD ); ?>"><?php esc_html_e( 'Color', 'anishop' ); ?></label>
		</th>
		<td>
	<?php endif; ?>
		<input
			type="text"
			id="<?php echo esc_attr( ANISHOP_IV_TERM_HEX_FIELD ); ?>"
			name="<?php echo esc_attr( ANISHOP_IV_TERM_HEX_FIELD ); ?>"
			value="<?php echo esc_attr( $current ); ?>"
			class="anishop-iv-term-hex"
			placeholder="#RRGGBB"
		/>
		<p class="description"><?php esc_html_e( 'The swatch color for this option (Anishop inline variations). Pick from the palette, choose any color from the spectrum, or type a HEX value (#RRGGBB). Leave empty for a neutral swatch.', 'anishop' ); ?></p>
		<?php wp_nonce_field( ANISHOP_IV_TERM_HEX_NONCE_ACTION, ANISHOP_IV_TERM_HEX_NONCE_FIELD ); ?>
	<?php if ( $add ) : ?>
	</div>
	<?php else : ?>
		</td>
	</tr>
	<?php endif; ?>
	<?php
}

/**
 * Term HEX save handler — runs after the term is created/updated.
 *
 * Hooked to `edited_{$taxonomy}` ($term_id, $tt_id, $deleted) and
 * `create_{$taxonomy}` ($term_id, $tt_id, $parent).
 *
 * Gated on: POST-only + the term-edit capability (see
 * {@see anishop_iv_admin_term_edit_cap()}) + a valid nonce + a color-type
 * taxonomy. This ensures only the Anishop term form save triggers the write
 * (quick edit, bulk edit, and programmatic term saves are no-ops). The
 * native term save flow is untouched.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy The `pa_`-prefixed taxonomy slug.
 * @return void
 */
function anishop_iv_admin_handle_term_hex_save( $term_id, $taxonomy ) {
	// POST-only: the admin term form is a POST.
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	// Capability.
	if ( ! current_user_can( anishop_iv_admin_term_edit_cap( $taxonomy ) ) ) {
		return;
	}

	// Only color-type taxonomies carry the field.
	if ( ! anishop_iv_admin_taxonomy_is_color( $taxonomy ) ) {
		return;
	}

	// Nonce (only the Anishop term form carries it).
	$nonce = isset( $_POST[ ANISHOP_IV_TERM_HEX_NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ ANISHOP_IV_TERM_HEX_NONCE_FIELD ] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, ANISHOP_IV_TERM_HEX_NONCE_ACTION ) ) {
		return;
	}

	$raw = isset( $_POST[ ANISHOP_IV_TERM_HEX_FIELD ] ) ? wp_unslash( $_POST[ ANISHOP_IV_TERM_HEX_FIELD ] ) : '';
	anishop_iv_save_term_hex( (int) $term_id, $raw );
}

/**
 * The standard color palette for the term color picker.
 *
 * A convenience palette of common digital-product colors (black, white, gray,
 * silver, gold, red, orange, yellow, green, blue, navy, purple, pink, brown).
 * It is passed to the core `wpColorPicker` via its `palettes` option and does
 * NOT limit the selectable colors — the Iris spectrum + HEX input remain fully
 * available for any custom color.
 *
 * @return string[]
 */
function anishop_iv_admin_color_palette() {
	return array(
		'#000000', // مشکی (black).
		'#FFFFFF', // سفید (white).
		'#808080', // خاکستری (gray).
		'#C0C0C0', // نقره‌ای (silver).
		'#D4AF37', // طلایی (gold).
		'#E53935', // قرمز (red).
		'#FB8C00', // نارنجی (orange).
		'#FDD835', // زرد (yellow).
		'#43A047', // سبز (green).
		'#1E88E5', // آبی (blue).
		'#1A237E', // سرمه‌ای (navy).
		'#8E24AA', // بنفش (purple).
		'#EC407A', // صورتی (pink).
		'#795548', // قهوه‌ای (brown).
	);
}

/**
 * Build the inline script that initializes the term color picker with the
 * convenience palette.
 *
 * The core `wpColorPicker` widget is configured with the `palettes` option (an
 * array of HEX strings) so the picker shows a row of clickable, keyboard-
 * accessible swatches. The Iris spectrum + HEX input remain fully available,
 * so the palette is a convenience — it does NOT limit the selectable colors.
 *
 * @return string
 */
function anishop_iv_admin_term_hex_inline_script() {
	$config = array(
		'palettes' => anishop_iv_admin_color_palette(),
	);
	return 'jQuery(function($){ $(".anishop-iv-term-hex").wpColorPicker(' . wp_json_encode( $config ) . '); });';
}

/**
 * Enqueue the WordPress core color picker on the color-term edit/create screen.
 *
 * Only loaded when the current screen is a term screen for a taxonomy whose
 * classification is `color` and the user can edit its terms. The two relevant
 * screens are the term list (`edit-tags.php`, which carries the inline "Add
 * New" form) and the term edit screen (`term.php`). In this WordPress version
 * the `admin_enqueue_scripts` hook suffix is the script basename
 * (`edit-tags.php` / `term.php`), NOT `edit-tags-php`.
 * Uses the core `wp-color-picker` asset (no new library). The picker is
 * configured with a convenience palette of common digital-product colors via
 * the core `palettes` option — the Iris spectrum + HEX input remain fully
 * available for any custom color.
 *
 * @param string $hook The current admin page hook suffix.
 * @return void
 */
function anishop_iv_admin_enqueue_term_hex_assets( $hook ) {
	if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return;
	}

	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen detection.
	if ( '' === $taxonomy || ! anishop_iv_admin_taxonomy_is_color( $taxonomy ) ) {
		return;
	}
	if ( ! current_user_can( anishop_iv_admin_term_edit_cap( $taxonomy ) ) ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_add_inline_script( 'wp-color-picker', anishop_iv_admin_term_hex_inline_script() );
}
add_action( 'admin_enqueue_scripts', 'anishop_iv_admin_enqueue_term_hex_assets' );


