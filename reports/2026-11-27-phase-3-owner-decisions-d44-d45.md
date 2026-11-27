# Report: Phase 3 Owner Decisions D44 & D45 — Final Approvals (Documentation Only)

**Date:** 2026-11-27
**Branch:** `chore/approve-phase-3-open-items`
**Base:** `chore/phase-3-0-roadmap-corrections` (commit `e6fc41610406d041cab90b05c0a87dc8191b9a82`)
**Task type:** Documentation only — no implementation, no runtime data changes.

---

## 1. Objective

Record the owner's explicit approval of the two previously unresolved Phase 3.0 roadmap open
items as final owner decisions:

- **OI-5** — admin UI location and control method for the `table_style` setting → **D44**.
- **OI-6** — product-ID transport for the Load REST endpoint → **D45**.

This task **records decisions only**. It does **not** implement the Customizer control, does
**not** implement or modify the REST endpoint, does **not** start Phase 3.1 (or any Phase 3
subphase), and **authorizes no implementation**. Phase 3.1 begins only after explicit owner
authorization (gate G0).

## 2. Decision D44 — GPA Table Style Settings UI (resolves OI-5)

**Decision:** the GPA `table_style` setting is available at
**Appearance → Customize → Anishop → Group Attributes**.

**Implementational requirements for the future Phase 3.1 task:**
- Use a **select** control.
- Supported values: `default`, `minimal`, `boxed`.
- Store the selected value in the existing Theme Mod `anishop_gpa_settings`.
- Setting key: `table_style`.
- Do **not** create a separate WordPress admin settings page.
- Preserve the existing Theme Mod architecture and single sanitizer.
- Preserve the approved CSS-only frontend architecture.
- `default` retains the native WooCommerce/Blocksy appearance and enqueues **no** GPA
  frontend CSS.

**Rationale:** the approved architecture (spec §10.4, D39) fixed the *storage* of the setting
(Theme Mod `anishop_gpa_settings` → `table_style`) but left the *admin UI location and control
method* open (recorded as OI-5 by the Phase 3.0 roadmap-corrections task). The owner has now
fixed that location to the Blocksy Customizer (Appearance → Customize → Anishop → Group
Attributes) using a select control, which reuses the existing Theme Mod + single-sanitizer
house pattern and introduces no separate admin settings page. This is a refinement of an
implementation detail; it does not change the data model, the Theme Mod architecture, or the
CSS-only frontend.

**Not implemented in this task:** the Customizer control itself (that is Phase 3.1 work).

## 3. Decision D45 — Product-ID Transport for the Load REST Endpoint (resolves OI-6)

**Decision:** keep the existing REST route `anishop/v1/gpa/groups/{id}/attributes`, where
`{id}` continues to identify the GPA group. The product ID is supplied as a **required query
parameter** `product_id`.

Example: `GET /wp-json/anishop/v1/gpa/groups/123/attributes?product_id=456`

**Future implementation requirements:**
1. Require `product_id`.
2. Validate that it is a positive integer.
3. Verify that the referenced product exists.
4. Verify that it is a valid WooCommerce product.
5. Perform the capability check: `current_user_can( 'edit_product', $product_id )`.
6. Validate the product **before** performing the capability check.
7. Preserve the `X-WP-Nonce` requirement.
8. Do not weaken or bypass any existing security requirement.

**Rationale:** the approved architecture (spec §5.3/§13.1) requires the `edit_product`
capability check "for the current product" but did not specify how the product ID reaches the
endpoint (recorded as OI-6 by the Phase 3.0 roadmap-corrections task). The owner has now fixed
the transport to a required `product_id` query parameter, with the product validated (exists +
is a WooCommerce product) **before** the capability check. The route, the `X-WP-Nonce`
requirement, and the `edit_product` check are all preserved; the check is **not** weakened.


## 4. Source Documents Consulted

