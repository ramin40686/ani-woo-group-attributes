# WooCommerce Attribute Technical Analysis (Phase 1)

> Evidence-based analysis of the native WooCommerce attribute architecture as installed on Anishop
> (WooCommerce **11.3.1**, WP **7.1.5**, PHP **8.3.36**). All live-data items were verified on
> 2026-11-24 with read-only WP-CLI audits (`temp/gpa-phase1-attr-audit*.php`, removed after capture).
> Source items were verified by reading the installed plugin source (read-only).
>
> Provenance labels: `[LIVE-VERIFIED 2026-11-24]` = read-only query against the live site ·
> `[WC 11.3.1 SOURCE]` = read from the installed plugin source · `[CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE VERIFIED]` = curl-rendered HTML.

## 1. Storage model (two patterns)

### 1.1 Global attribute taxonomies

- Table `wp_woocommerce_attribute_taxonomies` holds the **registered** global attributes.
  `[LIVE-VERIFIED 2026-11-24]` Anishop has **3 registered** global attributes:
  `color`, `memory-capacity`, `country`.
- Each registered attribute maps to a `product_attribute` taxonomy named `pa_{slug}`.
  `[LIVE-VERIFIED 2026-11-24]` The site actually has **521 `pa_*` taxonomies** existing and
  **507 distinct `pa_*` keys in use** on products — far more than the 3 registered ones.
  Terms are created on demand (WooCommerce auto-registers `pa_*` taxonomies when terms are
  assigned), so "registered" ≠ "in use".
- Taxonomy attribute values live in **terms** (`wp_terms` / `wp_term_relationships`), not in
  the product meta. The `_product_attributes` entry for a taxonomy attribute has `value = ''`.

### 1.2 Per-product serialized attributes

- Every product's attributes are stored in the **`_product_attributes`** post meta as a
  serialized associative array keyed by attribute name (`pa_{slug}` for taxonomy,
  URL-encoded raw name for custom). `[LIVE-VERIFIED 2026-11-24]` **326 of 334 products**
  carry `_product_attributes`.
- Each entry: `name`, `value`, `position`, `is_visible`, `is_variation`, `is_taxonomy`.
  `[WC 11.3.1 SOURCE]` `includes/data-stores/class-wc-product-data-store-cpt.php`
  (`read_attributes()` / `update_attributes()`).
- Custom attribute values are stored as **one string** in `value`. WooCommerce's own writer
  joins multiple values with the pipe delimiter (`WC_DELIMITER = '|'`,
  `includes/class-woocommerce.php:570`; `wc_implode_text_attributes()`).
  `[WC 11.3.1 SOURCE]`

### 1.3 Live attribute census `[LIVE-VERIFIED 2026-11-24]`

| Metric | Count |
|--------|-------|
| Total product posts | 334 |
| Products with `_product_attributes` | 326 |
| Attribute entries — taxonomy (`is_taxonomy=1`) | 4125 |
| Attribute entries — custom (`is_taxonomy=0`) | 1521 |
| Entries with `is_variation=1` | 408 |
| Entries with `is_visible=1` | 5555 |
| Distinct custom attribute names | 399 |
| Distinct `pa_*` keys in use | 507 |

**Implication:** GPA must support **both** attribute kinds. Value lists come from terms
(`wc_get_product_terms`) for taxonomy attributes and from the parsed `value` string for
custom attributes.

## 2. The delimiter anomaly (critical finding)

WooCommerce's standard custom multi-value parser, `wc_get_text_attributes( $raw )`
(`includes/wc-attribute-functions.php:21`), **splits only on `|`**, trims each part, and
drops empties. `[WC 11.3.1 SOURCE]`

Anishop's real data does **not** follow the pipe convention:

| Custom-attribute delimiter usage (per parsed attribute entry) | Count |
|---|---|
| Contains pipe `\|` (WC standard) | **0** |
| Contains Persian comma `،` (U+060C) | **259** |
| Contains ASCII comma `,` | **41** |
| Single value (no delimiter) | 1227 |
| Multi-value (any non-pipe delimiter) | **294** |

