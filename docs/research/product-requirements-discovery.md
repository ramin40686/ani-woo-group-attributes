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

### R-L2 — Multi-delimiter value normalization `[LIVE-VERIFIED 2026-11-24]`
**0** custom attributes use the WC-standard pipe; **259** use the Persian comma `،`
(U+060C); **41** use the ASCII comma `,`. All 294 multi-value custom attributes currently
render as single unsplit values on the frontend (live proof: product 4328, `نوع کاربری`
→ `رسمی، روزمره، ورزشی`). GPA's parser must normalize `|`, `،`, and `,` into value lists.
**Decision needed (Phase 2):** read-only display normalization (S1, recommended) vs data
migration (S2, rejected as default). Because some comma strings are spec lists, not value
lists, S1 needs a per-attribute "single value" override.

### R-L3 — Product type via taxonomy, not meta `[WC 11.3.1 SOURCE]`
WC 11.3.1 stores product type in the `product_type` taxonomy; `_product_type` meta does
not exist on this site (0 rows). GPA must use `$product->get_type()` / the taxonomy.

### R-L4 — "Variable-typed without variations" is the norm `[LIVE-VERIFIED 2026-11-24]`
293 products are variable-typed, but only **3** have actual `product_variation` children
(33 variation posts total). 287 variable-typed products have `is_variation=1` attributes
with no variations. GPA must **not** assume variations exist; variation-linked attribute
semantics apply only to `get_variation_attributes()` output.

### R-L5 — Data anomalies to tolerate, not fix `[LIVE-VERIFIED 2026-11-24]`
- Stale `product_type` term counts (`variable` term `count=0` but 293 members).
- 12 simple-typed products carry `is_variation=1` attributes.
- 6 variable-typed products have no variation attribute.
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

### R-L10 — Performance ceiling `[LIVE-VERIFIED 2026-11-24]`
326 products × up to ~15 attributes. A `wc_get_product()` loop over all products caused a
memory fatal during auditing — GPA's admin screens (group list, product load) must use
batched/raw queries and pagination, not per-product object loops.

## 3. Non-goals (confirmed)

- No runtime dependency on the reference plugin or Blocksy Pro.
- No new plugins, no JS frameworks, no page builders.
- No modification of Blocksy core, WC core, or existing child-theme modules.
- No data migration of native attributes (S2 rejected as default).
- No fix of pre-existing data anomalies (R-L5).

## 4. Open product questions (for owner / Phase 2)

1. **ASCII comma as separator** (R-L2): treat `,` as a value separator, or only `،` and
   `|`? Evidence: 41 custom attributes use ASCII comma; some are value lists
   (`پلاستیک ABS, پلی کربنات`), some are spec strings (`4K@24/25/30/60fps, 1080p@...`).
   Recommendation: support all three delimiters **plus** a per-attribute single-value
   override.
2. **"View Attribute" button behavior** (D10): unspecified in reference docs — see
   `reference-plugin-analysis.md` §5.
3. **Group vs product value authority:** when a group defines values and a product selects
   a subset, the product's selection wins (per reference workflow). Confirmed by spec;
   no conflict found.
