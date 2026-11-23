# Task Report — Pre-Phase 1 Documentation Corrections

**Date:** 2026-11-23
**Task:** Pre-Phase 1 documentation corrections (documentation maintenance — **NOT** Phase 1)
**Branch:** `chore/docs-audit-fixes` (created from `main` @ `3abd4db`)

## A. Summary

This task corrected four documentation inconsistencies identified during the repository review:

- **A** — WooCommerce API accuracy in `docs/woocommerce-attributes-dev-notes.md` (`get_attributes()` context, `get_attribute()` signature, related API references).
- **B** — Custom attribute value delimiter (pipe `|` convention + `wc_get_text_attributes()`; stored vs parsed representation).
- **C** — Removed an unrelated Blocksy Header Builder requirement from `docs/PROJECT-ROADMAP.md` (leftover from the previous Search Box project).
- **D** — Made the Phase 1 approval status consistent across all project documents: Phase 0 complete; Phase 1 prepared and awaiting explicit owner authorization; investigation not started.

All API claims were verified against the **installed** WooCommerce 11.3.1 source (read-only) plus one read-only meta read of product 9243. No implementation work was performed; Phase 1 was not started; the `phase-1-investigation` branch was not created.

## B. Files Changed

### Modified

| File | Reason |
|------|--------|
| `docs/woocommerce-attributes-dev-notes.md` | Corrections A + B: verified API signatures, pipe-delimiter storage convention, stored-vs-parsed distinction, live-data caveat |
| `docs/PROJECT-ROADMAP.md` | Correction C (Header Builder sentence replaced) + D (project status line + Phase 1 status) |
| `README.md` | Correction D (Status section) |
| `reports/2026-11-23-repo-setup.md` | Correction D ("Next" section now states Phase 1 awaits owner authorization) |
| `.clinerules/memory-bank.md` | Correction D (Current State) |
| `.clinerules/project-context.md` | Correction D (Phase table, Phase 1 row) |

### Created

| File | Reason |
|------|--------|
| `reports/2026-11-23-pre-phase-1-doc-corrections.md` | This report |

## C. Correction Details

### A — WooCommerce API accuracy

Verified against the installed WooCommerce **11.3.1** source (`wp-content/plugins/woocommerce/`, read-only):

| Old doc claim | Verified reality (WC 11.3.1) | Evidence (file:line) |
|---|---|---|
| `get_attributes( 'edit' \| 'visible' )` | `get_attributes( $context = 'view' )` — valid contexts are **`view`** (default) and **`edit`**; `visible` is not valid. `view` applies the `woocommerce_product_attributes` filter; `edit` returns the raw prop. Neither context filters by visibility — the caller does (e.g. `wc_attributes_array_filter_visible` in the frontend template functions) | `includes/abstracts/abstract-wc-product.php:590`, `includes/abstracts/abstract-wc-data.php:953`, `includes/wc-template-functions.php:4282` |
| `get_attribute( $name, $echo, $append_name )` | `get_attribute( $attribute )` — **single parameter**, returns a string (taxonomy: term names joined with `, `; text: options joined with ` | `); `''` when not found. The older 3-parameter form no longer exists in WC 11.3.1 | `includes/abstracts/abstract-wc-product.php:2366` |
| `wc_get_product_terms( $product, $taxonomy, $args )` | `wc_get_product_terms( $product_id, $taxonomy, $args = array() )` — product **ID**, not object | `includes/wc-term-functions.php:226` |
| `wc_attribute_label( $attribute )` | `wc_attribute_label( $name, $product = '' )` — name string | `includes/wc-attribute-functions.php:213` |
| Data store `save_attributes()` | `WC_Product_Data_Store_CPT::update_attributes()` (write) / `read_attributes()` (read) in WC 11.3.1 | `includes/data-stores/class-wc-product-data-store-cpt.php:1100 / 613` |

### B — Custom attribute value delimiter

- Old docs claimed custom attribute values are "comma-separated". **Incorrect.**
- Verified standard convention (WC 11.3.1 source):
  - **Stored representation:** one string in the `value` field; multiple values joined with space-pipe-space (`value1 | value2 | value3`); `WC_DELIMITER` = `|` (`includes/class-woocommerce.php:570`); written by `update_attributes()` via `wc_implode_text_attributes()` (`implode( ' ' . WC_DELIMITER . ' ', … )`, `includes/wc-attribute-functions.php:46`).
  - **Parsed values:** `read_attributes()` passes the stored string to `wc_get_text_attributes( $raw_string )` (`includes/wc-attribute-functions.php:21`) — splits on `|`, trims each part, drops empty values; exposed as `WC_Product_Attribute::get_options()`.
- **Live data check (read-only `wp post meta get 9243 _product_attributes`):** the observed custom attributes are **single-value free-text strings** (`5 وات`, `130 گرم`, …) — no delimiter present. The docs now state this and flag a full delimiter audit across all products as a Phase 1 item (no assumption that every custom attribute follows the standard multi-value format).

