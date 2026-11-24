# Phase 1.1 Report — Decisions Finalization & Architecture Corrections

**Date:** 2026-11-24 · **Branch:** `phase-1-1-decisions-finalization` · **Scope:** documentation, decision-finalization, and test-environment preparation (no implementation, per task brief)

## A. Git Summary

| Item | Value |
|------|-------|
| Base commit | `67f70ad596d8187c9e4af9bcfbbf19636a574cea` (tip of `phase-1-investigation`) |
| Branch | `phase-1-1-decisions-finalization` |
| Final commit SHA | _(filled at commit time)_ |
| Remote verification | _(filled after push)_ |
| Working-tree status | clean (after commit) |
| `main` | unchanged at `2b4b0902fbaee70519df20f1bcc63723e1d591ba` (no merge performed) |

## B. Decisions Finalized

Recorded in `.clinerules/decisions.md` (D14–D23) and reflected across the docs:

| # | Decision | Recorded in |
|---|----------|-------------|
| D14 (D-NEW-01) | Dual attribute support (global taxonomy + custom) — core requirement | decisions.md, product-requirements R-L1, architecture A1 |
| D15 (D-NEW-02) | Parent product is the primary attribute source; no variation assumption | decisions.md, product-requirements R-L4, WC analysis §3 |
| D16 (D-NEW-03) | Blocksy Custom Layer (B1) selected; NOT yet runtime-verified | decisions.md, integration §3, architecture §3/§5 |
| D17 (D-NEW-04) | Native table visible by default + optional hide; never disappears when GPA empty | decisions.md, integration §3, architecture §3 |
| D18 (D-NEW-05) | View Attribute button behavior unresolved; isolated/pluggable; no prod install | decisions.md, reference-plugin §5 |
| D19 (D-NEW-06) | persian-woocommerce compatibility must be tested, not assumed | decisions.md, WC analysis §8, architecture §7 |
| D20 (D-NEW-07) | Delimiter normalization DEFERRED; respect stored values; no migration | decisions.md, WC analysis §2/§8, product-requirements R-L2, architecture §4 |
| D21 (D-NEW-08) | No premature data cleanup; tolerate inconsistencies | decisions.md, product-requirements R-L5/non-goals |
| D22 | Selected architecture direction A1 + B1 (C deferred) | decisions.md, architecture §5, README |
| D23 | Permanent test-fixture policy (13143 simple, 17028 variable) | decisions.md, project-rules §Test Environment Policy, test-fixtures.md |

## C. Rules Added

**`project-rules.md` §Test Environment Policy (PERMANENT):**
- Designated fixtures: Simple **13143**, Variable **17028** (16 real variation children).
- Backups (raw, byte-exact) in `backups/gpa-test-fixtures/` (gitignored); restoration verified on hidden copies 17601/17602.
- **Permanent testing authorization:** within the fixtures + explicitly-created GPA test fixtures, Cline may add/edit/remove attributes, create global attributes/terms, create/edit/delete GPA groups & categories, assign groups, change selected values, reorder, change product meta, and create additional test products/groups/attributes as reasonably needed. **No cleanup required after each task/phase.**
- **Strict isolation boundary:** NO modification of unrelated live products, customer orders/data, real inventory/pricing, production attributes outside fixtures, global WooCommerce settings unrelated to GPA, production theme modules, or bulk catalog operations. Shared global attributes/terms must be inspected before create/rename/delete; prefer `GPA TEST — ` prefixed test attributes/terms; stop and report if an operation could affect non-test products.
- **Naming & registry:** `GPA TEST — ` prefix; registry at `docs/testing/test-fixtures.md` (kept current).

## D. Test Fixtures

### Selected & backed up (COMPLETED)

| Product | ID | Type | Name | Backup location | Backup verification | Restoration verification |
|---------|----|------|------|-----------------|---------------------|--------------------------|
| Simple | **13143** | simple | Philips OneBlade QP2824/10 | `backups/gpa-test-fixtures/product-13143-*.json` | **PASS** — 81/81 meta keys+values, all terms, 0/0 variations (read-only cross-check vs live) | **PASS** — restored onto hidden copy **17601**; byte-exact meta + terms match |
| Variable | **17028** | variable | OnePlus 15 (16 real variation children) | `backups/gpa-test-fixtures/product-17028-*.json` | **PASS** — 75/75 meta keys+values, all terms, 16/16 variations (meta+terms) | **PASS** — restored onto hidden copy **17602**; byte-exact meta + terms + 16/16 variations match |

- **Backup method:** raw `wp_postmeta` values (exact serialized strings) + post fields + all
  term relationships (term IDs) + variation children, captured read-only via WP-CLI and
  written to gitignored JSON.
- **Restoration-verification copies (created, hidden from catalog):** **17601** (simple)
  and **17602** (variable, 16 variations). Both confirmed readable by WooCommerce
  (`get_attributes()` returns 8 and 2 attributes, matching the originals) and
  `_catalog_visibility = hidden`.
- **Additional test products / test-only attributes created:** none yet (registry sections
  3–5 are empty placeholders).

> Note: an initial restore attempt via the WP meta API double-serialized
> `_product_attributes` (a WC hook re-serializes it on meta write). The verified restore
> uses raw `$wpdb` writes for byte-exact fidelity. The two broken first-attempt copies were
> deleted; the originals were never touched.

## E. Architecture

