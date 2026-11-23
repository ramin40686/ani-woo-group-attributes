# WooCommerce Attributes — Technical Dev Notes

> Working notes for the Group Product Attributes module. Items marked `[LIVE-VERIFIED 2026-11-23]` were checked against real Anishop data via read-only WP-CLI. Items marked `[LIVE-VERIFIED 2026-11-23 — WC 11.3.1 source]` were verified by reading the installed WooCommerce plugin source (read-only).

## 1. Storage Model

Product attributes are stored in the **`_product_attributes`** post meta as an associative array keyed by attribute name (taxonomy: `pa_{slug}`; custom: URL-encoded name). `[LIVE-VERIFIED 2026-11-23]`

Each entry:

| Key | Meaning |
|-----|---------|
| `name` | Attribute name (`pa_{slug}` for taxonomy, raw name for custom) |
| `value` | Single string for **custom** attributes (stored vs parsed — see below); empty string for taxonomy attributes (values live in terms) |
| `position` | Display order |
| `is_visible` | 1 = catalog visible |
| `is_variation` | 1 = used as a variation attribute |
| `is_taxonomy` | 1 = product taxonomy attribute, 0 = custom attribute |

### Custom attribute values: stored vs parsed `[LIVE-VERIFIED 2026-11-23 — WC 11.3.1 source]`

- **Stored representation:** one string in the `value` field. WooCommerce's standard convention joins multiple values with the pipe delimiter — `value1 | value2 | value3` (space-pipe-space; `WC_DELIMITER` = `|`, `includes/class-woocommerce.php:570`). The data store writes it via `wc_implode_text_attributes()` in `update_attributes()` (`includes/data-stores/class-wc-product-data-store-cpt.php:1100`).
- **Parsed values:** on read, `read_attributes()` (same file, line 613) passes the stored string to `wc_get_text_attributes( $raw_string )` (`includes/wc-attribute-functions.php:21`), which splits on `|`, trims each part, and drops empty values; the result is exposed as `WC_Product_Attribute::get_options()`.
- **Caveat:** the pipe format is what WooCommerce writes through its own data store. Data created by other tools may differ — do not assume every custom attribute follows it. A full audit of delimiter usage across Anishop products is a Phase 1 item.

### Real Anishop examples `[LIVE-VERIFIED 2026-11-23]`

- **Product 13551** (taxonomy only): `pa_brand`, `pa_color-selector` (`is_variation: 1`), `pa_guarantee`, `pa_registry` (`is_variation: 1`), `pa_at_tedad-sim-kart` — all `is_taxonomy: 1`.
- **Product 9243** (mixed): custom Persian attributes `توان مصرفی`, `وزن`, `مناسب برای`, `ظرفیت`, `زمان کار` (`is_taxonomy: 0`, **single-value free-text** `value` strings e.g. `5 وات`, `130 گرم` — no delimiter present; meta keys URL-encoded e.g. `%d8%aa%d9%88%d8%a7%d9%86-%d9%85%d8%b5%d8%b1%d9%81%db%8c`) + taxonomy `pa_رایحه` (`is_variation: 1`, `value` = `''`, Persian slug).

**Implication for GPA:** groups must support **both** taxonomy and custom attributes; value lists come from terms (`wc_get_product_terms`) for taxonomy attributes and from the parsed `value` string (`wc_get_text_attributes`) for custom attributes.

## 2. Key APIs `[LIVE-VERIFIED 2026-11-23 — WC 11.3.1 source]`

- `WC()->product_factory->get_product( $id )` → `WC_Product`
- `$product->get_attributes( $context = 'view' )` → array of `WC_Product_Attribute`. Valid contexts: **`view`** (default — applies the `woocommerce_product_attributes` filter) and **`edit`** (raw value, no filter). `visible` is **not** a valid context; visibility is filtered by the caller (e.g. `wc_attributes_array_filter_visible` in the frontend template functions).
- `$product->get_attribute( $attribute )` → one attribute as a **string** (taxonomy: term names joined with `, `; text: options joined with ` | `); `''` when not found. Single-parameter signature in WC 11.3.1 — the older `($attribute_id, $echo, $product)` form no longer exists.
- `wc_get_text_attributes( $raw_string )` → parsed value array of a custom attribute (splits on `|`, trims, drops empties)
- `wc_implode_text_attributes( $values )` → stored string (joins with ` | `)
- `wc_get_product_terms( $product_id, $taxonomy, $args = array() )` → terms of a taxonomy attribute (product **ID**, not object)
- `wc_attribute_label( $name, $product = '' )` / `wc_attribute_taxonomy_name( $name )`
- `WC_Product_Attribute::is_taxonomy()`, `->get_name()`, `->get_options()` (parsed values for text attributes), `->get_slugs()`, `->get_terms()`
- Data store: `WC_Product_Data_Store_CPT::update_attributes()` (writes `_product_attributes`) / `read_attributes()` (parses it)

## 3. Key Hooks (integration points)

- `woocommerce_process_product_meta_{post_type}` / `woocommerce_process_product_meta` — product save (metabox save handlers)
- `save_post_{post_type}` — generic save
- `woocommerce_product_options_general_product_data` — product data panel tabs/fields
- Variation attribute UI: `woocommerce_variation_settings`

## 4. Variable Products

- Attributes with `is_variation: 1` drive variation matching; variation meta stores term IDs per attribute.
- **GPA must not alter variation-linked attributes' semantics** — group display is a presentation layer on top of existing attributes.

## 5. Admin UI Surface

- Product Data → Attributes tab (native): add/remove attributes, manage terms, "used for variations" checkbox.
- GPA extends this surface (group loading, value selection, sorting) — architecture decision in Phase 2 (metabox vs native tab extension).

## 6. Open Questions (for Phase 1)

- How the reference plugin stores group↔product assignments (per project-rules.md: `_ani_gpa_assigned_groups` / `_ani_gpa_selected_values`).
- ~~Whether group value selection overrides native attribute visibility or only re-orders/filters display.~~ → **RESOLVED (owner, 2026-11-23, D9):** presentation layer only — the module renders the selected values without modifying native WooCommerce attribute data.
- Shop-page "View Attribute" button behavior → **Owner decision (2026-11-23, D10):** replicate the reference plugin's behavior; determine the exact behavior during Phase 1 investigation.
