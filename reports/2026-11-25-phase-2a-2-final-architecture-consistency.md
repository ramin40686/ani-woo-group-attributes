# Phase 2A.2 — Final Architecture Consistency Fixes (GPA)

## 1. Task Title
Perform the final, narrowly scoped documentation corrections required to finalize the
Phase 2A architecture specification: strengthen the safe-unserialization contract, make
empty-group behavior consistent, and formally ratify owner decisions U11, U12, and U13.

## 2. Date
2026-11-25

## 3. Executive Summary
Completed the final documentation-only consistency pass over the Phase 2A architecture
specification (`docs/architecture/phase-2a-architecture-specification.md`) and the project
decision records. Three substantive corrections were applied: (1) the safe-unserialization
contract for `_product_attributes` was strengthened after verifying that the installed
WP 7.1.5 `maybe_unserialize()` does **not** disable object instantiation, so the contract
now mandates `unserialize( …, [ 'allowed_classes' => false ] )` plus type and structure
validation; (2) empty-group behavior was made consistent across the specification (a group
assigned with no selected values is administratively valid but renders nothing and does not
count as renderable GPA content); and (3) owner items U11, U12, and U13 were formally
ratified as decisions D24, D25, and D26. The Phase 2B verification checklist was updated to
reflect the ratified decisions, and a new §13.5 API-verification appendix documents the
`maybe_unserialize()` / `is_serialized()` / `unserialize()` behavior. The work is
**documentation only**: no PHP/JS/CSS implementation, no database writes, no live WordPress
data changes. Phase 2B and Phase 3 remain **not authorized / not started**.

## 4. Objective and Scope
**Objective.** Finalize the Phase 2A architecture documentation so that it is internally
consistent, free of unsafe API assumptions, and fully aligned with the owner's ratified
decisions before Phase 2B (runtime verification) begins.

**Scope (documentation only):**
1. Strengthen the safe-unserialization contract for `_product_attributes` (§5.2) —
   `maybe_unserialize()` alone is not sufficient; require `allowed_classes => false` +
   validation.
2. Make empty-group behavior consistent across the specification (§4.2, §4.3, §7.2, §7.3,
   §7.4) — no empty tables, headings, or labels; not renderable content.
3. Record U11 (group-status policy) as an approved decision (D24).
4. Record U12 (publish-only product aggregation) as an approved decision (D25).
5. Record U13 (stale selected-value lifecycle) as an approved decision (D26).
6. Update the Phase 2B verification checklist (§11) to reflect the ratified decisions.
7. Restructure §12 to separate resolved owner decisions from open items.
8. Add a §13.5 API-verification appendix documenting the unserialization behavior.
9. Synchronize `.clinerules/decisions.md` (add D24–D27).
10. Add a historical addendum to the Phase 2A.1 report noting the later ratification of
    U11–U13.
11. Create this completion report.
12. Commit and push on a dedicated branch.

**Out of scope:** any implementation (PHP/JS/CSS), any database or live WordPress data
change, any Phase 2B runtime verification, any Phase 3 work, any catalog cleanup or
delimiter normalization (D20, D21).

## 5. Starting Branch and Commit
- **Starting branch:** `phase-2a-1-architecture-corrections`
- **Starting HEAD:** `b4da04a11c47b3799147677969069144d99cb848`
- **Working tree at start:** clean (verified).
- **Dedicated branch created:** `phase-2a-2-final-architecture-consistency` (from the
  starting HEAD).

## 6. Changed Files
**Modified:**
- `docs/architecture/phase-2a-architecture-specification.md` — the Phase 2A architecture
  specification (206 lines changed across the header, §3.3, §4.2, §4.3, §4.4, §5.2, §6.5,
  §7.2, §7.3, §7.4, §11, §12, and the new §13.5).
- `.clinerules/decisions.md` — added decisions D24, D25, D26, D27 (4 lines).
- `reports/2026-11-25-phase-2a-1-architecture-corrections.md` — added §19 post-completion
  addendum (11 lines) noting the later ratification of U11–U13.

**Created:**
- `reports/2026-11-25-phase-2a-2-final-architecture-consistency.md` — this completion
  report.

