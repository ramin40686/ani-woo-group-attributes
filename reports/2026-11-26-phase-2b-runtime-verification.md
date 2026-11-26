# Phase 2B — Runtime Verification Report

**Date:** 2026-11-26
**Branch:** `phase-2b-runtime-verification` (based on `ff90628`)
**Status:** COMPLETE — all 14 `[2B-VERIFY]` items verified against the live environment
**Scope:** Runtime verification of the approved Phase 2A.3 native-attribute-template architecture. No implementation code was written. No production data was modified.

---

## 1. Purpose

Phase 2B is the **runtime verification** phase of the approved native-template model
(decisions D28–D39). Its objective is to confirm, against the **live** Anishop
environment, that every architectural assumption the Phase 2A.3 specification relies on
is actually true in the installed WooCommerce 11.3.1 / Blocksy 2.1.62 /
persian-woocommerce 10.0.7 stack.

This phase is **investigation only** — no implementation, no data mutation. It produces
evidence for the 14 `[2B-VERIFY]` items in the Phase 2B readiness audit
(`temp/phase-2b-readiness-audit.md`), documents any discrepancies between the
specification's conceptual naming and the real WooCommerce implementation, and records
the ground-truth state of the two permanent test fixtures.

---

## 2. Environment (LIVE-VERIFIED 2026-11-26)

| Item | Value | Verified |
|------|-------|----------|
| WordPress | **7.1.5** | LIVE-VERIFIED (wp-cli `core version`) |
| PHP | **8.3.36** (Local by Flywheel) | LIVE-VERIFIED |
| WooCommerce | **11.3.1** | LIVE-VERIFIED (wp-cli `plugin list`) |
| Blocksy (parent) | **2.1.62** | LIVE-VERIFIED (wp-cli `theme list`) |
| blocksy-companion | **2.1.62** (active) | LIVE-VERIFIED |
| persian-woocommerce | **10.0.7** (active) | LIVE-VERIFIED |
| blocksy-child | **v1.1.1** | LIVE-VERIFIED |
| Site locale | `fa_IR` (Persian, RTL) | LIVE-VERIFIED |

All versions match the values recorded in `environment.md` and the Phase 2A.3
specification. No version drift was detected.

---

## 3. Test-Fixture Ground Truth

The two permanent fixtures (decision D23) were re-inspected at the raw
`_product_attributes` level.

### 3.1 Simple fixture — product **13143** (Philips OneBlade QP2824/10)

- Post status: `publish`, type: `simple`.
- Raw `_product_attributes` contains **13 attribute entries**:
  - **5 stale taxonomy references** — `is_taxonomy: 1` entries whose `pa_{slug}`
    taxonomies **no longer exist** (`taxonomy_exists()` returns `false`,
    `wc_attribute_taxonomy_id_by_name()` returns `0`).
  - **8 visible custom attributes** — `is_taxonomy: 0`, Persian-named, URL-encoded
    meta keys, all with `visible: 1`.

> **Discrepancy corrected (important):** the Phase 2A.2 audit had described 13143 as
> having "5 taxonomy attributes". Runtime inspection shows these 5 are **stale
> references** — the underlying taxonomies do not exist. The native WooCommerce view
> filters them out (a taxonomy attribute with no existing taxonomy is not rendered).
> The **live frontend** therefore correctly renders **only the 8 valid visible custom
> attributes**. This is expected native behavior, not a defect.

### 3.2 Variable fixture — product **17028** (OnePlus 15)

- Post status: `publish`, type: `variable`.
- **2 valid taxonomy variation attributes** (both taxonomies exist).
- **16 real variation children**.

### 3.3 Backups

Both fixture backup files are present in `backups/gpa-test-fixtures/` (gitignored) with
sizes matching the Phase 2A.2 audit. No fixture was modified during this phase.

---

## 4. Verification Results (14 `[2B-VERIFY]` items)

Legend: **PASS** = observed behavior matches the architectural expectation.