### C — Header Builder requirement removal

- Removed from `docs/PROJECT-ROADMAP.md` §1: "The implementation should integrate with the existing Blocksy Header Builder and preferably use its HTML element with a custom shortcode." (leftover from the previous Search Box project)
- Replaced with: "The module integrates with WooCommerce product administration (product data panel / product edit screen) and frontend product presentation (single product page and shop page), rendered inside the Blocksy child theme."
- A workspace-wide grep confirmed this was the **only** Header Builder / shortcode mention outside `docs/references/`. No other Search Box wording found in the surrounding paragraph.

### D — Phase 1 approval status consistency

Correct status (per owner instruction for this task): **Phase 0 complete · Phase 1 prepared, awaiting explicit owner authorization · investigation not started · Phase 2+ not started.**

Updated:

| File | Before | After |
|------|--------|-------|
| `README.md` | "Phase 1 (investigation) — approved 2026-11-23" | "Phase 0: complete. Phase 1 (investigation): prepared, awaiting explicit project-owner authorization to begin (not started)." |
| `docs/PROJECT-ROADMAP.md` (header) | "Phase 1 (investigation) — in progress (approved 2026-11-23)" | "Phase 0 complete — Phase 1 (investigation) prepared, awaiting explicit owner authorization (not started)" |
| `docs/PROJECT-ROADMAP.md` (Phase 1) | "IN PROGRESS (approved 2026-11-23)" | "PREPARED — awaiting explicit owner authorization (not started)" |
| `reports/2026-11-23-repo-setup.md` | "Phase 1 investigation begins on branch …" | "… will begin … **after explicit project-owner authorization**" |
| `.clinerules/memory-bank.md` | "Phase: 1 (investigation) — approved 2026-11-23" | "Phase: 0 complete; Phase 1 (investigation) prepared — awaiting explicit owner authorization, not started" |
| `.clinerules/project-context.md` | "IN PROGRESS (approved 2026-11-23)" | "PREPARED — awaiting owner authorization (not started)" |

A workspace-wide grep for `approved 2026-11-23` / `IN PROGRESS` / `in progress` confirmed no remaining status claims (one out-of-scope exception — see G).

## D. Verification

- [x] All four corrections applied (each modified file re-read after editing).
- [x] API claims verified against installed WC 11.3.1 source (read-only greps/sed; file:line evidence in §C).
- [x] Live delimiter check: read-only `wp post meta get 9243 _product_attributes` — single-value strings, no delimiter.
- [x] Grep sweep for stale status claims and Header Builder/shortcode mentions — clean (one out-of-scope exception, see G).
- [x] `git diff --stat` review before commit: only the 6 documentation files modified; no executable files, no snapshot files, no WordPress files touched.
- [x] No database writes, no plugin/theme changes, no WordPress settings changes.

## E. Git Status

- **Branch:** `chore/docs-audit-fixes` (created from `main` @ `3abd4db`)
- **Commits:**
  - `2dc9fa3` — `docs: correct WooCommerce references and project status` (the six corrected files)
  - (this report — second commit on the branch)
- **Push:** `chore/docs-audit-fixes` pushed to origin (verified via `git ls-remote`)
- **`main`:** not modified, not merged (still at `3abd4db`, in sync with `origin/main`)
- **`phase-1-investigation` branch:** NOT created (per task instruction)

## F. Scope Confirmation

| Area | Modified? |
|------|-----------|
| Production code (PHP/JS/CSS) | **No** |
| Child-theme snapshot (`snapshots/blocksy-child/`) | **No** |
| WordPress live environment | **No** (read-only source/meta inspection only) |
| Database | **No** (one read-only meta read; zero writes) |
| Existing module implementations | **No** |
| `.clinerules/decisions.md` | **No** (out of scope per task; see G) |
| Reference PDF / screenshots | **No** |

## G. Remaining Issues

1. **`.clinerules/decisions.md` D6** still reads "Phase 1 … approved to begin after the documentation fixes" — now inconsistent with the corrected status. The task explicitly forbids changing `decisions.md`, so it was left untouched. **Owner decision needed:** amend D6 (e.g. "Phase 1 prepared; start requires explicit owner authorization") or record a new decision.
2. **Full delimiter audit:** only product 9243 (mixed) and 13551 (taxonomy-only) were observed. Whether any Anishop product stores multi-value custom attributes — and in which format — is unverified; flagged as a Phase 1 item in the dev notes.
3. **`get_attributes()` context semantics:** verified that `view` applies the `woocommerce_product_attributes` filter and `edit` does not; the practical implication for GPA's own code (which context to use where) is a Phase 1/2 consideration.

## H. Phase Status

- **Phase 0:** Complete.
- **Phase 1:** Awaiting explicit project-owner authorization. Prepared (workspace, docs, branch workflow ready).
- **Phase 1 investigation:** Not started.
- **Phase 2 and later:** Not started.
- **No implementation has been performed.**