`[LIVE-VERIFIED 2026-11-24]` (corrected audit using literal U+060C detection; an earlier
audit run used a wrong byte sequence and falsely reported zero Persian-comma usage — the
corrected run is authoritative).

**Consequence:** all **294** multi-value custom attributes are seen by WooCommerce as
**single values**. Live frontend proof: product 4328's custom attribute `نوع کاربری`
(stored `رسمی، روزمره، ورزشی`) renders in the native attributes table as one unsplit
`<p>` blob: `[CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE VERIFIED]`

```html
<tr class="woocommerce-product-attributes-item woocommerce-product-attributes-item--attribute_...">
  <th class="woocommerce-product-attributes-item__label" scope="row">نوع کاربری</th>
  <td class="woocommerce-product-attributes-item__value"><p>رسمی، روزمره، ورزشی</p></td>
</tr>
```

Samples (real data): `پلی کربنات، پلاستیک ABS` · `کمپینگ ، موزیک ، کاربری عمومی` (spaces
around the comma) · `آچار فرانسه، انبردست، متر فلزی، پیچ گوشتی، چکش` ·
`RJ-45 LAN، USB Type-C` · ASCII-comma: `پلاستیک ABS, پلی کربنات` ·
`4K@24/25/30/60fps, 1080p@25/30/60/120/240fps; gyro-EIS`.

**Architecture input — DEFERRED (owner decision D20, Phase 1.1):** the owner has decided
that correcting/normalizing existing Anishop attribute values is **NOT** a current project
objective. GPA must **respect the actual stored values** and must **not** silently split
them with an unapproved heuristic. Delimiter normalization is recorded as a **deferred
issue** for possible future investigation. The two candidate strategies below are retained
for that future decision:

- **S1 — Read-only display normalization:** GPA parses the stored string with
  a multi-delimiter splitter at read time. No data migration, no writes to
  `_product_attributes`, fully consistent with D9 (presentation layer only). Risk: a value
  that legitimately contains a comma (e.g. `4K@24/25/30/60fps, 1080p@...` is a spec list,
  not a value list) would be over-split. Mitigation: per-group/per-attribute delimiter
  choice in the admin, or a "single value" override.