**Deleted:** None.

## 7. Safe Unserialization Correction
**Problem.** The Phase 2A.1 specification described a "guarded `unserialize()` + array
check" for the serialized `_product_attributes` blob, but did not explicitly rule out the
common `maybe_unserialize()`-first pattern. Verification against the installed WordPress
7.1.5 showed that `maybe_unserialize()` is **not** a safe deserializer for untrusted
metadata:
- `maybe_unserialize( $data )` (`wp-includes/functions.php:653`) calls
  `@unserialize( trim( $data ) )` with the **default** `allowed_classes` — meaning embedded
  objects **are instantiated**.
- For a serialized array blob (the actual shape of `_product_attributes`), a
  `maybe_unserialize()`-first pattern would already have unserialized the data with the
  default `allowed_classes` — instantiating any embedded objects — before any guarded
  fallback could run.

**Fix.** The §5.2 contract now mandates, for the raw-SQL aggregation path:
1. **No object instantiation** from untrusted serialized metadata, ever.
2. **`allowed_classes => false`** wherever explicit unserialization is required;
   `maybe_unserialize()` is explicitly **not** relied upon for deserialization safety on
   this path.
3. **Type validation:** the unserialized result must be an `array`; anything else (string,
   scalar, `false`, `__PHP_Incomplete_Class`) is rejected.
4. **Structure validation:** verify the expected array shape (a list of entries, each an
   array with `name` / `value` keys and string values) before processing; non-matching
   entries (including `__PHP_Incomplete_Class` objects) are rejected.
5. **Graceful handling:** malformed / corrupted / unexpected metadata is skipped (and
   logged under `WP_DEBUG`); it must never cause warnings, fatals, or uncontrolled
   processing, and must never abort the whole aggregation.
6. **Exclusion:** invalid metadata contributes **no** values to the Custom Attribute
   aggregation.

An illustrative (non-implementation) pattern is included, and a note clarifies that if a
value is instead obtained via the metadata API (`get_post_meta()` → `maybe_unserialize()`),
the same contract applies to the result. A new **§13.5 API-verification appendix**
documents the verified behavior of `maybe_unserialize()`, `is_serialized()`, and
`unserialize( …, [ 'allowed_classes' => false ] )` with source references.

## 8. Empty-Group Rendering Correction
**Problem.** The specification contained inconsistent statements about what happens when a
group is assigned to a product with no selected values (or when all selected values are
stale/invalid).

**Fix.** Empty-group behavior is now consistent across §4.2, §4.3, §7.2, §7.3, and §7.4,
and recorded as decision **D27**:
- A group may be assigned to a product **without selecting any values** — a valid
  administrative state; the assignment is **not** rejected solely because no values have
  been selected.
- Frontend: a group with no selected values (or no currently valid selected values)
  produces **no** frontend table — no empty tables, no headings, no group labels without
  actual attribute rows; such groups are **skipped**.
- Native table (D17 preserved): the native WooCommerce attributes table remains visible by
  default; GPA may hide it **only** when valid, renderable GPA content exists and the
  setting permits hiding. An assigned group with no selected values, and invalid or stale
  selected values, do **not** count as renderable GPA content; if no valid GPA content
  remains, the native table must remain visible.

## 9. U11 Owner Decision (Group-Status Policy) — D24
Ratified by the project owner on 2026-11-25 and recorded as **D24**:
- Groups with status `publish` **or** `draft` may be selected and assigned to products.
- Groups with any other status must **not** be accepted for new assignments or saved
  references.
- Only groups with `post_status === 'publish'` may be rendered on the frontend; draft
  groups must never appear on the public frontend, even if their IDs exist in product
  metadata.
- The specification distinguishes group existence, expected post type (`ani_gpa_group`),
  allowed status during administration, and published status required for frontend
  rendering.

Reflected in §2.1, §3.3, §6.5, §12.1, and checklist item B1-10.

## 10. U12 Owner Decision (Publish-Only Product Aggregation) — D25
Ratified by the project owner on 2026-11-25 and recorded as **D25**:
- Only products with `post_status = 'publish'` may contribute custom-attribute values to
  the store-wide group vocabulary.
