# Phase 2A — Architecture Specification (GPA)

## 1. Task Title
Prepare the implementation-ready Phase 2A architecture specification for WooCommerce
Product Group Attributes (GPA) as a Blocksy child-theme module.

## 2. Date
2026-11-25

## 3. Executive Summary
Produced the complete Phase 2A architecture specification
(`docs/architecture/phase-2a-architecture-specification.md`, 859 lines, 13 sections)
defining the data contracts, CPT/taxonomy design, attribute-resolution rules, admin and
frontend integration contracts, Blocksy custom-layer integration, Theme Mod settings, and
security/performance/compatibility/testing strategy for the GPA module. The work is
**documentation only** — no production PHP/JS/CSS, no database writes, no catalog changes.
Two new live-verified findings were incorporated: (1) the native `shop_attributes` table
renders in the **Additional information tab** (not the summary), and (2) the exact
attribute-name/option formats for both attribute kinds. The `anishop_attribute_presentation`
collision check was finalized: the proposed Theme Mod key `anishop_gpa_settings` is free
(no collision). Phase 2A is complete and ready for owner review; Phase 2B (runtime
verification) and Phase 3 (implementation) remain **not authorized**.

## 4. Task Scope
- Draft the full Phase 2A architecture specification covering: system overview; group CPT
  and taxonomy design; product-to-group data contract; selected attribute value data
  contract; global vs custom attribute resolution; admin integration contracts; frontend
  rendering pipeline; Blocksy integration contract (with explicit Phase 2B verification
  items); Theme Mod settings; security, performance, compatibility, testing, and module
  boundaries.
- Label every item as: Approved decision / Technical requirement / Recommendation /
  Unresolved / Phase 2B verification.
- Finalize the `anishop_attribute_presentation` option collision check.
- Create the Phase 2A completion report (this file).
- Commit and push the documentation changes.

## 5. Authorized Work
The project owner authorized Phase 2A (architecture design, documentation only) as part of
Phase 2. This task is strictly documentation: no production code, no DB changes, no catalog
cleanup, no delimiter normalization, no production data mutation.

## 6. Files Created
- `docs/architecture/phase-2a-architecture-specification.md` — the Phase 2A architecture
  specification (the primary deliverable).
- `reports/2026-11-25-phase-2a-architecture-specification.md` — this completion report.

## 7. Files Modified
- None. (No existing files were modified.)

## 8. Files Deleted
- None. (A temporary read-only diagnostic script was created in `/tmp` and removed after
  use; it was never part of the repository.)

## 9. Detailed Changes

### `docs/architecture/phase-2a-architecture-specification.md` (new)
The specification is organized into 13 sections:

- **§0 Scope and status** — documentation-only; defers runtime verification to Phase 2B;
  keeps the "View Attribute" button unresolved (D18).
- **§1 System overview** — what GPA is (presentation layer, D9); architecture direction
  A1 + B1, C deferred (D22, D16, D20); module file structure; admin + frontend data flow.
- **§2 Group CPT and taxonomy design** — `ani_gpa_group` CPT args; `ani_gpa_group_cat`
  taxonomy args; group value storage (`_ani_gpa_group_values`); the **value reference**
  structure (the core data primitive: `attribute` / `value` / `value_id`).
- **§3 Product-to-group data contract** — `_ani_gpa_assigned_groups` (ordered group IDs),
  lifecycle (survives type changes, D15), invariants.
- **§4 Selected attribute value data contract** — `_ani_gpa_selected_values`
  (`group_id => ordered value refs`), validation, empty/stale handling.
- **§5 Global vs custom attribute resolution** — taxonomy vs custom key formats, value
  sources, display text, dual support (D14), parent-first (D15), product-type detection
  (R-L11), value source contract summary.
- **§6 Admin integration contracts** — CPT admin, taxonomy admin, product-edit integration
  (load one / load category / select values / sort), settings tab, REST-first strategy,
  save handlers (nonce + capability + autosave/revision guards).
- **§7 Frontend rendering pipeline** — render trigger/gating, resolution and rendering
  (batched term lookups), native table (D17), empty/error states, no frontend JS, markup
  and styling contract (RTL-first, house style).
