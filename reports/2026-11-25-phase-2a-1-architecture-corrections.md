# Phase 2A.1 — Architecture Specification Corrections (GPA)

## 1. Task Title
Complete the documentation-only Phase 2A.1 corrections to the Phase 2A architecture
specification (`docs/architecture/phase-2a-architecture-specification.md`) before Phase 2B
begins.

## 2. Date
2026-11-25

## 3. Executive Summary
Corrected the Phase 2A architecture specification in place, fixing incorrect WordPress /
WooCommerce API assumptions and strengthening the value-validity model. The specification
grew from 861 to 1184 lines (360 insertions, 37 deletions, single file). The core semantic
fix: **group membership alone does not prove a value exists on a product** — valid
selections must satisfy `A ∩ B`, where `A` is the group vocabulary
(`_ani_gpa_group_values`) and `B` is the product's actual attribute values. Four incorrect
API patterns were corrected (`get_post()` post-type argument, `post_type` filtered on
`wp_postmeta`, bare `get_term()` without taxonomy, unsafe `(bool)` sanitization), a
three-tier value validity model (§5.7) and an exact matching contract (§5.8) were added,
save-time and render-time revalidation were specified (§4.4), the Phase 2B checklist was
expanded from 8 to 23 items, and three new owner-decision items (U11–U13) were recorded.
The work is **documentation only**: no PHP/JS/CSS implementation, no database writes, no
live WordPress data changes. Phase 2B and Phase 3 remain **not authorized / not started**.

## 4. Task Scope
- Correct the Phase 2A specification for the seven requested correction areas:
  1. `get_post()` group-reference validation (no post-type argument).
  2. Custom-attribute aggregation query requiring a `wp_posts` / `wp_postmeta` JOIN.
  3. Three-tier value validity model (group vocabulary / product attribute values /
     selected values).
  4. Exact matching between group values and actual product attributes.
  5. Taxonomy-scoped term validation.
  6. Strict Boolean sanitization.
  7. Save-time and render-time revalidation.
- Expand the Phase 2B verification checklist and record new unresolved items.
- Add a header note referencing the Phase 2A.1 correction.
- Create this completion report.
- Commit and push the documentation changes on a dedicated branch.

## 5. Authorized Work
The project owner authorized Phase 2A.1 as a **documentation-only** correction pass over
the Phase 2A specification. No implementation, no database writes, no live data changes,
and no Phase 2B / Phase 3 work were authorized.

## 6. Files Created
- `reports/2026-11-25-phase-2a-1-architecture-corrections.md` — this completion report.

## 7. Files Modified
- `docs/architecture/phase-2a-architecture-specification.md` — the Phase 2A architecture
  specification, corrected in place (861 → 1184 lines; 360 insertions, 37 deletions).

## 8. Files Deleted
- None.

## 9. Detailed Changes

All changes are in `docs/architecture/phase-2a-architecture-specification.md`.

### 9.1 Header note
- Added a **Phase 2A.1 correction note** (2026-11-25) under the title stating that the
  document was corrected in place for the API-assumption and value-validity issues, that
  the approved identifiers and architecture are unchanged, and that the Phase 2B checklist
  (§11) was expanded.

### 9.2 Correction area 1 — `get_post()` group-reference validation (§3.3, §7.2)
- **Before:** `get_post( $group_id, 'ani_gpa_group' )` — implied `get_post()` accepts a
  post-type constraint as its second argument.
- **After:** `get_post( $group_id )` (no post-type argument), then explicit checks: the
  post exists, `post_type === 'ani_gpa_group'`, and the status is permitted. The corrected
  sequence is specified in §3.3 (save/assignment context) and §7.2 (render context,
  `publish` gate).
- **Evidence:** [WP core API — `get_post()`, `wp-includes/post.php`] the second argument of
  `get_post()` is `$output` (`OBJECT` / `ARRAY_A` / `ARRAY_N`), not a post type.

