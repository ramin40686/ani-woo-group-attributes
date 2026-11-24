# Reference Plugin Analysis — WooCommerce Group Attributes (Phase 1)

> Analysis of the official **WooCommerce Group Attributes** plugin as the target experience
> to match (NOT a runtime dependency). Sources: the official PDF documentation
> (`docs/references/WooCommerce Group Product Attributes Plugin.pdf`, extracted 2026-11-23),
> the 11 admin screenshots in `docs/references/screenshots/`, and the WooCommerce.com
> documentation page. Provenance: `[REFERENCE DOC]` = from the PDF/screenshots ·
> `[NOT VERIFIED]` = not determinable from available sources.

## 1. What the reference plugin does (feature map)

`[REFERENCE DOC]`

| # | Area | Behavior |
|---|------|----------|
| 1 | **Products → Group Attributes** (admin menu) | Create/edit/delete attribute groups. A group has: a name, a set of **existing attribute values** selected to display under it, an optional **group category**, and **drag-and-drop value sorting**. A list screen shows all created groups. |
| 2 | **Products → Group Attributes Categories** (admin menu) | Create/edit/delete group categories; categories are attached to groups from each group's settings. |
| 3 | **Product edit → Product Data → Attributes** | "Create group for this product" toggle; "Manage Attribute Groups" select + **Load** button (loads one group's attributes onto the product at a time); "Load Group Categories" field (loads all groups of a category at once); after loading, the admin selects which attribute values display on the product page; **drag-and-drop group sorting** per product. |
| 4 | **WooCommerce → Settings → Group Attributes** | General settings: table style, location, table colors; optional **"View Attribute" button on the shop page**. |

## 2. Mapping to Anishop's architecture

| Reference behavior | Anishop implementation (Phase 2 design input) |
|--------------------|-----------------------------------------------|
| Products → Group Attributes menu | CPT `ani_gpa_group` (per project rules — the child theme cannot add a top-level "Products" submenu the same way a plugin does; the CPT registration provides its own admin menu, which is the standard WP equivalent). |
| Group Attributes Categories menu | Taxonomy `ani_gpa_group_cat` (hierarchical) with its own admin UI. |
| Product Data → Attributes integration | GPA section on the product edit screen (metabox or data-panel extension) with the same toggle / Load / Load Group Categories / value selection / group sorting controls. |
| WooCommerce → Settings → Group Attributes | Appearance → Anishop → GPA Settings tab (house convention; the child theme does not own WooCommerce's settings page). |
| Frontend grouped attribute tables | Blocksy custom layer (R1, see `anishop-integration-analysis.md` §3) with table style / location / colors from settings. |

**Key divergence (accepted):** the reference plugin is a standalone plugin with its own
menus and settings page; Anishop's module is a child-theme module, so admin surfaces move
to the house locations (CPT menu + Appearance → Anishop). The **behavior** (not the menu
placement) is what must match.

## 3. Data model implied by the reference

- A **group** = name + ordered list of attribute values + category. The reference selects
  *existing attribute values* — i.e. values that already exist on the store's attributes
  (taxonomy terms or custom attribute values). GPA's CPT stores the group definition;
  per-product assignment + selected values live in product meta
  (`_ani_gpa_assigned_groups`, `_ani_gpa_selected_values` — per project rules).
- **Per-product state** (loaded groups, selected values, group order) is product-scoped,
  matching the reference's "load onto this product" workflow.

## 4. Live-data fit (Anishop)

`[LIVE-VERIFIED 2026-11-24]` The reference's "select existing attribute values" workflow
fits Anishop's data well: 326 products carry attributes; 1521 custom + 4125 taxonomy
attribute entries; 399 distinct custom attribute names; 507 distinct `pa_*` keys. The
value-selection UI must therefore handle **both** attribute kinds and the delimiter
anomaly (294 comma-joined custom values — see
`woocommerce-attribute-technical-analysis.md` §2).

## 5. "View Attribute" shop-page button (D10)

`[REFERENCE DOC]` The PDF states the setting enables a "View Attribute" button on the shop
page. **The exact behavior (modal, link to product, anchor scroll, or other) is not
specified in the available documentation or screenshots** — the screenshots cover admin
screens only. `[NOT VERIFIED]`

Per decision D10, the behavior will be **replicated from the reference plugin** and
determined during Phase 2 (options: (a) install the reference plugin temporarily in a
sandbox for behavioral observation — requires owner approval per Safety rules; (b) infer
from the WooCommerce.com documentation; (c) owner specifies the desired behavior).
The Phase 2 architecture must keep the button's behavior **pluggable** (a single render
function + setting) so the final choice is a small change.

## 6. What the reference does NOT define (gaps to decide in Phase 2)

1. Exact "View Attribute" button behavior (§5).
2. How groups handle **variation-linked** attributes (Anishop has 408 `is_variation=1`
   entries; the reference docs are silent).
3. Behavior when a group's attribute no longer exists on a product (stale group).
4. Empty states (product with no loaded groups; group with no selectable values).
5. Multi-value custom attributes with non-pipe delimiters (Anishop-specific; the
   reference assumes WC-standard `|` data).