- Products in any other status (at minimum `draft`, `pending`, `private`, `future`,
  `trash`) must **not** contribute values.
- The status restriction is applied to the **posts table** (`p.post_status = 'publish'` in
  the `wp_posts` ⋈ `wp_postmeta` JOIN), not to the postmeta table.
- This decision does **not** introduce any catalog cleanup or migration behavior.

Reflected in §5.2 (the conceptual SQL now includes `AND p.post_status = 'publish'`),
§12.1, and checklist items B1-11 / B1-12.

## 11. U13 Owner Decision (Stale Selected-Value Lifecycle) — D26
Ratified by the project owner on 2026-11-25 and recorded as **D26**:
- **Render time:** revalidate every selected value against the current product attributes;
  ignore stale values; stale values are not rendered, do not make a group renderable, and
  do not suppress the native WooCommerce attributes table.
- **Save time:** revalidate submitted values; reject invalid or stale submitted
  selections; persist only accepted valid selections.
- **Storage:** do not automatically delete previously stored stale selections; preserve
  them until an explicit valid save replaces the submitted selection set; no automatic
  background cleanup or migration.
- **Rendering is read-only:** no metadata modification, no deletion of stale references,
  no rewriting of group assignments, no destructive cleanup.

Reflected in §4.4, §12.1, and checklist items B1-20 / B1-21 / B1-22.

## 12. Phase 2B Checklist Changes
The §11 checklist introduction now states that **all items are pending** (none executed or
verified as of Phase 2A.2). Items updated to reflect the ratified decisions:
- **B1-10** — now references the decided group-status policy (U11, D24): confirm `publish`
  + `draft` accepted at save/assignment, other statuses rejected, render gate enforces
  `publish` only.
- **B1-12** — now references the decided product-status filter (U12, D25): confirm only
  `publish` products contribute values (status filter on `wp_posts` in the JOIN).
- **B1-13** — now references the decided safe-unserialization contract: confirm object
  instantiation is prevented (`allowed_classes => false` — `maybe_unserialize()` alone does
  **not** provide this), malformed data safely rejected, array structure validated, invalid
  records excluded.
- **B1-20** — save-time validation now includes: a group may be assigned with no selected
  values (valid administrative state, not rejected); stale/invalid submitted selections
  rejected (U13/D26).
- **B1-21** — render-time validation now includes: a group with no (valid) selected values
  produces no table, no heading, no empty rows; rendering is read-only.
- **B1-22** — now references the decided stale selected-value lifecycle (U13, D26).
- **B1-23** — native table visibility now includes: stays visible when assigned groups have
  no selected values; hidden only when valid renderable content exists and the setting
  permits.

All checklist items remain **pending verification** — none has been executed.

## 13. Preserved Decisions
The following previously approved decisions and architecture elements are **unchanged** by
this pass:
- **Architecture direction A1 + B1** (D22) — data ownership A1 + Blocksy custom-layer
  rendering B1.
- **D9** — group value selection is presentation-only (no modification of native
  WooCommerce attribute data).
- **D14** — dual attribute support (global taxonomy + custom product attributes).
- **D15** — parent product is the primary attribute source.
- **D16** — Blocksy custom layer selected (B1), not yet runtime-verified.
- **D17** — native WooCommerce attributes table visible by default; optional hide setting;
  fallback preserved (reinforced, not changed, by D27).
- **D18** — View Attribute button behavior deferred (U1 remains unresolved).
- **D19** — persian-woocommerce compatibility must be tested.
- **D20** — delimiter normalization deferred (no comma/Persian-comma splitting).
- **D21** — no premature data cleanup.
- **D23** — permanent test-fixture policy.
- **Approved identifiers:** `ani_gpa_group`, `ani_gpa_group_cat`,
  `_ani_gpa_assigned_groups`, `_ani_gpa_selected_values`, `_ani_gpa_group_values`,
  `anishop_gpa_settings`, prefixes `ani_gpa_` / `Ani_GPA_` / `.ani-gpa-`, text domain
  `anishop`.

## 14. Remaining Unresolved Decisions
After this pass, the open items are:
- **U1** [UNRESOLVED] (D18) — "View Attribute" button behavior (modal / link / other);
  final decision after Phase 2B investigation (B1-8).
