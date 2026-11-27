# Report: Phase 3.0 Implementation Roadmap — Limited Corrections (Documentation Only)

**Date:** 2026-11-27
**Branch:** `chore/phase-3-0-roadmap-corrections`
**Base:** `chore/phase-3-0-implementation-roadmap` (commit `7bec9ad73bf9444a6a72791124d6d51d23fcc52a`)
**Task type:** Documentation only — no implementation, no runtime data changes.

---

## 1. Objective

Make **six narrowly scoped documentation corrections** to the existing Phase 3.0
implementation roadmap (`docs/implementation/phase-3-implementation-roadmap.md`) to remove
ambiguities and internal contradictions identified during the roadmap review. This task
**does not** redesign, expand, or modify the approved architecture, and it **authorizes no
implementation**. Phase 3.1 begins only after explicit owner authorization (gate G0).

## 2. The Six Corrections

### Correction 1 — Phase 3.1 CPT acceptance criteria (no CRUD in 3.1)

**Problem:** 3.1 acceptance criterion #1 stated a group post "can be created/edited/trashed
via the admin," and a verification step said "create a group post via the CPT." This
contradicted the CPT's `show_in_menu => false` (no standalone CPT menu) and the fact that
group CRUD is assigned to 3.2.

**Fix:**
- 3.1 acceptance criterion #1 now verifies **CPT registration** (via
  `get_post_type_object( 'ani_gpa_group' )`) and the **absence of a standalone CPT menu**
  (`show_in_menu => false`), and states that group posts are created/edited **only** through
  the GPA submenu interface built in 3.2 — 3.1 provides **no** group CRUD UI.
- The 3.1 verification step now confirms the CPT is registered and that **no** standalone
  CPT menu appears in the rendered sidebar, and notes that group CRUD is verified in 3.2.
- Group CRUD acceptance criteria remain **exclusively** in 3.2 (unchanged: "An admin can
  create, edit, and delete groups from the GPA submenu").

### Correction 2 — Frontend style setting: admin control location

**Problem:** The roadmap defines the Theme Mod `anishop_gpa_settings` → `table_style`
(`default` / `minimal` / `boxed`) but did not identify where an administrator selects or
changes the setting.

**Finding (source review):** The approved architecture specifies only the **storage** of the
setting — spec §10.4 and decision D39 define the Theme Mod key, the single sanitizer, and the
versioned schema. Neither the specification nor any owner decision specifies the **admin UI
location or control method** for changing `table_style`. The house pattern (Appearance →
Anishop page) is an existing convention but is **not** an approved decision for GPA's
setting, and D43's Products-submenu exception covers group management, not this setting.

**Fix:** No location was invented. The missing detail is recorded as an explicit, narrowly
scoped implementation clarification requiring owner approval:
- A note was added to 3.1 (Module foundation) flagging the settings UI location as
  **unresolved — [OWNER-APPROVAL]** (see OI-5).
- A new open item **OI-5** was added to §9.2.
- The Theme Mod architecture and all three approved styles are **preserved** unchanged.

### Correction 3 — B2-32: Phase 3.5 vs Phase 4 testing boundary

**Problem:** 3.5 attributed all of B2-32 ("RTL + Persian + real-mobile") to Phase 3.5, while
§7.5 and the 3.5 exclusions defer real-device (Live Link) testing to Phase 4.

**Fix:** The distinction is now made explicit and consistent:
- 3.5 B2-32 attribution is now "RTL + Persian + **responsive/browser-level checks**; the
  **real-device** portion of B2-32 is Phase 4."
- The 3.5 verification step now says "desktop + mobile **viewport**, RTL — browser-level
  only. Real-device (Live Link) verification is Phase 4, not 3.5."
- The real-device testing requirement is **not** removed from the project; it remains
  assigned to Phase 4 (unchanged in §7.5 and the 3.5 exclusions).

### Correction 4 — Phase 3.3 / 3.4 dependency and parallel-development rules

**Problem:** §5 described both a "strict dependency chain" and the possibility of developing
3.4 in parallel with 3.3, without distinguishing the two.

**Fix:** §5 now explicitly separates:
- **Approval and verification order (strict, invariant):** 3.1 → 3.2 → 3.3 → 3.4 → 3.5.
- **Hard prerequisite (implementation):** a subphase's implementation begins only after its
  declared dependencies are complete; 3.4's only hard prerequisite is 3.1 (the Theme Mod).
- **Independent development:** 3.4's CSS authoring can proceed **in parallel** with 3.3
  (it depends only on 3.1's Theme Mod, not on 3.3's output).
- **Integration/verification (must wait):** 3.4's end-to-end verification (styling *real*
  loaded attributes) waits until 3.3 is complete and verified.