- **S2 — Data migration:** rewrite the 294 stored strings to pipe-joined form. Rejected as
  the default: it mutates native attribute data (against D9's spirit), is irreversible
  without a backup, and is unnecessary for a presentation-layer module.


Note the ambiguity is real: some comma-joined strings are value lists (`رسمی، روزمره،
ورزشی`) and some are single spec strings (`4K@24/25/30/60fps, 1080p@...`). A purely
mechanical splitter cannot distinguish them — hence the per-attribute override in S1.

## 3. Product type in WC 11.3.1 (major finding)

**Product type is stored in a `product_type` taxonomy, NOT in `_product_type` meta.**
`[WC 11.3.1 SOURCE]` `WC_Product_Data_Store_CPT::get_product_type()`
(`class-wc-product-data-store-cpt.php:2249-2270`):

```php
$terms = get_the_terms( $product_id, 'product_type' );
$product_type = ! empty( $terms ) && ! is_wp_error( $terms )
    ? sanitize_title( current( $terms )->name )
    : ProductType::SIMPLE;
```

`[LIVE-VERIFIED 2026-11-24]` The site has **zero `_product_type` meta rows** (verified
three ways: grouped meta query, `wp post meta get`, raw meta scan of a sample product).
Product type distribution via the `product_type` taxonomy:

| Type | Products (all statuses) | Products (published only) |
|------|-------------------------|---------------------------|
| variable | **292** | 289 |
| simple | 42 | 35 |
| external / grouped | 0 | 0 |
| **Total product posts** | **334** | **324** |

> **Corrected 2026-11-24 (Phase 1.1):** the original Phase 1 figures (293 variable /
> 43 simple = 336) were each off by one. A targeted re-audit confirms **334 total product
> posts = 292 variable + 42 simple** (all statuses); 324 published (289 variable + 35
> simple); 10 non-published (2 private, 8 draft). No product carries more than one
> `product_type` term.

Reconciliation with variation data `[LIVE-VERIFIED 2026-11-24]`:

| Cross-tab | Count |
|-----------|-------|
| variable-typed AND has `is_variation=1` attribute | 287 |
| variable-typed, no variation attribute | 6 |
| simple-typed BUT has `is_variation=1` attribute (anomaly) | 12 |
| Products with actual `product_variation` children | **3** (33 variation posts total) |

**Data-quality observations (pre-existing, out of scope for GPA, documented for awareness):**

1. The `product_type` term counts are stale (the `variable` term reports `count=0` while
   actually containing 292 products; `simple` reports `count=35` vs 42 actual).
2. **`WP_Query`/`tax_query` on `product_type` is BROKEN on this site** `[LIVE-VERIFIED
   2026-11-24, Phase 1.1]`: because the term counts are stale, `get_terms( hide_empty=true )`
   (which `WP_Query` uses internally to resolve tax_query terms) omits the `variable` term,
   so a `tax_query` for `product_type => variable` returns **0 rows** even though 292
   products have that term. GPA must detect product type via the **API**
   (`$product->get_type()` / `is_type( 'variable' )`) or `get_the_terms( $id, 'product_type' )`
   — **never** via `tax_query` or `term->count`.
3. ~290 products are typed `variable` but have **no variation children** — the store's
   catalog is overwhelmingly "variable-typed without variations".
4. 12 simple-typed products carry `is_variation=1` attribute flags.

**Architecture inputs:**

- GPA must detect product type via the **API** (`$product->get_type()` /
  `is_type( 'variable' )`) or `get_the_terms( $id, 'product_type' )` — **never** via
  `_product_type` meta (absent in this WC version) and **never** via `tax_query` /
  `term->count` (broken on this site — see data-quality observation 2).
- GPA must not assume "variable-typed ⇒ has variations". Variation-linked semantics apply
  only to attributes returned by `$product->get_variation_attributes()` (287 products have
  such attributes; only 3 products have live variation rows).
- Per D9 / dev-notes §4: GPA must not alter variation-linked attributes' semantics.

## 4. Key APIs `[WC 11.3.1 SOURCE]`

| API | Notes |
|-----|-------|
| `WC()->product_factory->get_product( $id )` | → `WC_Product` |
| `$product->get_attributes( $context = 'view' )` | Defined in `includes/abstracts/abstract-wc-product.php:590`. `view` (default) applies the `woocommerce_product_attributes` filter via `get_prop()`; `edit` returns the raw array (used by the admin Attributes tab). **Neither context filters by `is_visible`** — visibility is applied by the caller (e.g. `wc_display_product_attributes()`). |
| `$product->get_attribute( $attribute )` | Single-parameter signature in 11.3.1 (`abstract-wc-product.php:2366`). Returns a **string** (taxonomy: term names joined `, `; text: options joined via `wc_implode_text_attributes()` = ` \| `); `''` when absent. |
| `$product->get_variation_attributes()` | `attribute_name => options[]` for variation attributes only. |
| `wc_get_text_attributes( $raw_attributes )` | `includes/wc-attribute-functions.php:21`. Splits on `WC_DELIMITER` (`\|`) only, `html_entity_decode`s first, trims, drops empties. |
| `wc_implode_text_attributes( $attributes )` | `includes/wc-attribute-functions.php:46`. Joins with ` \| `. |
| `wc_get_product_terms( $product_id, $taxonomy, $args = array() )` | `includes/wc-term-functions.php:226`. Terms of a taxonomy attribute (product **ID**, not object). |
| `wc_attribute_label( $name, $product = '' )` | `includes/wc-attribute-functions.php:213`. Human label (global attribute name or raw name). |
| `WC_Product_Attribute` | `is_taxonomy()`, `get_name()`, `get_options()`, `get_slugs()`, `get_terms()`. |
| Data store | `WC_Product_Data_Store_CPT::update_attributes()` / `read_attributes()` own `_product_attributes`. `read_attributes()` skips taxonomy attributes whose taxonomy no longer exists. |


## 5. Lifecycle / integration points

| Point | Hook / location | Relevance to GPA |
|-------|-----------------|------------------|
| Product save | `woocommerce_process_product_meta_{post_type}` / `woocommerce_process_product_meta` | GPA product-meta save handlers (nonce + `current_user_can( 'edit_product', $post_id )`). |
| Product data panel — General tab | `woocommerce_product_options_general_product_data` (fires at the end of the **General** tab view, `includes/admin/meta-boxes/views/html-product-data-general.php:261`) | NOT the Attributes tab. Available if GPA's product-edit UI is placed on the General tab. |
| Product data panel — Attributes tab | `woocommerce_product_options_attributes` (fires in `includes/admin/meta-boxes/views/html-product-data-attributes.php:69`) | The native Attributes tab integration point. The product data panel is rendered by `WC_Meta_Box_Product_Data::output()` → `html-product-data-panel.php`; `output_tabs()` includes all six tab views (general, inventory, shipping, linked-products, attributes, advanced). GPA's "load groups" UI attaches here (or a dedicated metabox) — Phase 2 decision. |
| Variation UI | `woocommerce_variation_settings` | GPA must not interfere. |
| Frontend single product | `woocommerce_product_attribute` action + `wc_get_product` loop in `woocommerce/templates/single-product/product-attributes.php` | Native table GPA renders alongside (B1) per D17. |
| Frontend visibility filter | `wc_attributes_array_filter_visible` — a **PHP callback function** (`includes/wc-attribute-functions.php:430`), NOT a WordPress filter hook. Used as the `array_filter()` callback inside `wc_display_product_attributes()` (`includes/wc-template-functions.php:4282`). Returns true when the attribute is a `WC_Product_Attribute`, `get_visible()` is true, and (for taxonomy attributes) the taxonomy exists. | GPA's selected-values list is independent of `is_visible` (D9: presentation only). |
| Type change | `woocommerce_product_type_changed` | GPA must survive type changes (groups stay assigned; rendering adapts). |

## 6. Frontend rendering (native)

`[CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE VERIFIED]` The native single-product attributes
table (product 4328, fetched via curl):

- Container: `<table class="woocommerce-product-attributes shop_attributes">`.
- Rows: `<tr class="woocommerce-product-attributes-item woocommerce-product-attributes-item--attribute_{slug}">` with `<th class="...__label">` + `<td class="...__value"><p>…</p></td>`.
- Custom multi-value strings render **unsplit** (see §2).
- The table sits in the product summary area; Blocksy composes the single-product layout
  from layers (see `anishop-integration-analysis.md` §3).

## 7. Security / performance / compatibility constraints

- **Security:** all GPA admin writes need nonce + capability (`edit_product` /
  `manage_options` for settings); all output escaped; inputs sanitized
  (`sanitize_text_field`, `wp_unslash`, `absint`). CPT/taxonomy registration must set
  `show_ui`/capabilities explicitly.
- **Performance:** 326 products × up to ~15 attributes each; group rendering must avoid
  N+1 term queries (batch `wc_get_product_terms` per taxonomy or use the product object's
  cached attributes). Admin group-list screens need pagination. Assets load only on
  product pages / admin screens that need them (established child-theme convention).
- **Compatibility:** must coexist with `persian-woocommerce` 10.0.7 (active; localizes
  WooCommerce strings and may filter attribute output) and all 14 existing child-theme
  modules. No Blocksy Pro dependency. No new plugins, no JS frameworks.

## 8. Open questions

1. **Delimiter strategy S1 vs S2** (§2) — **DEFERRED** by owner decision D20 (Phase 1.1).
   Not a Phase 2 requirement. GPA must respect stored values as-is until the owner decides.
2. **`persian-woocommerce` interaction** — whether it filters `woocommerce_product_attribute`
   output or the attributes table; verify in Phase 2 before finalizing the render hook.
   `[NOT VERIFIED]` (required by D19).
3. **Stale `product_type` term counts** — pre-existing data issue; GPA must not depend on
   `term->count` or `tax_query` (use the API or `get_the_terms`). No fix in scope (D21).
4. **`tax_query` on `product_type` is broken** on this site (stale term counts) — GPA must
   use the API / `get_the_terms` for product-type detection (see §3, observation 2).