| Source | Used for |
|--------|----------|
| `docs/implementation/phase-3-implementation-roadmap.md` | The document being updated (OI-5/OI-6, Phase 3.1/3.3 requirements, decision ranges, §9.2/§9.3). |
| `.clinerules/decisions.md` | Active decision set (D1–D43) to which D44/D45 are appended; superseded/obsolete list preserved. |
| `.clinerules/memory-bank.md` | Current-state and decisions summary to keep current. |
| `reports/2026-11-27-phase-3-0-roadmap-corrections.md` | The task that recorded OI-5/OI-6 as unresolved (format reference; not modified). |
| `docs/architecture/phase-2a-architecture-specification.md` | §5.3/§13.1 (Load + REST endpoint), §10.4 (settings storage) — context only; **not modified** (no strictly necessary cross-reference required). |

No broad project audit was performed; only the documents necessary to record the two decisions
were reviewed.

## 5. Changed Files

| File | Change |
|------|--------|
| `.clinerules/decisions.md` | Modified — added D44 and D45 to the active-decisions table; added a header note; added OI-5/OI-6 to the Resolved section. All existing decisions preserved; no superseded decision reactivated. |
| `docs/implementation/phase-3-implementation-roadmap.md` | Modified — OI-5/OI-6 marked RESOLVED (by D44/D45) in §9.2 and §9.3; Phase 3.1 settings-UI note + scope + acceptance + verification updated for D44; Phase 3.3 REST-endpoint scope + acceptance + verification updated for D45; decision ranges updated (D28–D43 → D28–D45, D40–D43 → D40–D45, D1–D43 → D1–D45); new source S16 added. All six previously completed roadmap corrections preserved. |
| `.clinerules/memory-bank.md` | Modified — current-state, architecture, documentation, and decisions sections updated to reflect D44–D45. |
| `reports/2026-11-27-phase-3-owner-decisions-d44-d45.md` | This report (created). |

No other files were created, modified, or deleted. The architecture specification was **not**
modified (no strictly necessary cross-reference required).

## 6. Verification

- [x] D44 and D45 are recorded accurately in `.clinerules/decisions.md` (active table + header note + Resolved section).
- [x] OI-5 and OI-6 are marked **RESOLVED** (by D44 and D45 respectively) in the roadmap §9.2 and §9.3.
- [x] D39 and D40–D43 remain unchanged (verified by diff — no edits to those rows).
- [x] All six Phase 3.0 roadmap corrections remain intact (Corrections 1, 3, 4, 5 untouched; Corrections 2 and 6 updated to their resolved status as required).
- [x] Decision-range references updated consistently (D28–D45, D40–D45, D1–D45) in the roadmap and memory-bank.
- [x] `git diff --check` passes with no output (no trailing whitespace, no CRLF issues).
- [x] No trailing whitespace in the modified files (grep verified).
- [x] Only the authorized documentation files changed (`git diff --name-only` shows only `.md`).
- [x] No PHP, JS, CSS, HTML, or asset files created or modified.
- [x] No WordPress or WooCommerce runtime changes.
- [x] No database changes.
- [x] No product, attribute, term, or fixture modifications.
- [x] No Customizer control implemented.
- [x] No REST endpoint implemented or modified.
- [x] Phase 3.1 (and any Phase 3 subphase) was **not** started.
- [x] No merge into `main`.

## 7. Confirmation

- **No GPA implementation was performed.**
- **No Phase 3 implementation work was started.**
- **No production code was changed.**
- **No products, product attributes, terms, or fixtures were changed.**
- **No database changes were made.**
- **No branch was merged into `main`.**
- The decisions are committed on the dedicated branch `chore/approve-phase-3-open-items` and
  await owner review.

## 8. Next (owner-driven)

1. Owner review of `chore/approve-phase-3-open-items`.
2. If approved, merge or otherwise proceed per owner instructions.
3. Phase 3.1 (settings UI per D44) and Phase 3.3 (REST endpoint per D45) each begin only after
   their own explicit owner authorization.
**Not implemented in this task:** the REST endpoint itself (that is Phase 3.3 work).