The 3.4 **Dependencies** line was updated to label 3.1 as the hard prerequisite and 3.3 as
required only for end-to-end verification. No subphase was added, removed, or reordered, and
no approval gate changed.

### Correction 5 — D35 superseded-decision classification

**Problem:** The §1 authoritative-inputs row listed the active decisions as "… **D28–D43**,"
a range that includes D35, which is superseded by D43.

**Fix:** The active range is now "**D28–D34, D36–D43** (D35 is superseded by D43 and is
**not** active)." D35 remains in the superseded/obsolete list (and in §6.1). D40–D43 are
unchanged; no owner decision was reopened.

### Correction 6 — REST endpoint: product ID resolution

**Problem:** The Load REST endpoint `anishop/v1/gpa/groups/{id}/attributes` specifies a
`permission_callback` checking `current_user_can( 'edit_product', $product_id )`, but the
route (which carries the **group** ID in `{id}`) did not explain how `$product_id` is
obtained.

**Finding (source review):** Spec §5.3 and §13.1 require the `edit_product` check "for the
current product" but do **not** specify how the product ID reaches the endpoint. The approved
architecture does not settle this detail.

**Fix:** No undocumented source was assumed and the check was **not** weakened. The 3.3 scope
now states that the route carries the group ID, that the product ID is **not** in the route
and must be supplied by the product edit screen (the actual product being edited), that the
exact transport (query parameter vs. request body) is **not settled** by the approved
architecture, and that `$product_id` must be validated (exists + is a product) before the
capability check. A new open item **OI-6** records this as **unresolved — [OWNER-APPROVAL]**
before 3.3 implementation. No different API architecture was introduced.
## 3. Source Documents Consulted

| Source | Used for |
|--------|----------|
| `docs/implementation/phase-3-implementation-roadmap.md` | The document being corrected (all six points). |
| `docs/architecture/phase-2a-architecture-specification.md` | §5.3/§5.4 (Load + REST endpoint), §10.4 (settings storage), §12 (admin menu / CPT `show_in_menu`), §13.1 (nonces/capabilities), §17 (B2-32). |
| `.clinerules/decisions.md` | D28–D43 (active set), D35 (superseded by D43), D39 (Theme Mod), D41/D42/D43. |
| `.clinerules/technical-conventions.md` | House admin-UI pattern (Appearance → Anishop page) — context only, not an approved GPA decision. |
| `reports/2026-11-27-phase-3-0-implementation-roadmap.md` | Prior roadmap report (format reference; not modified). |

No broad project audit was performed; only the documents necessary to resolve the six points
were reviewed.

## 4. Unresolved Clarifications Requiring Owner Approval

Two narrowly scoped clarifications were recorded (not decided) in the roadmap:

| # | Item | Where recorded |
|---|------|----------------|
| OI-5 | Admin UI location / control method for the `table_style` setting | Roadmap §5.1 note + §9.2 (OI-5). |
| OI-6 | Product-ID source for the Load REST endpoint's `permission_callback` | Roadmap §5.3 + §9.2 (OI-6). |

Both are **non-blocking for Phase 3.0** (this documentation task) but **must be resolved by
the owner before the relevant implementation subphase begins** (OI-5 before 3.1's settings
UI; OI-6 before 3.3). No architectural decision was made on either.

## 5. Changed Files

| File | Change |
|------|--------|
| `docs/implementation/phase-3-implementation-roadmap.md` | Modified — six corrections (51 insertions, 12 deletions). |
| `reports/2026-11-27-phase-3-0-roadmap-corrections.md` | This report (created). |

No other files were created, modified, or deleted.

## 6. Verification

- [x] All six requested corrections are present and individually verified in the roadmap.
- [x] Heading structure verified (sections 0–11, subphases 5.1–5.5, all present and ordered).
- [x] `git diff --check` passes with no output (no trailing whitespace, no CRLF issues).
- [x] No trailing whitespace in the modified file (grep verified).
- [x] Only the roadmap and this new report changed (`git diff --name-only` shows only `.md`).
- [x] No PHP, JS, CSS, HTML, or asset files created or modified.
- [x] No database or WordPress data changes.
- [x] No product, attribute, term, fixture, or catalog modifications.
- [x] No changes to approved architecture or owner decisions (D40–D43 unchanged).
- [x] Phase 3.1 (and any Phase 3) implementation was **not** started.
- [x] No merge into `main`.

## 7. Confirmation

- **No GPA implementation was performed.**
- **No Phase 3 implementation work was started.**
- **No production code was changed.**
- **No products, product attributes, terms, or fixtures were changed.**
- **No database changes were made.**
- **No branch was merged into `main`.**
- The corrections are committed on the dedicated branch
  `chore/phase-3-0-roadmap-corrections` and await owner review.