- **U2–U9** [RECOMMEND] — CPT menu placement, group `menu_order`, product-edit section
  placement, "create group for this product" toggle persistence, aggregation strategy,
  `table_style` options, `location` anchor options, Theme Mod key. These retain their
  recommended status and await owner ratification.
- **U10** [RECOMMEND — confirmed by D27] — empty-group rendering (skip vs render empty
  header); now confirmed as "skip; no empty tables or headings."
- **U11 / U12 / U13** — **resolved** this pass as D24 / D25 / D26 (no longer open).

## 15. Scope Compliance
Only authorized documentation work was performed:
- **No PHP/JS/CSS implementation** — no implementation file was created or modified; no
  module code exists; all changed files are documentation (`.md`).
- **No database or live WordPress data was changed** — no WP-CLI mutating command was run;
  no option, post, term, meta, or theme mod was written; all source references (e.g.
  `wp-includes/functions.php`) were read from the existing installation files (read-only).
- **Phase 2B and Phase 3 were not started** — no runtime verification, no implementation,
  no testing against the live site.
- **No catalog, product, attribute, or setting was changed.** No delimiter normalization or
  catalog cleanup was performed (D20, D21).
- **No unrelated files were touched.** The two-path filesystem boundary (workspace + target
  WordPress installation) was respected.
- **`main` was not modified** — no merge was performed.

## 16. Git Information — Final Commit Hash
- **Branch:** `phase-2a-2-final-architecture-consistency`
- **Starting branch:** `phase-2a-1-architecture-corrections`
- **Starting HEAD:** `b4da04a11c47b3799147677969069144d99cb848`
- **Work commit SHA:** `3c27b97e48f03fcd44d0d2dc42b70d4be2f0e69b`
  (`docs: Phase 2A.2 final architecture consistency fixes (safe unserialization,
  empty-group behavior, U11/U12/U13 ratification)`) — the commit containing all
  substantive changes (spec + decisions + 2A.1 addendum).
- **Metadata commit SHA:** `744156101f59edc9deb7c0e564c7eacb725b659b`
  (the commit containing this report.)
- **`main` (local and remote):** `2b4b0902fbaee70519df20f1bcc63723e1d591ba` —
  **unchanged** (no merge performed).

> The work commit SHA is the commit containing all substantive changes; the metadata commit
> SHA is the commit containing this report. Both are recorded above.

## 17. Push and Working-Tree Verification
- **Remote branch:** `origin/phase-2a-2-final-architecture-consistency` — pushed and
  verified via `git ls-remote origin` (resolves to
  `744156101f59edc9deb7c0e564c7eacb725b659b`).
- **Local HEAD == Remote HEAD:** verified at the metadata-commit push — both
  `744156101f59edc9deb7c0e564c7eacb725b659b`. This follow-up commit (which records the
  metadata SHA and this push status) is pushed immediately after, keeping local and remote
  in sync; the final shared HEAD is the follow-up commit.
- **Working tree:** clean (verified after commit).
- **`main` unchanged:** local `main` == remote `main` ==
  `2b4b0902fbaee70519df20f1bcc63723e1d591ba`.

## 18. Phase / Task Status
Phase 2A.2 (final architecture consistency fixes) **complete** — the corrected
specification, synchronized decision records, and this report are delivered and ready for
owner review. Phase 2B (runtime verification) **NOT authorized / not started**. Phase 3
(implementation) **NOT authorized / not started**. No implementation code exists.

## 19. Recommended Next Step
Owner reviews the finalized Phase 2A specification
(`docs/architecture/phase-2a-architecture-specification.md`) and the ratified decisions
(D24–D27), ratifies the remaining [RECOMMEND] items (U2–U10), and explicitly authorizes
**Phase 2B** (runtime verification per the §11 checklist, B1-1…B1-23).

## 20. Stop Confirmation
Work has stopped after completing the Phase 2A.2 corrections and this report, committing
and pushing the documentation, and verifying the final Git state. Awaiting the project
owner's next instruction. No further phase or task has been started. Phase 2A.3 and
Phase 2B have **not** been started.