### 9.3 Correction area 2 — custom-attribute aggregation JOIN (§5.2)
- **Before:** the aggregation query filtered `post_type` directly on `wp_postmeta`.
- **After:** the query **must JOIN the posts table** because `wp_postmeta` has **no**
  `post_type` column; `post_type` (and `post_status`) live on `wp_posts`. A corrected
  conceptual SQL shape is given (table names via `$wpdb->posts` / `$wpdb->postmeta`,
  prefix never hardcoded):
  `SELECT pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID =
  pm.post_id WHERE pm.meta_key = '_product_attributes' AND p.post_type = 'product'
  [AND p.post_status IN (...)]`.
- Added: product-status inclusion is an owner decision (**U12**, alternatives a/b/c, trash
  never recommended); guarded `unserialize()` + array check for the serialized
  `_product_attributes` blob; malformed-blob skip (never abort the aggregation); transient
  caching with explicit invalidation events (create / update / delete / trash / restore;
  import / bulk / programmatic write paths flagged [2B-VERIFY] → B1-14).

### 9.4 Correction area 3 — three-tier value validity model (§5.7, new)
- Added §5.7 defining three distinct sets:
  - **Tier A — Group vocabulary:** the reusable candidate values defined by the group
    (`_ani_gpa_group_values`), store-wide.
  - **Tier B — Product attribute values:** the values actually present on the current
    product (native WC attribute data, read-only).
  - **Tier C — Selected product values:** the admin's explicit per-product choices
    (`_ani_gpa_selected_values`), valid only where **C ⊆ (A ∩ B)**.