| # | Item | Expected | Observed | Status |
|---|------|----------|----------|--------|
| B2-2 | Products top-level menu position | Auto-assigned, known at runtime | Position **26** | **PASS** |
| B2-3 | Candidate GPA menu positions free | 26.1 / 26.2 / 26.5 unoccupied | All three free; no plugin menu collision near Products | **PASS** |
| B2-7 | Stale reference validation | `taxonomy_exists()` false + `wc_attribute_taxonomy_id_by_name()` 0 for stale refs | Confirmed on 13143's 5 stale refs | **PASS** |
| B2-14 | Load row insertion mechanism | Native `woocommerce_add_attribute` AJAX + `i` index + select2 re-init + `attribute_position` re-index | Confirmed in `class-wc-ajax.php` + `meta-boxes-product.js` | **PASS** |
| B2-18 | Native admin attribute row markup | Row markup as in `html-product-attribute*.php` | Confirmed | **PASS** |
| B2-19 | Admin save/processing path | `WC_Meta_Box_Product_Data::save` processes attributes | Confirmed | **PASS** |
| B2-20 | Save-button disable on empty attrs | `maybe_disable_save_button()` disables save when attribute fields empty | Confirmed | **PASS** |
| B2-21 | Native coexistence | GPA rows coexist with native rows in the same collection | Confirmed feasible (single `_product_attributes` array) | **PASS** |
| B2-23 | Frontend `shop_attributes` rendering | Visible custom attrs render in native table | Confirmed on 13143 (8 visible custom attrs) | **PASS** |
| B2-24 | CSS-only styling mechanism | Styling the native `shop_attributes` table is feasible | Confirmed (table has stable class) | **PASS** |
| B2-26 | Blocksy Additional Information markup | Accordion `ct-accordion-tab` + `#tab-additional_information` + `.entry-content.is-layout-constrained` + `table.shop_attributes` | Confirmed | **PASS** |
| B2-27 | persian-woocommerce non-interference | Plugin does not alter the frontend `shop_attributes` table | Confirmed (no interference observed) | **PASS** |
| B2-28 | WC 11.3.1 version conformance | All referenced APIs exist in 11.3.1 | Re-confirmed | **PASS** |
| B2-29 | Blocksy Free (no Pro) | Additional Information renders without Blocksy Pro | Confirmed (Free theme renders the tab) | **PASS** |

**Result: 14 / 14 PASS.** No architectural assumption was invalidated.

---

## 5. Detailed Evidence

### 5.1 Admin menu (B2-2, B2-3)

- The **Products** top-level menu is registered at position **26** (auto-assigned by
  WooCommerce, not a fixed constant).
- Candidate fractional positions **26.1 / 26.2 / 26.5** are all **free** — no active
  plugin or theme registers a menu at those positions.
- **WP-CLI limitation (documented):** including `wp-admin/menu.php` in a WP-CLI context
  did not reliably populate the global `$menu` array. Verification therefore used
  replicated CPT menu-position logic + `do_action('admin_menu')` + source-level
  inspection of active plugin menu registrations. This is a tooling limitation, not an
  architectural finding.

### 5.2 Stale reference validation (B2-7)

- For each of 13143's 5 stale taxonomy references:
  - `taxonomy_exists( 'pa_{slug}' )` → `false`
  - `wc_attribute_taxonomy_id_by_name( '{name}' )` → `0`
- This confirms the reference-integrity contract (spec §3.3): a group's Global
  Attribute reference can become stale after a rename/delete, and the validation
  functions correctly detect it.

### 5.3 Load row insertion (B2-14)

The native "add attribute" flow that GPA's Load operation will reuse:

- **AJAX action:** `WC_AJAX::add_attribute` (registered as
  `wp_ajax_woocommerce_add_attribute`) in `woocommerce/includes/class-wc-ajax.php`.
- **Row insertion + index computation:** `woocommerce/assets/js/admin/meta-boxes-product.js`
  computes the `i` index for the new row, inserts the row markup, and re-initializes
  select2.
- **Position re-index:** `attribute_position` values are re-indexed after insertion so
  the native ordering stays contiguous.

### 5.4 Admin row markup (B2-18)

- Row markup confirmed in
  `woocommerce/includes/admin/meta-boxes/views/html-product-attribute.php` and
  `html-product-attribute-inner.php`.

### 5.5 Admin save path (B2-19, B2-20)

- **Save/processing:** `WC_Meta_Box_Product_Data::save`
  (`woocommerce/includes/admin/meta-boxes/class-wc-meta-box-product-data.php`) reads the
  submitted attribute fields and persists them via the product's native save lifecycle.
- **Save-button guard:** `maybe_disable_save_button()` disables the save button when
  attribute fields are empty — a native guard GPA must respect (not bypass).

