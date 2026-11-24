# Architecture Options and Recommendations (Phase 1)

> Options A/B/C for the GPA module architecture, with an evidence-based recommendation.
> Inputs: `woocommerce-attribute-technical-analysis.md`, `anishop-integration-analysis.md`,
> `reference-plugin-analysis.md`, `product-requirements-discovery.md`.

## 1. The three architectural axes

The design decomposes into three independent decisions:

1. **A — Data ownership:** where group definitions and per-product selections live.
2. **B — Frontend rendering:** how grouped attribute tables reach the product page.
3. **C — Value parsing:** how multi-value custom attributes are normalized.

## 2. Axis A — Data ownership

| Option | Description | Verdict |
|--------|-------------|---------|
| **A1 (recommended)** | CPT `ani_gpa_group` + taxonomy `ani_gpa_group_cat` for group definitions; product meta `_ani_gpa_assigned_groups` + `_ani_gpa_selected_values` for per-product state; Theme Mod for settings. | Matches project rules exactly; presentation-layer (D9); no native data writes. |
| A2 | Store everything in product meta (no CPT). | Rejected: groups are store-wide entities reused across products; a CPT gives list/edit UI, taxonomy categories, and clean permissions for free. |
| A3 | Store group definitions in an option (serialized). | Rejected: no admin UI, no taxonomy, no scalability; against the decided data model. |

**Recommendation: A1** (already the decided data model in `project-rules.md` /
`memory-bank.md`).

## 3. Axis B — Frontend rendering

| Option | Description | Verdict |
|--------|-------------|---------|
| **B1 (SELECTED — D16)** | Blocksy custom layer (`blocksy:woocommerce:product:custom:layer`) at a configurable position; GPA renders its own grouped tables. | House pattern (buy-box, inline-variations); Pro-free; position = the "location" setting; no WC template coupling. **Selected direction, NOT yet runtime-verified** — exact hook/registration/position to be confirmed in Phase 2. |
| B2 | Filter the native `shop_attributes` table (`woocommerce_product_attribute` / `woocommerce_get_product_attribute`). | Viable but couples GPA to WC template internals and to `persian-woocommerce` filters; harder to style independently; "location" setting becomes awkward. |
| B3 | Template override of `single-product/product-attributes.php`. | Rejected: child theme has no template overrides today; fragile across WC updates. |

**Selected: B1** (owner decision D16, Phase 1.1), with the native table left visible by
default (D17: a setting can hide it; it must never disappear when GPA has no renderable
content).

## 4. Axis C — Value parsing / delimiter normalization — **DEFERRED (D20)**

> **Owner decision D20 (Phase 1.1):** correcting/normalizing existing Anishop attribute
> values is **NOT** a current project objective. Axis C is **deferred** — it is NOT a
> mandatory implementation requirement at this stage. GPA must respect the actual stored
> values and must not silently split them with an unapproved heuristic. The options below
> are retained for a possible future decision.

| Option | Description | Verdict |
|--------|-------------|---------|
| C1 | Read-only display normalization: GPA splits custom values on `|`, `،`, `,` at read time; per-attribute "single value" override for spec-list strings. No data migration. | Deferred. Consistent with D9; reversible; handles all 294 live multi-value attributes; override resolves the spec-list ambiguity. |
| C2 | Data migration: rewrite 294 stored strings to pipe-joined. | Rejected as default: mutates native attribute data (against D9's spirit), irreversible without backup, unnecessary for a presentation module. |
| C3 | Persian comma only (ignore ASCII comma). | Rejected: 41 live attributes use ASCII comma; would leave them unsplit. |

**Current scope: no automatic comma-based normalization.** If/when the owner reopens this
issue, the evidence-based candidate is C1 (splitter `preg_split('/[|،,]/u', $value)`, trim,
drop empties, then apply a per-attribute override if set).

## 5. Selected architecture direction (A1 + B1; C deferred)

> **Owner decision D22 (Phase 1.1):** A1 + B1 is the selected direction for Phase 2
> technical design. C (delimiter normalization) is deferred (D20). This is a design
> direction, **not** an implementation authorization.

```
inc/ani-group-product-attributes/
  config.php      — Theme Mod schema + single sanitizer (anishop_gpa_settings)
  bootstrap.php   — CPT ani_gpa_group + taxonomy ani_gpa_group_cat registration;
                    asset registration; hook wiring
  admin.php       — Appearance → Anishop tabs (anishop_admin_tabs filter);
                    group list/edit UI; product-edit GPA section; save handlers
                    (nonce + capability + single-authority sanitization)
  render.php      — frontend grouped-table renderer (Blocksy custom layer, B1);
                    value resolution (raw values; delimiter normalization deferred, D20);
                    "View Attribute" shop button (pluggable, D18)
assets/js/ani-group-product-attributes.js      — frontend (if any; likely none)
assets/js/ani-group-product-attributes-admin.js — drag-and-drop (vanilla IIFE)
assets/css/ani-group-product-attributes.css    — frontend tables (RTL-first)
assets/css/ani-group-product-attributes-admin.css
```

**Data flow (frontend):** product page → render.php reads
`_ani_gpa_assigned_groups` (ordered group IDs) + `_ani_gpa_selected_values`
(`group_id => [value, ...]`) → for each group, resolve the attribute (taxonomy terms or
custom string) → render the **raw stored values** (delimiter normalization deferred, D20)
→ render grouped table at the configured position. No writes to native data.

**Data flow (admin product edit):** "Load" / "Load Group Categories" → AJAX (REST-first
per house rules) returns the group's attribute values for the current product → admin
selects values + sorts groups → save handler writes the two product meta keys (nonce +
`edit_product` cap + autosave/revision guards).

## 6. Risks & mitigations

| Risk | Mitigation |
|------|-----------|
| Over-splitting spec-list values (if C is ever enabled) | Per-attribute single-value override; default delimiters configurable in settings. **Deferred (D20) — not in current scope.** |
| `persian-woocommerce` filters native output | B1 keeps GPA output independent; verify in Phase 2 (D19). |
| N+1 term queries on product pages | Use the product object's cached attributes; batch term fetches per taxonomy. |
| Admin memory (334 products) | Raw/batched queries + pagination in group list; no per-product `wc_get_product()` loops. |
| Stale groups (attribute removed) | Renderer skips missing attributes gracefully; admin shows a warning. |
| Variation-linked attributes | Never alter `is_variation` semantics; GPA selection is presentation-only (D9, D15). |
| Broken `tax_query` on `product_type` (stale term counts) | Use the API / `get_the_terms` for product-type detection, never `tax_query`/`term->count`. |

## 7. What Phase 2 must finalize

1. Confirm **B1 (selected)** — verify the custom-layer hook, registration mechanism, and
   render position against the installed Blocksy 2.1.62 with a live render test.
2. Verify `persian-woocommerce`'s attribute-output filters (required by D19).
3. Investigate the "View Attribute" button behavior (D18) — keep the render function
   pluggable; do not install the reference plugin on the production site.
4. **Delimiter normalization (C) is DEFERRED (D20)** — out of Phase 2 scope unless the
   owner reopens it.
5. Inspect the pre-existing `anishop_attribute_presentation` option for collisions.
6. Confirm the exact A1 meta schema, serialization format, permissions, and lifecycle
   behavior (per D22) before any implementation.
7. Produce the full technical architecture (file-by-file, hook-by-hook) for approval.