- **§8 Blocksy integration contract** — custom layer (B1); **corrected native-table
  location finding** (Additional information tab); non-destructive native-table hide
  mechanism; location setting; "View Attribute" button isolation (D18); Phase 2B
  verification items.
- **§9 Theme Mod settings** — key `anishop_gpa_settings` (collision-checked, live-verified
  free); versioned schema; single sanitizer; save.
- **§10 Security, performance, compatibility, testing, module boundaries** — nonces/caps/
  escaping/sanitization; no N+1, cached custom-value aggregation; coexistence with 14
  modules + `persian-woocommerce` (D19); fixture-based testing (D23); module boundaries
  and rollback.
- **§11 Phase 2B verification checklist** — consolidated B1-1…B1-8 items.
- **§12 Unresolved items and owner decisions needed** — U1 (View Attribute, UNRESOLVED) +
  U2–U10 (RECOMMEND items to ratify).
- **§13 Evidence appendix** — live-verified attribute formats (fixtures 13143 + 17028),
  static code evidence (render chain, `read_attributes()`, `persian-woocommerce`), and
  carried-over Phase 1/1.1 facts.

Every design item is labeled **[DECISION D#]** / **[REQ]** / **[RECOMMEND]** /
**[UNRESOLVED]** / **[2B-VERIFY]**, and evidence carries provenance labels
([LIVE-VERIFIED 2026-11-25], [STATIC CODE EVIDENCE], [WC 11.3.1 SOURCE], etc.).

## 10. Tests and Verification

**Static / documentation verification:**
- Confirmed the document structure (13 sections, 859 lines) via `grep` on headings.
- Confirmed label usage: REQ (94), RECOMMEND (45), UNRESOLVED (3), 2B-VERIFY (10),
  DECISION (26) — all five label types present and used consistently.

**Live read-only verification (WP-CLI `eval-file`, no writes):**
- **Attribute name/option formats** on fixtures 13143 (simple, custom attributes) and
  17028 (variable, taxonomy attributes) — confirmed custom `name` is raw Persian (not
  URL-encoded), custom `options` is the raw string (commas not split), taxonomy
  `get_attributes()` options are term IDs, and `get_variation_attributes()` options are
  term slugs. (Basis for the value reference structure, §2.4.)
- **Collision check** — confirmed `anishop_gpa_settings` Theme Mod is NOT set (free to
  use); `anishop_attribute_presentation` is an option set to `{"pa_color":"color"}`
  (a different concern); listed all existing `anishop_*` Theme Mods. (Basis for §9.1.)

**Source verification (static code reading):**
- Native `shop_attributes` render chain in Blocksy 2.1.62 + WC 11.3.1 (tabs.php →
  `woocommerce_product_additional_information()` → `wc_display_product_attributes()` →
  `product-attributes.php` with the `if ( ! $product_attributes ) { return; }` guard and
  the `woocommerce_display_product_attributes` filter).
- `read_attributes()` in `class-wc-product-data-store-cpt.php:613` (taxonomy → term IDs;
  custom → `wc_get_text_attributes()`).
- `persian-woocommerce` source tree — no hooks on the attribute display path.

**Git verification:**
- Confirmed branch `phase-2a-architecture-specification`, base `05ec502`, clean tree
  (only `docs/architecture/` untracked before commit).

## 11. Verified Results
- **Value reference structure is grounded in live data** — [LIVE-VERIFIED 2026-11-25]
  fixtures 13143 + 17028 confirm the exact `attribute`/`value`/`value_id` formats for both
  attribute kinds.
- **Native table location corrected** — [LIVE-VERIFIED 2026-11-25] + [STATIC CODE
  EVIDENCE] the `shop_attributes` table renders in the Additional information tab, not the
  summary.
- **Non-destructive native-table hide mechanism identified** — [WC 11.3.1 SOURCE] the
  `woocommerce_display_product_attributes` filter returning `array()` suppresses the table
  via the template guard; `_product_attributes` is untouched.
- **`anishop_gpa_settings` Theme Mod key is collision-free** — [LIVE-VERIFIED 2026-11-25]
  the key is not set; `anishop_attribute_presentation` is a distinct option.
- **`persian-woocommerce` does not hook the attribute display path** — [STATIC CODE
  EVIDENCE] (runtime compatibility still deferred to Phase 2B per D19).
- **Document is complete and consistently labeled** — [STATIC] 13 sections, all five
  label types present.

## 12. Not Verified / Limitations
- **Blocksy custom-layer runtime behavior (B1)** — the exact hook signature, registration
  mechanism, and render position are **not** runtime-verified; deferred to Phase 2B
  (B1-1, B1-2, B1-3). B1 remains a selected direction, not a verified fact (D16).
- **`persian-woocommerce` runtime compatibility** — static evidence only; runtime testing
  deferred to Phase 2B (B1-5, D19).
- **Product-edit hook firing** — `woocommerce_product_options_attributes` /
  `woocommerce_process_product_meta` are the standard WC hooks but their live firing is
  not runtime-verified (B1-6).
- **Custom-value aggregation query** — the proposed single raw-SQL + transient strategy is
  a recommendation, not yet executed/verified (B1-7).
- **"View Attribute" button behavior** — unresolved (D18); not assumed; deferred to
  Phase 2B investigation (B1-8).
- **No frontend rendering was performed** — this is a documentation task; no GPA output
  was rendered (nothing to render yet).

## 13. Risks and Open Questions
- **B1 runtime risk:** if the Blocksy custom-layer hook/registration differs from the
  static reading, the frontend integration contract (§8.1) may need adjustment. Mitigated
  by the explicit Phase 2B verification checklist (§11).
- **Custom-value aggregation cost:** aggregating distinct custom values from the catalog
  could be expensive on a large catalog; the single-query + transient strategy is a
  recommendation to be confirmed (U6, B1-7).
- **Open owner decisions (U2–U10):** several [RECOMMEND] design choices (CPT menu
  placement, order mechanism, product-edit placement, toggle persistence, aggregation
  strategy, style/location option sets, Theme Mod key, empty-group rendering) need owner
  ratification before/during Phase 3. None block Phase 2B.
- **U1 (View Attribute)** remains a true [UNRESOLVED] and must stay architecturally
  isolated (D18).

## 14. Scope Compliance
Only authorized documentation work was performed. No production PHP/JS/CSS was created or
modified. No database writes were made (all WP-CLI usage was read-only `eval-file`). No
catalog, product, attribute, or setting was changed. No delimiter normalization or catalog
cleanup was performed (D20, D21). The temporary diagnostic script was created in `/tmp`
(outside the repository and production) and removed after use. No unrelated files were
touched.

## 15. Git Information
- **Branch:** `phase-2a-architecture-specification`
- **Base branch:** `phase-1-1-1-tax-query-verification` (per task context; `main` is the
  ultimate integration target per project rules)
- **Base commit:** `05ec502`
- **Work commit SHA:** `12f58ccd599cba5769ab05b12962c9022442f854`
  (`docs: Phase 2A architecture specification + completion report`) — the commit
  containing all substantive changes.
- **Remote push status:** pushed to `origin/phase-2a-architecture-specification` and
  verified — `git ls-remote origin phase-2a-architecture-specification` →
  `12f58ccd599cba5769ab05b12962c9022442f854` (matches the work commit).
- **Working-tree status:** clean (verified after commit).
- **`main`:** unchanged (no merge performed).

> A follow-up metadata commit records this Git state. The work commit SHA above is the
> commit containing all substantive changes; the metadata commit is documentation-only.

## 16. Phase / Task Status
Phase 2A (architecture specification) **complete** — documentation delivered and ready for
owner review. Phase 2B (runtime verification) **NOT authorized / not started**. Phase 3
(implementation) **NOT authorized / not started**. No implementation code exists.

## 17. Recommended Next Step
Owner reviews the Phase 2A architecture specification
(`docs/architecture/phase-2a-architecture-specification.md`), ratifies the [RECOMMEND]
items (U2–U10), and explicitly authorizes **Phase 2B** (runtime verification of the Blocksy
custom-layer integration + `persian-woocommerce` compatibility, per the §11 checklist).

## 18. Stop Confirmation
Work has stopped after completing the Phase 2A specification and this report, committing
and pushing the documentation, and verifying the final Git state. Awaiting the project
owner's next instruction. No further phase or task has been started.