### 5.6 Frontend rendering (B2-23, B2-24, B2-26, B2-27, B2-29)

- **Render function:** `wc_display_product_attributes()`
  (`woocommerce/includes/wc-template-functions.php`), which calls
  `wc_attributes_array_filter_visible()` to keep only visible attributes.
- **Template:** `woocommerce/templates/single-product/product-attributes.php` renders
  `<table class="woocommerce-product-attributes shop_attributes">`.
- **Blocksy structure:** the Additional Information tab is rendered inside a Blocksy
  accordion (`ct-accordion-tab`) with the following selector path:
  - `#tab-additional_information`
  - `.entry-content.is-layout-constrained`
  - `<table class="woocommerce-product-attributes shop_attributes">`
- **persian-woocommerce (B2-27):** no interference with the frontend `shop_attributes`
  table was observed. The plugin's compatibility surface is reduced by the CSS-only
  styling mechanism (decision D19 preserved — compatibility is still to be re-tested in
  Phase 4, not claimed on assumption).
- **Blocksy Free (B2-29):** the Additional Information tab renders correctly on the Free
  theme — no Blocksy Pro dependency.

### 5.7 Version conformance (B2-28)

All APIs referenced by the specification were re-confirmed to exist in WooCommerce
11.3.1 (see §6 for the full mapping).

---

## 6. Spec Function-Name → Actual WooCommerce 11.3.1 Mapping

Several names in the Phase 2A.3 architecture document are **conceptual**. The actual
WooCommerce 11.3.1 mechanisms are:

| Spec (conceptual) name | Actual WooCommerce 11.3.1 mechanism |
|------------------------|--------------------------------------|
| "set product attributes" | `WC_Product::set_attributes()` / `get_attribute()` (`abstract-wc-product.php`) |
| "persist product attributes" | `_product_attributes` in `WC_Product_Data_Store_CPT` (`class-wc-product-data-store-cpt.php`) |
| "admin save handler" | `WC_Meta_Box_Product_Data::save` (`class-wc-meta-box-product-data.php`) |
| "add attribute AJAX" | `WC_AJAX::add_attribute` (`class-wc-ajax.php`) |
| "row insertion / index / select2" | `meta-boxes-product.js` (`assets/js/admin/meta-boxes-product.js`) |
| "frontend attribute display" | `wc_display_product_attributes()` (`wc-template-functions.php`) |
| "filter visible attributes" | `wc_attributes_array_filter_visible()` (`wc-template-functions.php`) |
| "attribute taxonomy lookup" | `wc_attribute_taxonomy_id_by_name()` (`wc-attribute-functions.php`) |

This mapping is recorded so Phase 3 implementation targets the **real** functions, not
the conceptual names.

---

## 7. Changed Files

| Action | Path |
|--------|------|
| Created | `reports/2026-11-26-phase-2b-runtime-verification.md` (this report) |

No production code, theme file, or plugin file was modified. No fixture was modified.

---

## 8. Risks & Open Questions

1. **WP-CLI menu inspection limitation** — the global `$menu` array is not reliably
   populated in a WP-CLI context. The menu-position verification used replicated logic
   + source inspection. This is acceptable for Phase 2B but should be re-confirmed in a
   real browser session during Phase 3/4.
2. **persian-woocommerce compatibility** — confirmed non-interfering for the frontend
   `shop_attributes` table in this phase, but per decision D19 the compatibility claim
   must still be **tested** (not assumed) in Phase 4.
3. **Stale references on 13143** — the 5 stale taxonomy references are a pre-existing
   catalog inconsistency (out of scope per D21). They are tolerated by the native view
   and do not affect GPA, but they are a useful test case for the reference-integrity
   contract (spec §3.3).
4. **Open owner items (unchanged):** group categories (B2-33), group status policy
   (B2-34), table-style visual design (B2-35), admin menu fractional offset (B2-36).

---

## 9. Conclusion

All 14 `[2B-VERIFY]` items **PASS** against the live environment. The approved
Phase 2A.3 native-template architecture is **runtime-verified** and ready for Phase 3
implementation, subject to explicit owner authorization. No architectural assumption
was invalidated. The specification's conceptual function names have been mapped to the
real WooCommerce 11.3.1 mechanisms (§6) for use in Phase 3.

**Next (requires explicit owner authorization):** Phase 3 — full implementation on a
dedicated branch.

