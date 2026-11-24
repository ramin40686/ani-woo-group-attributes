# Phase 1 Investigation Report — WooCommerce Group Attributes (GPA)

**Date:** 2026-11-24 · **Branch:** `phase-1-investigation` · **Scope:** read-only investigation + documentation (no implementation, per ROADMAP §3)

## 1. Summary

Phase 1 investigated the live Anishop environment, the native WooCommerce 11.3.1 attribute
architecture, the reference plugin's behavior, and the child-theme integration surface.
The investigation is **complete**. Five research documents were produced under
`docs/research/`, and an evidence-based architecture recommendation (A1 + B1 + C1) is
ready for Phase 2 design and owner approval.

**Headline findings:**

1. **Delimiter anomaly (critical):** Anishop's custom attributes never use the
   WC-standard pipe. 259 use the Persian comma `،`, 41 use the ASCII comma `,`. All 294
   multi-value custom attributes currently render as single unsplit values on the
   frontend (live proof: product 4328). GPA must normalize `|`, `،`, `,` at read time.
2. **Product type is a taxonomy in WC 11.3.1:** `_product_type` meta does not exist on
   this site (0 rows); type comes from the `product_type` taxonomy (293 variable, 43
   simple). GPA must use the API/taxonomy, never the meta.
3. **"Variable-typed without variations" is the norm:** 293 variable-typed products, but
   only 3 have actual variation children (33 variation posts). GPA must not assume
   variations exist.
4. **Presentation-layer integration is the proven house pattern:** the existing
   inline-variations and buy-box modules render via Blocksy custom layers without
   touching native data — exactly the contract D9 requires.

## 2. Deliverables (changed files)

**Created (this task):**

| File | Purpose |
|------|---------|
| `docs/research/woocommerce-attribute-technical-analysis.md` | Native WC attribute storage, APIs, hooks, delimiter anomaly, product-type taxonomy, live census |
| `docs/research/anishop-integration-analysis.md` | Child-theme integration surface, coexistence constraints, rendering options R1–R3, admin plan |
| `docs/research/reference-plugin-analysis.md` | Reference plugin feature map, Anishop mapping, "View Attribute" gap (D10) |
| `docs/research/product-requirements-discovery.md` | Live-environment requirements R-L1…R-L10, non-goals, open product questions |
| `docs/research/architecture-options-and-recommendations.md` | Options A/B/C per axis, recommendation A1+B1+C1, risks, Phase 2 checklist |
| `reports/2026-11-24-phase-1-investigation.md` | This report |

**Deleted (after evidence capture):** `temp/gpa-phase1-attr-audit.php` …
`temp/gpa-phase1-attr-audit7.php` (7 read-only audit scripts).

**Not changed:** no production files, no child-theme files, no database writes.

## 3. Evidence base

| Evidence | Provenance |
|----------|-----------|
| Attribute census (334 products, 326 with attributes, 1521 custom / 4125 taxonomy entries, 408 `is_variation=1`, 5555 `is_visible=1`, 399 custom names, 507 `pa_*` keys) | `[LIVE-VERIFIED 2026-11-24]` read-only WP-CLI audits |
| Delimiter counts (0 pipe / 259 Persian comma / 41 ASCII comma / 294 multi-value) | `[LIVE-VERIFIED 2026-11-24]` corrected audit (literal U+060C detection) |
| Product type (293 variable / 43 simple; 0 `_product_type` rows; 33 variations under 3 parents; cross-tab 287/6/12/0) | `[LIVE-VERIFIED 2026-11-24]` read-only audits |
| Frontend unsplit rendering (product 4328, `نوع کاربری` → `رسمی، روزمره، ورزشی`) | `[CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE VERIFIED]` curl-fetched HTML |
| WC 11.3.1 source (data store, `wc_get_text_attributes`, `WC_DELIMITER`, `get_product_type`) | `[WC 11.3.1 SOURCE]` read-only source reading |
| Child-theme structure (14 modules, hooks, Theme Mods, MD5 locks) | `[LIVE-VERIFIED 2026-11-23]` |
| Reference plugin behavior | `[REFERENCE DOC]` PDF + 11 screenshots |

Consolidated raw audit output was captured at `/tmp/gpa-consolidated-audit.txt`
(transient; key numbers transcribed into the research docs above).

## 4. Verified / Not verified

**Verified (this phase):**
- Live attribute census and delimiter distribution (read-only queries).
- Product-type storage mechanism (source + live cross-check, three independent methods).
- Variation reconciliation (293/43/3/33 + cross-tab).
- Native frontend rendering of the unsplit value (product 4328).
- Child-theme module inventory and integration hooks.
- Reference plugin feature map (from official docs).

**Not verified (flagged for Phase 2):**
- `persian-woocommerce` 10.0.7's effect on attribute output (`[NOT VERIFIED]`).
- Exact "View Attribute" button behavior (unspecified in reference docs — D10).
- The pre-existing `anishop_attribute_presentation` option schema (collision check).
- Custom-layer render position for GPA tables (needs a live render test).
- Real-mobile rendering (no mobile-specific behavior was in scope for Phase 1).

## 5. Recommendations (for Phase 2 approval)

- **A1 — Data ownership:** CPT `ani_gpa_group` + taxonomy `ani_gpa_group_cat` + product
  meta `_ani_gpa_assigned_groups` / `_ani_gpa_selected_values` + Theme Mod settings
  (the already-decided data model).
- **B1 — Rendering:** Blocksy custom layer (`blocksy:woocommerce:product:custom:layer`),
  configurable position; native table untouched.
- **C1 — Value parsing:** read-only display normalization splitting on `|`, `،`, `,`
  with a per-attribute single-value override; **no data migration**.

Full rationale, rejected alternatives, and risks:
`docs/research/architecture-options-and-recommendations.md`.

## 6. Open questions requiring owner input

1. **ASCII comma as separator** — recommend supporting it (41 live attributes) with the
   per-attribute override for spec-list strings. Owner confirmation requested.
2. **"View Attribute" button behavior (D10)** — options: (a) temporarily install the
   reference plugin in a sandbox to observe behavior (requires owner approval per Safety
   rules), (b) infer from WooCommerce.com docs, (c) owner specifies. The architecture
   keeps the behavior pluggable regardless.
3. **Native table visibility** — when GPA tables render, should the native
   `shop_attributes` table be hidden (setting) or left visible?

## 7. Risks & open items

- Over-splitting spec-list values → mitigated by C1 override.
- Admin memory on 326-product catalog → batched/raw queries + pagination (a
  `wc_get_product()` loop over all products caused a memory fatal during auditing).
- Stale `product_type` term counts and 12 simple-typed products with variation flags →
  pre-existing data quality; GPA tolerates, does not fix.
- `persian-woocommerce` interaction → verify in Phase 2 before finalizing render hooks.

## 8. Next step (requires explicit owner authorization)

Phase 2 — architecture design and approval, on branch `phase-2-architecture`, using the
Phase 2 checklist in `docs/research/architecture-options-and-recommendations.md` §7.
**Not started** — awaiting owner authorization per project rules (D13).

