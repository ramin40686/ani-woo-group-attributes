# Anishop Integration Analysis (Phase 1)

> How the GPA module will integrate with the live Anishop Blocksy child theme.
> Provenance: `[LIVE-VERIFIED 2026-11-23/24]` = read-only inspection of the live child theme
> and site · `[STATIC CODE EVIDENCE]` = source reading.

## 1. Child theme structure (integration surface)

`[LIVE-VERIFIED 2026-11-23]` `blocksy-child` v1.1.1 is a **block-theme child** with no
template overrides: `functions.php` (bootstrap-only, 14 `require_once` entries), `style.css`,
`screenshot.jpg`, `inc/`, `assets/`, `languages/`.

- **Module pattern to follow:** `inc/{module}/{config.php, admin.php, render.php, bootstrap.php}`.
  GPA target: `inc/ani-group-product-attributes/` (per project rules).
- **Asset pattern:** `assets/js/ani-group-product-attributes.js` (+ `-admin.js`),
  `assets/css/ani-group-product-attributes.css` (+ `-admin.css`); `filemtime` versioning;
  dependency-free vanilla IIFE JS; `wp_localize_script` for per-page config.
- **Text domain:** `anishop`; Persian `.mo` at `languages/fa_IR.mo` (locale-only filename).
  All admin strings: English source + Persian translation.
- **Settings storage:** Theme Mod with a single sanitizer + versioned schema
  (e.g. `anishop_gpa_settings`), consistent with `anishop_mobile_nav`, `anishop_buy_box`, etc.
- **Admin UI:** Appearance → Anishop page, owned by `anishop-mobile-nav/admin.php`; other
  modules add tabs via the `anishop_admin_tabs` filter. GPA adds its tabs (Groups, Group
  Categories, Settings) through this filter — no new top-level menu.
- **Save handlers:** POST + capability + nonce + single-authority sanitization;
  autosave/revision guards for post meta.

## 2. Existing modules GPA must coexist with (PRESERVE)

`[LIVE-VERIFIED 2026-11-23]` 14 `require_once` modules (full list in
`technical-conventions.md` §2): hero, home-products, mobile-nav (owns the Anishop admin
page), inline-variations (config/admin/render/key-features), buy-box (config/admin/render/
product-rows), search-box. Plus the header search panel under `inc/panel-builder/header/`.

**Coexistence constraints:**

- No duplicate hooks, no parallel save handlers, no changes to these modules.
- Two files are MD5-locked (mobile-nav JS/CSS) — must remain byte-identical.
- Theme Mods in use: `anishop_mobile_nav`, `anishop_inline_variations`,
  `anishop_key_features_settings`, `anishop_buy_box`, `footer_placements`.
  GPA must use its own Theme Mod key.
- Option in use: `anishop_attribute_presentation` (pre-existing attribute-presentation
  option — GPA must not collide; its exact schema was not inspected in Phase 1
  `[NOT VERIFIED]`, flagged for Phase 2).

## 3. Frontend rendering architecture (Blocksy + WooCommerce)

`[STATIC CODE EVIDENCE]` from `anishop-buy-box/render.php` and
`anishop-inline-variations/render.php`:

- Both modules render via **Blocksy custom layers**: the `blocksy:woocommerce:product:custom:layer`
  action (and layout filter `blocksy:woocommerce:product-single:layout` where needed).
  This is the established, Pro-free way to inject content into the single-product page.
- The buy-box module demonstrates the full pattern: config layer (Theme Mod + sanitizer),
  render layer (desktop 3-column + mobile sticky bar), per-product overrides via product
  meta, `filemtime`-versioned assets, RTL-first markup.
- The native WooCommerce attributes table (`shop_attributes`) is rendered by the
  `woocommerce_product_attribute` action inside the product summary; Blocksy's
  WooCommerce single-product layout includes it as a layer.

**GPA rendering options (Phase 2 decision):**

- **R1 — Custom layer (recommended):** render GPA group tables via
  `blocksy:woocommerce:product:custom:layer` at a configurable position (matches the
  "location" setting in ROADMAP §1.4 and the house pattern).
- **R2 — Filter the native table:** hook `woocommerce_product_attribute` /
  `woocommerce_get_product_attribute` to re-render grouped rows inside the native table.
  Tighter to the native markup but couples GPA to WC template internals and to
  `persian-woocommerce`'s possible filters.
- **R3 — Template override:** override `single-product/product-attributes.php` in the
  child theme. Rejected: the child theme currently has **no** template overrides, and a
  block-theme child overriding a WC template is fragile across WC updates.

## 4. Presentation-layer precedent (D9)

`[STATIC CODE EVIDENCE]` The inline-variations module already implements the exact
pattern D9 requires for GPA: it reads native WooCommerce attribute/variation data and
renders a **presentation-only** view without modifying `_product_attributes`, visibility
flags, or variation semantics. GPA will follow the same contract:

- Read: product attributes (taxonomy terms + custom strings), variation attributes.
- Write: only GPA-owned data — CPT `ani_gpa_group`, taxonomy `ani_gpa_group_cat`,
  product meta `_ani_gpa_assigned_groups` / `_ani_gpa_selected_values`, Theme Mod settings.
- Never write: `_product_attributes`, `is_visible`, `is_variation`, term assignments.

## 5. Admin screen plan (evidence-based)

- **CPT `ani_gpa_group`** (attribute groups): list table with drag-and-drop value sorting
  (vanilla JS, no framework); edit screen with value selection from the product's
  available attribute values; group-category assignment (hierarchical taxonomy
  `ani_gpa_group_cat`).
- **Product edit screen:** "create group for this product" toggle + "Manage Attribute
  Groups" (load one group) + "Load Group Categories" (load all groups of a category) +
  per-product group sorting. Attaches to the product data panel
  (`woocommerce_product_options_general_product_data`) or a dedicated metabox — Phase 2
  decision; save via `woocommerce_process_product_meta` with nonce + `edit_product` cap.
- **Settings tab** (Appearance → Anishop): table style, location, table colors,
  "View Attribute" shop-page button toggle (D10: behavior to be replicated from the
  reference plugin — see `reference-plugin-analysis.md` §5).

## 6. Compatibility notes

- **`persian-woocommerce` 10.0.7** (active): localizes WooCommerce; may filter attribute
  output. GPA's own output is independent, but R2 would be affected. `[NOT VERIFIED]`
- **Blocksy 2.1.62 + blocksy-companion 2.1.62:** custom-layer hooks are stable in the
  free version (already used by two existing modules). No Pro dependency.
- **RTL/Persian:** all GPA markup RTL-first (house style: Vazirmatn, accent `#2872FA`,
  radii 2/4px, flat). Admin UI bilingual (English source + Persian `.mo`).
- **Performance:** assets only on product pages / relevant admin screens; no N+1 term
  queries (see `woocommerce-attribute-technical-analysis.md` §7).

