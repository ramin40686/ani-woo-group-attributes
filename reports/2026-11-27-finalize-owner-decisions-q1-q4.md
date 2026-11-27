# Report: Finalize Owner Decisions Q1–Q4 — Documentation Only

**Date:** 2026-11-27
**Branch:** `chore/finalize-owner-decisions-q1-q4`
**Base:** `chore/phase-2b-evidence-gap-followup` (commit `d5279f2`)
**Task type:** Documentation only — no implementation, no runtime data changes.

---

## 1. Objective

Record the owner's final decisions for Q1–Q4 in the authoritative project documentation.
The four open questions (Q1–Q4) that were recorded as unresolved after Phase 2A.3 are now
resolved by owner decisions D40–D43.

## 2. Decisions Recorded

| # | Question | Decision | New Decision ID |
|---|----------|----------|-----------------|
| Q1 | Group Categories | No group-category taxonomy in the initial GPA version. Groups remain a flat collection. No `ani_gpa_group_cat`. | **D40** |
| Q2 | Group Publication Status Policy | Only Published groups are eligible for loading into product attributes. Draft groups remain manageable in GPA admin but are not loadable. | **D41** |
| Q3 | Frontend Attribute Table Styling | Retain all three styles (`default` / `minimal` / `boxed`). CSS-only presentation confirmed. No JS, no separate table, no renderer modification. | **D42** |
| Q4 | GPA Administration Menu Placement | GPA is a **submenu** under WooCommerce Products, immediately after Attributes. Label: **گروه ویژگی‌های آنی**. No separate top-level menu. | **D43** |

## 3. Superseded Decisions

| Old Decision | Status | Superseded By | Reason |
|-------------|--------|---------------|--------|
| D35 (Independent top-level admin menu) | **Superseded** | D43 | Owner explicitly prefers submenu under Products over independent top-level menu. |
| D24 (Group status policy — Modified) | **Superseded** | D41 | Owner confirmed "only `publish` groups are loadable" policy. |

## 4. Changed Files

| File | Change |
|------|--------|
| `.clinerules/decisions.md` | Added D40–D43 to Active decisions; moved D35 to Superseded table; updated D24 status to Superseded; updated Undecided/Open section; added Resolved section; updated header note. |
| `docs/architecture/phase-2a-architecture-specification.md` | Updated §2.3 (CPT `show_in_menu`, group status), §2.4 (group categories resolved), §5.3 (group select D41), §10.3 (CSS-only confirmed D42), §11.1 (admin menu row), §12 (complete rewrite for submenu placement), §16 (admin menu test), §17.1 (B2-2/B2-3), §17.7 (B2-33–B2-36 resolved), §18.1 (Q1–Q4 resolved), §18.2 (item 4 updated). |
| `docs/PROJECT-ROADMAP.md` | Updated §1 items 1–2 (menu placement, group categories); Phase 2 deliverables (category taxonomy note); Phase 4 tests (group categories note). |
| `.clinerules/project-context.md` | Updated Mandatory Functionality items 1–2. |
| `.clinerules/memory-bank.md` | Updated Data Model (group categories), Conventions (admin menu), Current State (decisions D28–D43), Decisions section. |
| `.clinerules/project-rules.md` | Updated group category notes (2 locations). |
| `docs/testing/test-fixtures.md` | Updated group category notes (2 locations). |
| `reports/2026-11-27-finalize-owner-decisions-q1-q4.md` | This report (created). |

## 5. Checks Performed

- **Consistency search:** `grep` for "independent top-level", "UNRESOLVED Q1–Q4", "open item B2-33–36", "D35", "D24" across all active documentation (excluding `reports/`, `temp/`, `snapshots/`, `docs/research/`, `docs/references/`). All remaining references are in the context of explaining the supersession (correct).
- **No contradictory active documentation remains.** All active references to the four decision areas now point to D40–D43.
- **No implementation code changed.** No PHP, JavaScript, CSS, or database files were modified.
- **No runtime data changed.** No WordPress settings, WooCommerce products, Global Attributes, or test fixtures were modified.
- **No merge into `main`.** The branch is committed and pushed only.

## 6. Verification

- [x] All four owner decisions (Q1–Q4) are recorded as D40–D43.
- [x] The old top-level menu decision (D35) is clearly superseded by D43.
- [x] The exact menu label is `گروه ویژگی‌های آنی`.
- [x] The menu hierarchy is documented as Products → Attributes → GPA submenu (immediately after Attributes).
- [x] No contradictory active documentation remains.
- [x] No implementation or runtime data was changed.
- [x] All changes and the completion report are committed and pushed.

## 7. Confirmation

- **No GPA implementation was performed.**
- **No Phase 3 work was started.**
- **No production code was changed.**
- **No products, product attributes, terms, or fixtures were changed.**
- **No database changes were made.**
- **No branch was merged into `main`.**

## 8. Next Steps

- Owner review of the updated documentation.
- Explicit owner authorization for Phase 2B runtime verification (spec §17 checklist).
- Phase 2B will verify the WooCommerce Products menu slug, Attributes submenu slug, and GPA submenu placement at runtime (B2-2, B2-3).
- Visual specifications for `minimal` and `boxed` table styles to be defined before final CSS implementation (D42).
