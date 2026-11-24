# Phase 1.1.1 — `product_type` `tax_query` Zero-Result Verification & Product-Count Reconciliation

**Date:** 2026-11-24
**Branch:** `phase-1-1-1-tax-query-verification` (created from `7359c20`)
**Type:** Read-only verification + documentation correction
**Status:** COMPLETE

## 1. Scope & Read-Only Safety Statement

This task (Phase 1.1.1) verifies the Phase 1.1 claim that "`tax_query` on `product_type` is
broken on this site (stale term counts)" and reconciles the product-count documentation
inconsistency (292/42/334 vs 293/43/336).

**Read-only safety:** All verification used read-only WP-CLI commands (`wp eval-file` with
read-only PHP scripts) and read-only source-code inspection. **No database writes, no
taxonomy repairs, no product edits, no plugin changes, no production-code changes, and no
Phase 2 implementation were performed.** The two temp scripts
(`temp/gpa-1111-count-check.php`, `temp/gpa-1111-taxquery-test.php`) execute only `SELECT`
queries and read-only WP/WC API calls; they are gitignored (`temp/`) and are **not**
committed. Their key outputs are preserved in §4.

## 2. Environment Inspected

| Item | Value | Verified |
|------|-------|----------|
| WordPress | 7.1.5 | LIVE-VERIFIED 2026-11-24 (`wp core version`) |
| WooCommerce | 11.3.1 | LIVE-VERIFIED 2026-11-24 (`wp plugin list`) |
| persian-woocommerce | 10.0.7 | LIVE-VERIFIED 2026-11-24 |
| PHP | 8.3.36 | LIVE-VERIFIED 2026-11-15 |

Source files inspected (read-only):
- `wp-includes/class-wp-tax-query.php` (WP 7.1.5)
- `wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-product.php` (WC 11.3.1)
- `wp-content/plugins/woocommerce/includes/data-stores/class-wc-product-data-store-cpt.php` (WC 11.3.1)

## 3. Source-Code Execution-Path Analysis

### 3.1 The failing path: `tax_query` without `field` (defaults to `term_id`)

For a clause `['taxonomy' => 'product_type', 'terms' => 'variable']` (no `field`):

1. `WP_Tax_Query::parse_query()` merges clause defaults; `'field' => 'term_id'` is the
   default (`class-wp-tax-query.php:142`).
2. `get_sql_for_clause()` calls `clean_query( $clause )` (line 395).
3. `clean_query()` (line 546): because `field` is `term_id` (not `slug`/`name`), line 563
   runs `$query['terms'] = wp_parse_id_list( $query['terms'] )`. `wp_parse_id_list('variable')`
   casts the string to int → `[0]`.