- Added the worked failure example (group "رنگ‌ها" vocabulary Green/Black/White/Gold vs
  product's actual Green/Black → only Green and Black selectable/renderable).
- Clarified that **loading a group onto a product** makes its vocabulary available as
  candidates in the admin UI only — it does not copy values onto the product and does not
  modify native attributes (D9 presentation-only).

### 9.5 Correction area 4 — exact matching contract (§5.8, new)
- Added §5.8, the identity contract used at save (§4.2) and render (§7.2):
  - **Taxonomy attributes:** match by **term ID + taxonomy identity together** (never by
    display label): taxonomy-scoped `get_term( $value_id, $attribute )`; resolved term's
    `slug` must equal the stored `value`; the term must be actually assigned to the product
    (`wc_get_product_terms()` / `get_options()`); a term in any other taxonomy is never a
    valid match.
  - **Custom attributes:** match by **attribute-name identity (byte-for-byte) + exact
    value identity** against `wc_get_text_attributes()` parsed values (WC's existing
    parsing: split on `|` only, `html_entity_decode`, trim, drop empties). No new
    normalization on `|` / `،` / `,` (D20).
  - **Invalid / deleted / mismatched / stale references:** group save rejects or flags;
    product save drops; render skips (rest of group still renders).
- Unresolvable edge cases (e.g. entity-encoding differences) are marked [2B-VERIFY] rather
  than guessed.

### 9.6 Correction area 5 — taxonomy-scoped term validation (§5.1, §5.8, §7.2)
- **Before:** display-text resolution used a bare `get_term( $value_id )`.
- **After:** all term lookups are **taxonomy-scoped** — `get_term( $value_id, 'pa_{slug}' )`
  — so the term is verified to belong to the expected taxonomy. A bare `get_term( $value_id
  )` does **not** prove taxonomy membership (a term sharing the same `term_id` in a
  different `term_taxonomy` is not a valid match).
- **Evidence:** [WP core API — `get_term()`, `wp-includes/taxonomy.php`] passing the
  taxonomy argument scopes the lookup to that taxonomy.

### 9.7 Correction area 6 — strict Boolean sanitization (§9.3)
- **Before:** the settings sanitizer used a bare `(bool)` cast.
- **After:** strict parsing distinguishing three input classes — valid true (`true`,
  `'1'`, `'true'`, `'yes'`, `'on'`, case-insensitive), valid false (`false`, `'0'`,
  `'false'`, `'no'`, `'off'`, case-insensitive), and invalid/ambiguous (anything else →
  reject the field, fall back to the schema default, **never** coerce to `true`). An
  explicit allowlist is the recommended mechanism. `rest_sanitize_boolean()` is acceptable
  only for inputs known to originate from a REST request, and even then it maps any other
  non-empty string to `true` (e.g. `rest_sanitize_boolean( 'yes' ) === true`), so it does
  not by itself satisfy the rule. The admin form POST path (not a REST payload) is covered
  by the same strict parsing.
- **Evidence:** [WP core API — `rest_sanitize_boolean()`, `wp-includes/rest-api.php`]
  `rest_sanitize_boolean( 'yes' )` returns `true`; `(bool) 'false' === true`.

### 9.8 Correction area 7 — save-time and render-time revalidation (§4.4, new; §4.2, §7.2)
- Added §4.4 **Validation and revalidation contract** with two explicit passes:
  - **Save-time (product save, §4.2):** validate the group reference (exists,
    `post_type === 'ani_gpa_group'`, status permitted — U11); validate each value ref
    against tier A (group vocabulary) and tier B (product membership, §5.8); persist only
    the accepted subset; drop invalid refs (not persisted as valid selections).
  - **Render-time (§7.2):** treat stored metadata as **untrusted** and re-validate
    everything: group reference (exists, post type, `publish`), term/value identity, and
    product membership; skip anything that fails; never crash, never render a broken row.
  - **Stale selected values:** a value no longer on the product is ignored at render,
    rejected at next save, and **retained in storage** — no destructive auto-cleanup
    (lifecycle confirmation is owner item **U13**).
- §4.2 (product save) and §7.2 (render) were updated to reference the §4.4 contract and
  the §5.8 matching rules.

### 9.9 Phase 2B checklist expansion (§11)
- Expanded from B1-1…B1-8 to **B1-1…B1-23**. New items:
  - B1-9 `get_post()` retrieval + post-type validation sequence (no post-type argument).
  - B1-10 allowed group-status behavior (U11) + render `publish` gate.
  - B1-11 `wp_posts` ⋈ `wp_postmeta` JOIN aggregation query on the live catalog.
  - B1-12 product-status inclusion in the aggregation (U12); trash excluded.
  - B1-13 guarded `unserialize()` + array check; malformed blobs skipped.
  - B1-14 cache invalidation coverage (create / update / delete / trash / restore; import /
    bulk / programmatic write paths).
  - B1-15 taxonomy matching (term ID + taxonomy identity + product term membership).
  - B1-16 custom matching (exact name identity + exact parsed-value identity).
  - B1-17 name/value identity rules; no splitting/normalization on `|` / `،` / `,` (D20).
  - B1-18 strict taxonomy-scoped term validation (missing / wrong-taxonomy /
    slug-inconsistent rejected).
  - B1-19 strict Boolean parsing (no arbitrary non-empty string → `true`).
  - B1-20 save-time validation (group ref + tier A + tier B + term identity; persist only
    the accepted subset).
  - B1-21 render-time validation (re-verify group ref, term/value identity, product
    membership; exclude stale/invalid).
  - B1-22 stale selected-value behavior (ignored at render, rejected at save, retained in
    storage, no destructive cleanup — U13).
  - B1-23 native table visibility (stays visible when GPA content absent **or** entirely
    stale/invalid; hidden only when valid renderable content exists and the setting
    permits).

### 9.10 New unresolved items (§12)
- **U11** [UNRESOLVED] — group-status policy: which post statuses may be *assigned* to a
  product at save time (vs the render-time `publish` gate). Recommended: `publish` +
  optionally `draft`.
- **U12** [UNRESOLVED] — custom-value aggregation: which product statuses contribute values
  to the admin picker (publish-only vs all non-trashed vs all). Recommended: publish-only
  or all non-trashed; never trash.
- **U13** [UNRESOLVED] — stale selected-value lifecycle confirmation: ignored at render,
  rejected at next save, retained in storage, no destructive auto-cleanup.

### 9.11 Consistency updates
- §5.1 display text now uses the taxonomy-scoped lookup with a pointer to §5.8.
- §5.2 cache-invalidation note cross-reference corrected to B1-14.
- §7.2 render steps now reference §3.3 (group validation), §5.8 (matching), and the
  taxonomy-scoped term lookup.
- §7.3 native-table hide condition now requires **valid** renderable content (group passed
  §3.3 validation **and** at least one value passed §5.8 re-validation); stale/invalid GPA
  metadata must not trigger the hide (D17 fallback preserved).
- §10.1 sanitization bullet updated to reference the strict Boolean parsing rule (§9.3).
- §13 evidence appendix extended with the WP core API evidence entries (get_post,
  get_term, rest_sanitize_boolean, wp_postmeta schema) and the WC 11.3.1 source entries
  (wc_get_text_attributes, wc_get_product_terms, read_attributes).

## 10. Tests and Verification

**Static / documentation verification:**
- Confirmed the corrected document structure (13 sections, 1184 lines) via `grep` on
  headings.
- Confirmed the diff scope: `git diff --stat 8a52064` → single file changed, 360
  insertions, 37 deletions.
- Re-checked that no incorrect patterns remain in the specification:
  - `get_post( $id, 'ani_gpa_group' )` — **absent** (only the corrected
    `get_post( $group_id )` + explicit post-type check remains).
  - `post_type` filtered directly on `wp_postmeta` — **absent** (the JOIN shape is the only
    aggregation query).
  - unsafe bare `(bool)` sanitization as the recommended mechanism — **absent** (only
    referenced as the anti-pattern to avoid, with the strict allowlist rule).
  - taxonomy-unverified `get_term( $value_id )` as the resolution mechanism — **absent**
    (only referenced as insufficient; all resolution paths use the taxonomy-scoped form).
  - wording implying group membership alone proves product value validity — **absent**
    (§5.7 states the opposite: valid C ⊆ (A ∩ B)).

**Source verification (static code reading, no writes):**
- `wp-includes/post.php` — `get_post( $post = null, $output = OBJECT )`: second argument is
  the output format, not a post type.
- `wp-includes/taxonomy.php` — `get_term( $term, $taxonomy = '' )`: passing the taxonomy
  scopes the lookup; a bare call does not prove taxonomy membership.
- `wp-includes/rest-api.php` — `rest_sanitize_boolean()`: maps any non-empty string other
  than the false-words to `true` (e.g. `'yes'` → `true`).
- `wp-content/plugins/woocommerce/includes/wc-formatting.php` — `wc_get_text_attributes()`:
  splits on `|` only, `html_entity_decode`s, trims, drops empties.
- `wp-content/plugins/woocommerce/includes/wc-term-functions.php` —
  `wc_get_product_terms( $product_id, $taxonomy )` returns `WP_Term[]` for the product.
- `wp-content/plugins/woocommerce/includes/class-wc-product-attribute.php` —
  `is_taxonomy()` / `get_id()` semantics for kind detection.
- `wp-content/plugins/woocommerce/includes/data-stores/class-wc-product-data-store-cpt.php`
  — `read_attributes()`: taxonomy → term IDs; custom → `wc_get_text_attributes()`.
- `wp_postmeta` schema (WP core `schema.sql`): columns `post_id`, `meta_key`,
  `meta_value` — **no** `post_type` column (the JOIN requirement).

**Git verification:**
- Confirmed branch `phase-2a-1-architecture-corrections` created from base `8a52064`
  (clean tree at start).
- Confirmed only `docs/architecture/phase-2a-architecture-specification.md` modified
  (plus this new report file); no other files touched.

## 11. Verified Results
- **All seven correction areas applied** — [STATIC CODE EVIDENCE] each correction is
  grounded in the cited WP core / WC 11.3.1 source (see §10).
- **No incorrect API patterns remain** — [STATIC] grep re-check over the corrected
  specification (see §10).
- **Approved identifiers and architecture preserved** — [STATIC] `ani_gpa_group`,
  `ani_gpa_group_cat`, `_ani_gpa_assigned_groups`, `_ani_gpa_selected_values`,
  `_ani_gpa_group_values`, `anishop_gpa_settings`, A1 + B1 direction, and the D17
  native-table fallback are unchanged.
- **Document is complete and consistently labeled** — [STATIC] 13 sections, 1184 lines;
  all five label types ([DECISION D#] / [REQ] / [RECOMMEND] / [UNRESOLVED] /
  [2B-VERIFY]) present; Phase 2B checklist B1-1…B1-23; unresolved items U1–U13.

## 12. Not Verified / Limitations
- **No runtime verification was performed** — this is a documentation task; the corrected
  API behaviors (e.g. `get_term( $id, $taxonomy )` rejection semantics, the JOIN query
  results on the live catalog) are source-verified only and remain Phase 2B items
  (B1-9…B1-23).
- **Blocksy custom-layer runtime behavior (B1)** — still not runtime-verified (B1-1…B1-3);
  unchanged by this correction pass.
- **`persian-woocommerce` runtime compatibility** — static evidence only (B1-5, D19);
  unchanged.
- **U11 / U12 / U13** — owner decisions still required; the specification records the
  recommended behavior but does not assume final approval.

## 13. Risks and Open Questions
- **U11 (group-status policy):** save/assignment-time status set is undecided; the spec
  recommends `publish` (+ optionally `draft`) with a hard `publish` render gate.
- **U12 (aggregation status set):** undecided; the spec recommends publish-only or all
  non-trashed, never trash.
- **U13 (stale selected-value lifecycle):** the recommended behavior (ignore at render,
  reject at save, retain in storage, no destructive cleanup) needs owner confirmation.
- **U1 (View Attribute)** remains a true [UNRESOLVED] (D18); unchanged by this pass.
- **Import / bulk / programmatic write paths** that bypass `save_post_product` when writing
  `_product_attributes` are not yet enumerated (B1-14); the transient invalidation design
  depends on that enumeration.

## 14. Scope Compliance
Only authorized documentation work was performed:
- **No PHP implementation was performed** — no PHP/JS/CSS file was created or modified;
  no module code exists; the only changed file is the architecture specification
  (documentation).
- **No database or live WordPress data was changed** — no WP-CLI mutating command was run;
  no option, post, term, meta, or theme mod was written; all source references were read
  from the existing installation files (read-only).
- **Phase 2B and Phase 3 were not started** — no runtime verification, no implementation,
  no testing against the live site.
- No catalog, product, attribute, or setting was changed. No delimiter normalization or
  catalog cleanup was performed (D20, D21). No unrelated files were touched. The
  two-path filesystem boundary (workspace + target WordPress installation) was respected.

## 15. Git Information
- **Branch:** `phase-2a-1-architecture-corrections`
- **Base branch:** `phase-2a-architecture-specification` (per task context; `main` is the
  ultimate integration target per project rules)
- **Base commit:** `8a520641021fef0aa77f16234981e034be8e43c5`
- **Work commit SHA:** `__WORK_COMMIT_SHA__`
  (`docs: Phase 2A.1 architecture specification corrections + completion report`) — the
  commit containing all substantive changes.
- **Remote push status:** pushed to `origin/phase-2a-1-architecture-corrections` and
  verified via `git ls-remote origin phase-2a-1-architecture-corrections`.
- **Working-tree status:** clean (verified after commit).
- **`main`:** unchanged (no merge performed).

> A follow-up metadata commit records this Git state. The work commit SHA above is the
> commit containing all substantive changes; the metadata commit is documentation-only.

## 16. Phase / Task Status
Phase 2A.1 (architecture specification corrections) **complete** — the corrected
specification and this report are delivered and ready for owner review. Phase 2B (runtime
verification) **NOT authorized / not started**. Phase 3 (implementation) **NOT authorized /
not started**. No implementation code exists.

## 17. Recommended Next Step
Owner reviews the corrected Phase 2A specification
(`docs/architecture/phase-2a-architecture-specification.md`), decides the new owner items
(U11 group-status policy, U12 aggregation status set, U13 stale selected-value lifecycle),
ratifies the [RECOMMEND] items (U2–U10), and explicitly authorizes **Phase 2B** (runtime
verification per the expanded §11 checklist, B1-1…B1-23).

## 18. Stop Confirmation
Work has stopped after completing the Phase 2A.1 corrections and this report, committing
and pushing the documentation, and verifying the final Git state. Awaiting the project
owner's next instruction. No further phase or task has been started.

