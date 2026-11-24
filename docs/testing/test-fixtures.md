# GPA Test-Fixture Registry

> Permanent registry of GPA development/testing fixtures, backups, and test-only data.
> **Keep this document current as the project evolves** (per `project-rules.md` §Test
> Environment Policy, decision D23).
>
> **Isolation rule:** the testing authorization applies ONLY to the fixtures listed here
> and to products explicitly created as GPA test fixtures. Never modify unrelated live
> products, orders, inventory, pricing, global settings, or production modules. Before
> creating/renaming/deleting any global attribute or term, check whether non-test products
> use it. Test-only attributes/terms use the `GPA TEST — ` prefix.

## 1. Original test fixtures (permanent)

### Simple fixture — product 13143

| Field | Value |
|-------|-------|
| Product ID | **13143** |
| Name | ماشین اصلاح فیلیپس مدل OneBlade QP2824/10 (Philips OneBlade QP2824/10) |
| Type | simple |
| Status | publish |
| Original URL | `http://anishop.local/product/%d9%85%d8%a7%d8%b4%db%8c%d9%86-%d8%a7%d8%b5%d9%84%d8%a7%d8%ad-%d9%81%db%8c%d9%84%db%8c%d9%be%d8%b3-%d9%85%d8%af%d9%84-oneblade-qp2824-10/` |
| Attributes | 13 total — 5 taxonomy (`pa_brand`, `pa_at_mdl`, `pa_at_mnbe-anrzhi`, `pa_وضعیت-محصول`, `pa_guarantee`) + 8 custom (Persian-named, incl. multi-value ASCII-comma values e.g. `سری جایگزین` = `QP210,QP220, QP230`) |
| Real variation children | 0 (n/a — simple) |
| Backup | `backups/gpa-test-fixtures/product-13143-*.json` (raw, byte-exact; gitignored) |
| Backup verification | Cross-checked against live: 81/81 meta keys + values, all terms, 0/0 variations — **PASS** |
| Restoration verification | Restored onto hidden copy **17601**; byte-exact meta + terms match — **PASS** |

### Variable fixture — product 17028

| Field | Value |
|-------|-------|
| Product ID | **17028** |
| Name | گوشی موبایل وان پلاس مدل 15 دو سیم کارت ظرفیت 256 گیگابایت و رم 12 گیگابایت (OnePlus 15) |
| Type | variable |
| Status | publish |
| Original URL | `http://anishop.local/product/%da%af%d9%88%d8%b4%db%8c-%d9%85%d9%88%d8%a8%d8%a7%db%8c%d9%84-%d9%88%d8%a7%d9%86-%d9%be%d9%84%d8%a7%d8%b3-%d9%85%d8%af%d9%84-15-%d8%af%d9%88-%d8%b3%db%8c%d9%85-%da%a9%d8%a7%d8%b1%d8%aa-%d8%b8%d8%b1/` |
| Attributes | 2 total — both taxonomy + variation-linked (`pa_color`, `pa_memory-capacity`) |
| Real variation children | **16** (variation posts 17484–17499) |
| Backup | `backups/gpa-test-fixtures/product-17028-*.json` (raw, byte-exact incl. all 16 variations; gitignored) |
| Backup verification | Cross-checked against live: 75/75 meta keys + values, all terms, 16/16 variations (meta + terms) — **PASS** |
| Restoration verification | Restored onto hidden copy **17602**; byte-exact meta + terms + 16/16 variations match — **PASS** |

## 2. Restoration-verification copies (hidden test fixtures)

Created during Phase 1.1 to verify the backups are restorable. Both are `publish` but
`_catalog_visibility = hidden` (not shown in shop/archive). They are byte-exact copies of
the originals (except `_catalog_visibility` and the `GPA TEST — ` title prefix).

| Copy ID | Source | Name | URL | Variations |
|---------|--------|------|-----|-----------|
| **17601** | 13143 | GPA TEST — restore verification: ماشین اصلاح فیلیپس مدل OneBlade QP2824/10 | `http://anishop.local/product/gpa-test-restore-13143/` | 0 |
| **17602** | 17028 | GPA TEST — restore verification: گوشی موبایل وان پلاس مدل 15 … | `http://anishop.local/product/gpa-test-restore-17028/` | 16 |

These copies may be used as additional test fixtures or deleted; they are not required for
restoring the originals.

## 3. Additional test products

| Product ID | Name | Derived from | Created | Purpose |
|-----------|------|--------------|---------|---------|
| _(none yet)_ | | | | |

> Record any additional test products created by duplicating the fixtures here.

## 4. Test-only attributes & taxonomy terms

| Name / slug | Type | Created | Used by |
|-------------|------|---------|---------|
| _(none yet)_ | | | |

> Use the `GPA TEST — ` prefix for all test-only attributes and terms. Record them here.
> **Never** rename or delete a shared production attribute/term for testing.

## 5. Test groups & categories

| Group / category | ID | Created | Notes |
|------------------|----|---------|-------|
| _(none yet — CPT `ani_gpa_group` / taxonomy `ani_gpa_group_cat` not yet registered)_ | | | |

## 6. Known limitations

- The originals (13143, 17028) are **real, published** Anishop products. All fixture
  modifications must be reversible via the backups; the originals must never be lost.
- 17028's two attributes are both variation-linked taxonomy attributes. Custom-attribute
  testing on the variable fixture requires adding `GPA TEST — ` custom attributes.
- 13143 has no variation children (it is simple). Variable-without-variations testing can
  use any of the 289 published variable-typed products that lack children (do not modify
  them — use a duplicate).
- `tax_query` on `product_type` is broken on this site (stale term counts) — use the API /
  `get_the_terms` for product-type detection (see R-L11).
- Restoring meta via the WP meta API double-serializes `_product_attributes` (a WC hook
  re-serializes it). Use raw DB writes (`$wpdb`) for byte-exact restoration.

## 7. Restoration instructions

To restore an original fixture from its backup:

1. Locate the backup: `backups/gpa-test-fixtures/product-{ID}-*.json`.
2. The backup contains: post fields, **all** meta (raw serialized strings), all term
   relationships (term IDs), and variation children (post + meta + terms).
3. Restore by writing the raw meta values **verbatim** to `wp_postmeta` via `$wpdb`
   (bypassing the meta API to avoid double-serialization of `_product_attributes`), setting
   the term relationships via `wp_set_object_terms`, and recreating variation children.
4. Verify by comparing the restored product's raw meta + terms against the backup
   byte-for-byte.

> The Phase 1.1 restoration-verification copies (17601, 17602) demonstrate a successful
> restore. The backup JSONs are the source of truth for recovery.

## 8. Isolation rules (summary)

- Authorized: modify the fixtures above + explicitly-created GPA test fixtures; create test
  groups/categories/attributes/products.
- **Not authorized:** unrelated live products, customer orders/data, real inventory/pricing,
  production attributes outside fixtures, global WooCommerce settings unrelated to GPA,
  production theme modules, bulk catalog operations.
- No cleanup required after each task/phase; accumulated test data may remain.
- If an operation could affect non-test products, **stop and report** first.