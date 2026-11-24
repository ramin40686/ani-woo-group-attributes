# Product Requirements Discovery (Phase 1)

> Requirements distilled from the ROADMAP, the reference plugin, and the **live Anishop
> data** (read-only audits, 2026-11-24). This document records what the live environment
> forces on the design — beyond the written spec.

## 1. Mandatory functionality (from ROADMAP §1 — unchanged)

1. Group attribute management (admin): CRUD groups; name; select existing attribute
   values; group category; drag-and-drop value sorting; list all groups.
2. Group category management (admin): CRUD categories; attach to groups.
3. Product-level group loading: "create group for this product" toggle; "Manage Attribute
   Groups" + Load (one by one); "Load Group Categories" (all of a category); select which
   values display; drag-and-drop group sorting per product.
4. General settings: table style, location, table colors; optional "View Attribute"
   shop-page button.
5. Cross-cutting: error handling; responsive desktop + mobile; Persian RTL; minimal
   resource consumption.

## 2. Live-environment requirements (discovered in Phase 1)

These are **new** requirements that the written spec does not state but the live data
forces. Each is evidence-backed.

### R-L1 — Dual attribute-kind support `[LIVE-VERIFIED 2026-11-24]`
Anishop uses **both** taxonomy attributes (4125 entries, 507 `pa_*` keys) and custom
attributes (1521 entries, 399 distinct names). GPA's value-selection UI and renderer must
handle both. Value sources: `wc_get_product_terms()` for taxonomy; parsed `value` string
for custom.

### R-L2 — Multi-delimiter values exist; normalization DEFERRED `[LIVE-VERIFIED 2026-11-24]`
**0** custom attributes use the WC-standard pipe; **259** use the Persian comma `،`
(U+060C); **41** use the ASCII comma `,`. All 294 multi-value custom attributes currently
render as single unsplit values on the frontend (live proof: product 4328, `نوع کاربری`
→ `رسمی، روزمره، ورزشی`). **Owner decision D20 (Phase 1.1): delimiter normalization is
DEFERRED** — GPA must respect the actual stored values and must not silently split them
with an unapproved heuristic. The owner may later decide whether commas represent separate
values or part of a single value. Until then, GPA renders stored values as-is.

### R-L3 — Product type via taxonomy, not meta `[WC 11.3.1 SOURCE]`
WC 11.3.1 stores product type in the `product_type` taxonomy; `_product_type` meta does
not exist on this site (0 rows). GPA must use `$product->get_type()` / the taxonomy.

### R-L4 — "Variable-typed without variations" is the norm `[LIVE-VERIFIED 2026-11-24, re-verified 2026-11-24 Phase 1.1.1]`
293 products are variable-typed (all statuses; 290 published), but only **3** have actual
`product_variation` children (33 variation posts total). 287 variable-typed products have
`is_variation=1` attributes with no variations. GPA must **not** assume variations exist;
variation-linked attribute semantics apply only to `get_variation_attributes()` output.
The parent product is the primary attribute source (D15).

### R-L5 — Data anomalies to tolerate, not fix `[LIVE-VERIFIED 2026-11-24, re-verified 2026-11-24 Phase 1.1.1]`
- 12 simple-typed products carry `is_variation=1` attributes.
- 6 variable-typed products have no variation attribute.
- (The Phase 1.1 "stale `product_type` term counts" anomaly was re-verified in Phase 1.1.1
  and **does not exist**: the `variable` term reports `count=290` and `simple` reports
  `count=36`, both accurate for the published population.)
GPA must degrade gracefully (never crash, never mis-render) on all of these. Out of scope
to fix (pre-existing data quality).

### R-L6 — Coexistence with 14 existing child-theme modules `[LIVE-VERIFIED 2026-11-23]`
No duplicate hooks, no parallel save handlers, no modification of existing modules;
two MD5-locked mobile-nav assets must stay byte-identical; GPA uses its own Theme Mod key
and the `anishop_admin_tabs` filter for admin tabs.

### R-L7 — `persian-woocommerce` 10.0.7 active `[LIVE-VERIFIED 2026-11-23]`
Localizes WooCommerce and may filter attribute output. GPA's own output is independent;
if the native-table filter approach (R2) is chosen, this plugin's filters must be
accounted for. `[NOT VERIFIED]` — verify in Phase 2.

### R-L8 — Presentation-layer only (D9) `[DECISION D9]`
GPA renders selected attribute values **without modifying** native WooCommerce attribute
data (`_product_attributes`, visibility, variation semantics). Precedent: the existing
inline-variations module.

### R-L9 — RTL/Persian + house style `[PROJECT RULES]`
All frontend markup RTL-first; Vazirmatn; accent `#2872FA`; radii 2/4px; flat. Admin UI
bilingual (English source + Persian `.mo`, text domain `anishop`).

### R-L10 — Performance ceiling `[LIVE-VERIFIED 2026-11-24, re-verified 2026-11-24 Phase 1.1.1]`
328 products × up to ~15 attributes. A `wc_get_product()` loop over all products caused a
memory fatal during auditing — GPA's admin screens (group list, product load) must use
batched/raw queries and pagination, not per-product object loops.

### R-L11 — `tax_query` on `product_type` requires `field => 'slug'` `[LIVE-VERIFIED 2026-11-24, re-verified 2026-11-24 Phase 1.1.1]`
The Phase 1.1 claim that `tax_query` on `product_type` is "broken" (stale term counts,
`get_terms( hide_empty=true )` omitting the `variable` term) was **incorrect**. The actual
cause of the "0 rows" observation was a **query-construction error**: passing
`terms => 'variable'` without `field` defaults to `field => 'term_id'`, and
`wp_parse_id_list()` converts the slug to `0`, which `array_filter()` then drops, producing
a `0 = 1` clause. With `field => 'slug'`, the identical query returns all **293** variable
products. GPA should detect product type via the API (`$product->get_type()` /
`is_type( 'variable' )`) or `get_the_terms( $id, 'product_type' )`; if a `tax_query` is
used, it must specify `field => 'slug'`.

## 3. Non-goals (confirmed)

- No runtime dependency on the reference plugin or Blocksy Pro.
- No new plugins, no JS frameworks, no page builders.
- No modification of Blocksy core, WC core, or existing child-theme modules.
- **No delimiter normalization / data migration of native attributes** (D20 — deferred; S2 rejected).
- **No fix of pre-existing data anomalies** (R-L5, D21) — including the `is_variation=1`
  flags on simple-typed products and variable-typed products without variation attributes.
  GPA tolerates them. (The Phase 1.1 "stale `product_type` term counts / broken `tax_query`"
  anomaly was **disproven** in Phase 1.1.1 — see R-L5/R-L11.)
- **No production catalog cleanup** (D21).

## 4. Open product questions (for owner / Phase 2)

1. **ASCII comma as separator** (R-L2) — **DEFERRED (D20).** The owner may later decide
   whether commas in existing values represent separate values or part of a single value.
   GPA must not assume either until then. (Evidence retained: 41 custom attributes use
   ASCII comma; some are value lists, some are spec strings.)
2. **"View Attribute" button behavior** (D18): unspecified in reference docs — to be
   investigated in Phase 2; see `reference-plugin-analysis.md` §5.
3. **Native `shop_attributes` table visibility** (D17): default is visible + optional hide
   setting; final UX confirmation pending Phase 2.
4. **Group vs product value authority:** when a group defines values and a product selects
   a subset, the product's selection wins (per reference workflow). Confirmed by spec;
   no conflict found.