- **Selected direction: A1 + B1** (owner decision D22).
  - **A1 — Data ownership:** CPT `ani_gpa_group` + taxonomy `ani_gpa_group_cat` + product
    meta `_ani_gpa_assigned_groups` / `_ani_gpa_selected_values` + Theme Mod settings.
  - **B1 — Frontend rendering:** Blocksy custom layer (`blocksy:woocommerce:product:custom:layer`),
    configurable position; native `shop_attributes` table visible by default with an
    optional hide setting (D17). **B1 is a selected direction, NOT runtime-verified** — the
    exact hook/registration/position is a Phase 2 verification item.
- **C — Value parsing / delimiter normalization: DEFERRED (D20).** GPA renders stored
  values as-is; no comma-based splitting, no `_product_attributes` migration, no
  auto-interpretation of commas. Recorded as a deferred issue for possible future
  investigation.
- **Not implemented.** Phase 1.1 is documentation/decision/test-preparation only.

## F. Documentation Corrections

| File | Substantive corrections |
|------|-------------------------|
| `docs/research/woocommerce-attribute-technical-analysis.md` | §5: `woocommerce_product_options_general_product_data` is the **General** tab (not the Attributes tab); added the correct Attributes-tab hook `woocommerce_product_options_attributes` + panel structure. §5: `wc_attributes_array_filter_visible` corrected from "template functions" to a **PHP callback function** (not a filter hook) used inside `wc_display_product_attributes()`. §4: refined `get_attributes`/`get_attribute`/`wc_get_text_attributes` signatures with exact source locations; clarified neither context filters by `is_visible`. §3: corrected product-type counts (293/43 → **292/42**, 334 total; 324 published) and added the **broken `tax_query` on `product_type`** finding (stale term counts). §2/§8: delimiter strategy marked **DEFERRED (D20)**. |
| `docs/research/anishop-integration-analysis.md` | §3: B1 marked **SELECTED (D16)** with proven/selected/unverified clearly separated; added D17 native-table behavior. §5: product-edit integration point corrected to the Attributes-tab hook. |
| `docs/research/architecture-options-and-recommendations.md` | §3: B1 **SELECTED**. §4: Axis C **DEFERRED (D20)**. §5: retitled "Selected architecture direction (A1 + B1; C deferred)"; data flow renders raw values. §6/§7: risks + Phase 2 checklist updated (B1 verification, D19, D18, D20 deferred, A1 schema confirmation, broken tax_query). |
| `docs/research/product-requirements-discovery.md` | R-L2: normalization **DEFERRED (D20)**. R-L4: counts corrected (292) + parent-first (D15). Added **R-L11** (broken `tax_query`). Non-goals + open questions updated (D17/D18/D20/D21). |
| `docs/research/reference-plugin-analysis.md` | §5: View Attribute button retitled (D18); do-not-invent + no-prod-install + Phase 2 investigation. |
| `reports/2026-11-24-phase-1-investigation.md` | Summary, recommendations (A1+B1, C deferred), open questions, and risks updated to reflect D14–D23; marked superseded by Phase 1.1. |
| `docs/PROJECT-ROADMAP.md` | Status line; Phase 1 → COMPLETE; new **Phase 1.1** section (COMPLETE); Phase 2 → NOT STARTED (awaiting authorization). |
| `README.md` | Status updated (Phase 0/1/1.1 complete; Phase 2 awaiting authorization) + selected A1+B1 direction. |
| `.clinerules/decisions.md` | Added D14–D23; Undecided section updated. |
| `.clinerules/memory-bank.md` | Current State, Test Fixtures, and Decisions sections updated. |
| `.clinerules/project-rules.md` | Added §Test Environment Policy (permanent). |

**Created:** `docs/testing/test-fixtures.md` (registry), `reports/2026-11-24-phase-1-1-decisions-finalization.md` (this report).
**Modified (config):** `.gitignore` (added `backups/`).

## G. Remaining Questions

Genuinely unresolved (for the owner / Phase 2):

1. **ASCII comma as a value separator** — deferred (D20); owner may decide later whether
   commas in existing values are separate values or part of a single value.
2. **"View Attribute" button behavior** — unresolved (D18); to be investigated in Phase 2.
3. **Native `shop_attributes` table visibility default** — D17 sets visible + optional hide;
   final UX confirmation pending Phase 2.
4. **B1 runtime verification** — exact custom-layer hook/registration/position to be
   confirmed against Blocksy 2.1.62 in Phase 2.
5. **`persian-woocommerce` attribute-output behavior** — `[NOT VERIFIED]`; to be tested
   (D19).
6. **`anishop_attribute_presentation` option schema** — collision check pending (Phase 2).

## H. Safety Verification

- **No unrelated production products were modified.** — Verified: only the two designated
  fixtures (13143, 17028) were read; the only writes were creating new test-fixture posts
  (17601, 17602 + 16 variations) and deleting the two broken first-attempt copies. Originals
  confirmed intact (status/title unchanged).
- **No production catalog migration occurred.** — Verified: no `_product_attributes` or any
  other production data was rewritten; no bulk operations.
- **No production module implementation occurred.** — Verified: no PHP/CSS/JS functionality
  created; no `inc/ani-group-product-attributes/` files.
- **No existing child-theme module was modified.** — Verified: all changes are in the
  workspace (`.clinerules/`, `docs/`, `reports/`, `README.md`, `.gitignore`); no files under
  `wp-content/themes/blocksy-child/` were touched.
- **No merge occurred.** — Verified: `main` unchanged at `2b4b090`; no merge performed.
- **Phase 2 was not started.** — Verified: no architecture design or implementation begun.
- **Backups not committed.** — Verified: `backups/` is gitignored; no production data in the
  commit.

**Not verified / out of scope this phase:** B1 runtime rendering, `persian-woocommerce`
compatibility, real-mobile behavior (all deferred to Phase 2/4).