4. `clean_query()` then calls `transform_query( $query, 'term_taxonomy_id' )` (line 581).
5. `transform_query()` (line 596): line 608 `$terms = array_filter( $query['terms'] )` →
   `array_filter([0])` → `[]` (0 is falsy). Lines 609–612: because the filtered list is
   empty, it sets `$query['terms'] = array()` and returns early ("Empty 'terms' always
   results in a null transformation").
6. Back in `get_sql_for_clause()`: line 404 `'IN' === $operator` (default); lines 406–408
   `if ( empty( $terms ) ) { return self::$no_results; }`.
7. `self::$no_results` is `'where' => array( '0 = 1' )` (lines 49–52) — an impossible SQL clause.

**Result: the query's WHERE contains `0 = 1` → 0 rows.** This is exactly the observed
"zero-result" behavior.

### 3.2 The correct path: `tax_query` with `field => 'slug'`

For a clause `['taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'variable']`:

1. `clean_query()`: because `field` is `slug`, line 561 runs
   `$query['terms'] = array_unique( (array) $query['terms'] )` → `['variable']` (no int cast).
2. `transform_query( $query, 'term_taxonomy_id' )`: line 608 `array_filter(['variable'])` →
   `['variable']` (not empty). Lines 615–627 build `$args` with `'get' => 'all'` and
   `$args['slug'] = ['variable']`.
3. Lines 643–644: `new WP_Term_Query()` → `query( $args )`. Because `get => 'all'`,
   `hide_empty` is **not** applied on this path (the term is resolved regardless of its count).
4. Line 656: `$query['terms'] = wp_list_pluck( $term_list, 'term_taxonomy_id' )` → `[4]`
   (the `variable` term's `term_taxonomy_id`).
5. `get_sql_for_clause()`: `IN (4)` → matches every product carrying the `variable` term.

**Result: all 293 variable products (all statuses) / 290 (published).**

### 3.3 WooCommerce corroboration

- `WC_Product_Data_Store_CPT::get_wp_query_args()`
  (`class-wc-product-data-store-cpt.php:2375–2379`): WooCommerce's own
  `wc_get_products( [ 'type' => 'variable' ] )` builds the `tax_query` with
  **`'field' => 'slug'`** — confirming `field => 'slug'` is the intended, correct way to
  filter products by type.
- `WC_Product::get_type()` (`abstract-wc-product.php:163`): returns the cached
  `$this->product_type` property (defaults to `simple`).
- `WC_Product_Data_Store_CPT::get_product_type( $product_id )` (line 2249): cached; for
  non-variation products it uses `get_the_terms( $product_id, 'product_type' )` +
  `sanitize_title( current( $terms )->name )`, defaulting to `simple`.

## 4. Live-Test Methodology & Results

### 4.1 Methodology

Two read-only PHP scripts were run via `wp eval-file` against the live site (no writes):
- `temp/gpa-1111-taxquery-test.php` — 13 tests covering `get_terms`, `get_the_terms`,
  `WP_Query` `tax_query` (with/without `field`), direct SQL, `WP_Term_Query`,
  `wp_parse_id_list`, and the WC product API.
- `temp/gpa-1111-count-check.php` — 5 cross-checks covering direct SQL counts
  (all-status + published), non-product objects, a `get_the_terms` loop, and the product
  status breakdown.

Both scripts execute only `SELECT` queries and read-only API calls. They are gitignored and
not committed; their outputs are preserved below.

### 4.2 Results

**Product-type counts (all cross-checks agree):**

| Method | variable | simple | total |
|--------|----------|--------|-------|
| Direct SQL, all statuses (CROSS-CHECK 1) | 293 | 43 | 336 |
| Direct SQL, published only (CROSS-CHECK 2) | 290 | 36 | 326 |
| `get_the_terms` loop over all 336 IDs (CROSS-CHECK 4) | 293 | 43 | 336 |
| `WP_Query` `tax_query` `field=slug`, all statuses (TEST 4/5) | 293 | 43 | — |
| `get_terms( hide_empty=true )` term `count` (TEST 1) | 290 | 36 | — |
| `WP_Query` baseline, all statuses (TEST 10) | — | — | 336 |
| `WP_Query` baseline, published (TEST 11) | — | — | 326 |

**Status breakdown (CROSS-CHECK 5):** publish=326, draft=8, private=2. (326 + 8 + 2 = 336.)

**The zero-result reproduction & fix:**

| Test | Clause | found_posts |
|------|--------|-------------|
| TEST 8 | `terms='variable'`, no `field` (defaults `term_id`) | **0** |
| TEST 9 | `terms='simple'`, no `field` (defaults `term_id`) | **0** |
| TEST 4 | `terms='variable'`, `field='slug'` | **293** |
| TEST 5 | `terms='simple'`, `field='slug'` | **43** |

**Term-count accuracy (TEST 1/2/6):**
- `get_terms( hide_empty=true )` returns 2 terms: `simple` (count=36, term_id=2),
  `variable` (count=290, term_id=4).
- `get_terms( hide_empty=false )` returns 4 terms: `external` (0), `grouped` (0),
  `simple` (36), `variable` (290).
- The stored term `count` (36/290) **exactly matches** the published product counts
  (36/290) — the term counts are **not stale**.

**Mechanism confirmation (TEST 12/13):**
- `WP_Term_Query( get=all, slug=variable )` returns 1 term (count=290) — confirming
  `hide_empty` is not applied on the `transform_query` path.
- `wp_parse_id_list( ['variable'] )` → `[0]` — confirming the slug is coerced to `0` under
  the default `term_id` field.

**WC API detection (TEST 3/7):**
- `get_the_terms( 17028, 'product_type' )` → `variable`;
  `get_the_terms( 13143, 'product_type' )` → `simple`.
- `wc_get_product(17028)->get_type()` → `variable`;
  `wc_get_product(13143)->get_type()` → `simple`.

**Minor observation (out of scope, D21):** TEST 6 (raw `COUNT(tr.object_id)`) reports
44 simple / 294 variable relationship rows, one more than the 43/293 distinct existing
products (CROSS-CHECK 1). CROSS-CHECK 3 found 0 non-product posts carrying `product_type`
terms. The most likely explanation is **2 orphaned `term_relationships` rows** (1 simple,
1 variable) pointing to deleted posts. This is a pre-existing data-quality artifact; per
D21 it is out of scope and was not modified.

## 5. Root-Cause Determination

**[VERIFIED]** The Phase 1.1 "zero-result `tax_query`" observation was caused by
**query construction, not by taxonomy data**:
- A `tax_query` clause with `terms => 'variable'` and **no `field`** defaults to
  `field => 'term_id'`.
- `wp_parse_id_list()` coerces the slug string `'variable'` to `0`.
- `WP_Tax_Query::transform_query()` then drops the falsy `0` via `array_filter()`, leaving
  an empty term list.
- `get_sql_for_clause()` returns the impossible `0 = 1` clause for an empty `IN` list →
  **0 rows**.

**[VERIFIED]** The `product_type` term counts are **not stale**: `variable` reports
`count=290` and `simple` reports `count=36`, both exactly matching the published product
counts. `get_terms( hide_empty=true )` returns both terms.

**[VERIFIED]** With `field => 'slug'`, the identical query returns all **293** variable
products (all statuses) / **290** (published).

**Confidence: HIGH.** The root cause is confirmed by (a) direct source-code reading of the
exact execution path in WP 7.1.5, (b) live reproduction of the 0-row result with the
default field, (c) live confirmation of the correct result with `field => 'slug'`, and
(d) WooCommerce's own data store using `field => 'slug'` for the same purpose.

## 6. The Product-Count Inconsistency (292/42/334 vs 293/43/336)

**[VERIFIED]** The Phase 1.1 "corrected" counts of **292 variable / 42 simple / 334 total**
(289/35/324 published) were themselves in error: they had **silently excluded the two
designated GPA test fixtures** — product **17028** (variable) and product **13143**
(simple).

- 293 − 1 (17028) = **292** variable
- 43 − 1 (13143) = **42** simple
- 336 − 2 = **334** total
- (Published: 290 − 1 = 289; 36 − 1 = 35; 326 − 2 = 324.)

Both fixtures are published and carry `_product_attributes` (confirmed in Phase 1.1). The
**original Phase 1 figures (293/43/336) were correct.**

**[VERIFIED]** The verified counts are:
- **All-status:** 336 total, 293 variable, 43 simple.
- **Published:** 326 total, 290 variable, 36 simple.

> **Note on the task premise:** the task asked for correction to "292 variable." Live
> verification shows **293 variable** for the all-status population. **292** was a
> fixture-excluded count, not the full live count. The documentation has been corrected to
> the verified **293/43/336** (all-status) and **290/36/326** (published).

## 7. Corrected GPA Product-Type Detection Recommendation

**[RECOMMENDATION]** For GPA product-type detection:
1. **Per-product detection (preferred):** use `$product->get_type()` or
   `$product->is_type( 'variable' )`. This is the WooCommerce API, is cached, and is
   unambiguous.
2. **`get_the_terms( $product_id, 'product_type' )`** is also acceptable for per-product
   detection.
3. **Batch filtering via `WP_Query`/`tax_query`:** if used, **always** specify
   `field => 'slug'` (or `field => 'name'`). Never rely on the default `field` (`term_id`)
   with a slug string.
4. **Do not** rely on `term->count` for product-type detection, and do **not** assume
   `tax_query` is broken — it works correctly with the proper `field`.

## 8. Documentation Changes

The following documents were corrected to reflect the verified findings (all changes are
documentation-only; no code):

| File | Change |
|------|--------|
| `docs/research/woocommerce-attribute-technical-analysis.md` | Restored verified all-status counts (336/293/43) and published counts (326/290/36); products-with-attributes → 328; removed the incorrect "stale term counts" and "tax_query is broken" claims; replaced with the verified `field`/`wp_parse_id_list()` root cause; corrected the performance figure (326→328) and the open-questions list. |
| `docs/research/product-requirements-discovery.md` | R-L4 counts (292→293, 289→290); R-L5 removed the "stale term counts" anomaly (disproven); R-L10 performance figure (326→328); R-L11 retitled and rewritten (tax_query requires `field => 'slug'`, not "broken"); Non-goals updated. |
| `docs/research/architecture-options-and-recommendations.md` | Admin-memory figure (334→336); risk row rewritten (tax_query without `field` → use `field => 'slug'`). |
| `docs/research/reference-plugin-analysis.md` | Products-with-attributes figure (326→328). |
| `docs/testing/test-fixtures.md` | Published-variable figure (289→290); "broken tax_query" limitation rewritten (works with `field => 'slug'`). |
| `reports/2026-11-24-phase-1-investigation.md` | Restored 293/43 (re-verified); corrected the "broken tax_query" finding; attribute census (334→336, 326→328); risks section updated. |
| `reports/2026-11-24-phase-1-1-decisions-finalization.md` | Added a **SUPERSEDED** note flagging that the 292/42 "correction" and the "broken tax_query" finding were disproven in Phase 1.1.1 (historical record preserved). |

## 9. Changed-File List

**Modified (documentation only):**
1. `docs/research/woocommerce-attribute-technical-analysis.md`
2. `docs/research/product-requirements-discovery.md`
3. `docs/research/architecture-options-and-recommendations.md`
4. `docs/research/reference-plugin-analysis.md`
5. `docs/testing/test-fixtures.md`
6. `reports/2026-11-24-phase-1-investigation.md`
7. `reports/2026-11-24-phase-1-1-decisions-finalization.md`

**Created:**
8. `reports/2026-11-24-phase-1-1-1-tax-query-verification.md` (this report)

**Not committed (gitignored, workspace-only):**
- `temp/gpa-1111-count-check.php`
- `temp/gpa-1111-taxquery-test.php`

## 10. Safety Confirmation

**[VERIFIED]** No production code or live data was modified:
- No database writes (all WP-CLI usage was read-only `eval-file` with `SELECT`-only scripts
  + `core version` / `plugin list`).
- No taxonomy repairs, no product edits, no attribute changes.
- No plugin/theme install/activate/deactivate.
- No changes to `wp-content/` (Blocksy parent, Blocksy Companion, WooCommerce, or any
  plugin).
- No changes to the `blocksy-child` theme or any existing child-theme module.
- No Phase 2 implementation work was started.

## 11. VERIFIED / NOT VERIFIED / TECHNICAL INTERPRETATION / RECOMMENDATION

### VERIFIED (live, read-only, 2026-11-24)
- All-status product counts: **336 total, 293 variable, 43 simple** (direct SQL +
  `get_the_terms` loop + `WP_Query` baseline all agree).
- Published product counts: **326 total, 290 variable, 36 simple**.
- `product_type` term counts are **not stale** (`variable`=290, `simple`=36, matching
  published counts).
- `get_terms( hide_empty=true )` returns both `variable` and `simple`.
- `tax_query` with `field => 'slug'` returns all 293 variable / 43 simple products.
- `tax_query` without `field` (default `term_id`) + slug string returns **0 rows**
  (reproduced).
- `wp_parse_id_list( ['variable'] )` → `[0]`.
- `WP_Term_Query( get=all, slug=variable )` returns the term (count=290) — `hide_empty`
  not applied on that path.
- WC API: `get_type()`/`get_the_terms()` correctly report 17028=variable, 13143=simple.
- Both fixtures (17028, 13143) are published and carry `_product_attributes`.
- The 292/42/334 figures excluded the two fixtures (293−1, 43−1, 336−2).

### NOT VERIFIED
- The exact identity of the 2 orphaned `term_relationships` rows (TEST 6 vs CROSS-CHECK 1
  discrepancy) — noted as a minor observation, not investigated (out of scope, D21).
- Whether `persian-woocommerce` filters `product_type` term output (D19 — deferred to
  Phase 2).

### TECHNICAL INTERPRETATION
- The 0-row result is fully explained by the `field` default + `wp_parse_id_list()`
  coercion + `array_filter()` drop + `0 = 1` clause (source-confirmed in WP 7.1.5).
- The 44/294 vs 43/293 relationship-row discrepancy is most likely 2 orphaned rows
  (1 simple, 1 variable) from deleted posts.

### RECOMMENDATION
- Per-product type detection: `$product->get_type()` / `is_type()` (preferred) or
  `get_the_terms( $id, 'product_type' )`.
- Batch `tax_query` filtering: always `field => 'slug'` (or `name`).
- Do not rely on `term->count` for type detection; do not assume `tax_query` is broken.

## 12. Git State

| Item | Value |
|------|-------|
| Base commit | `7359c20` (tip of `phase-1-1-decisions-finalization`) |
| Branch | `phase-1-1-1-tax-query-verification` |
| Work commit SHA | `7662fe81df721df6bc09df93b2aa8805b65eb082` (`docs: Phase 1.1.1 — verify product_type tax_query root cause + correct product counts`) |
| Remote verification | `git ls-remote origin phase-1-1-1-tax-query-verification` → `7662fe81df721df6bc09df93b2aa8805b65eb082` — **matches the pushed work commit** |
| Working-tree status | clean (verified after commit) |
| `main` | unchanged at `2b4b090` (no merge performed) |
| Phase 2 | not started |

> A follow-up metadata commit records this Git state. The work commit SHA above is the
> commit containing all substantive changes; the metadata commit is documentation-only.