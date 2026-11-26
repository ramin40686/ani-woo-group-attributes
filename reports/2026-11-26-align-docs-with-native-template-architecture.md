# Align Active Documentation with the Native Attribute Template Architecture

## 1. Task Title
Align active documentation with the completed Phase 2A.3 native WooCommerce Global Attribute template architecture (documentation-only chore).

## 2. Date
2026-11-26

## 3. Executive Summary
All active project documentation was audited and updated to reflect the Phase 2A.3
native-template model (decisions D28–D39): GPA groups are templates of Global Attribute
definitions, loading a group inserts native WooCommerce attribute rows, the product's
native `_product_attributes` is the single source of truth, all attributes render through
the standard WooCommerce/Blocksy Additional Information tab, and no GPA-specific product
metadata or core group-category taxonomy exists. Obsolete references (old product meta
keys, `ani_gpa_group_cat` as a core component, View Attribute button, Blocksy
custom-layer rendering, A1/B1 direction, old admin controls) were removed from active
docs or explicitly marked as superseded/unresolved. Phase statuses were normalized to
2A.1/2A.2/2A.3 COMPLETE, 2B NOT STARTED. No code, data, database, or runtime changes were
made; no merge was performed; Phase 2B was not started.

## 4. Task Scope
- Audit active documentation for references to the superseded Phase 2A/2A.1/2A.2
  value-selection architecture.
- Update active docs to the Phase 2A.3 native-template model.
- Normalize documented phase statuses.
- Preserve historical reports/research verbatim; leave owner questions Q1–Q4 unresolved.
- Produce this report; commit and push the chore branch.

## 5. Authorized Work
The project owner authorized a documentation-only alignment task on a dedicated chore
branch: update active docs to the native-template architecture, do not change code, data,
or the actual project phase status, do not merge, and do not start Phase 2B or
implementation.

## 6. Files Created
- `reports/2026-11-26-align-docs-with-native-template-architecture.md` — this report.

## 7. Files Modified
- `.clinerules/memory-bank.md` — data model section rewritten to the native-template model
  (CPT + `_ani_gpa_group_attributes` group meta + Theme Mod `anishop_gpa_settings`; no GPA
  product meta; no core `ani_gpa_group_cat` taxonomy); admin-UI convention notes the GPA
  top-level-menu exception (D35); Current State updated (2A.1/2A.2/2A.3 COMPLETE, 2B NOT
  STARTED, native-template architecture, repository state, next step); Decisions section
  updated to D1–D39 with the superseded set (D9/D10/D14/D16/D17/D18/D22/D24/D25/D26/D27)
  and the new D28–D39 set.
- `.clinerules/project-context.md` — Ultimate Objective rewritten to the template model;
  Mandatory Functionality items 1–4 rewritten (group template management, no core group
  categories, Product Data → Attributes loading, general settings with no View Attribute
  button); Phase table statuses updated (2 = IN PROGRESS with 2A.1/2A.2/2A.3 COMPLETE and
  2B NOT STARTED; Phase 4 test list updated to native loading/rendering).
- `.clinerules/project-rules.md` — data-storage rule now names only the CPT
  `ani_gpa_group` (the `ani_gpa_group_cat` taxonomy is explicitly not part of the core
  architecture — Q1); the obsolete product-meta rule (`_ani_gpa_assigned_groups` /
  `_ani_gpa_selected_values`) replaced with the native-template data model (group meta
  `_ani_gpa_group_attributes`, Theme Mod `anishop_gpa_settings`, no GPA product meta).
- `README.md` — Current Status updated (2A.1/2A.2/2A.3 complete, 2B and 3 NOT STARTED);
  Architecture section rewritten to the native-template model (template CPT, native row
  insertion, native rendering, no product meta, no core category taxonomy, top-level
  admin menu, deferred View Attribute).
- `docs/PROJECT-ROADMAP.md` — §1 Mandatory Functionality items 1–4 rewritten to the
  native-template model; §3 Phase 2 status line and sub-phase list updated (2A.1/2A.2/
  2A.3 COMPLETE with the 2A.3 entry added; 2B NOT STARTED); Phase 2 deliverables updated
  (component responsibilities + data model); Phase 4 required-tests list updated (group
  template CRUD, Product Data → Attributes loading, Additional Information tab rendering,
  no View Attribute test).
- `docs/testing/test-fixtures.md` — group/category registry note updated (CPT not yet
  registered; `ani_gpa_group_cat` not part of the core architecture — Q1/B2-33);
  isolation-rules summary updated (group categories only if Q1 is resolved).

## 8. Files Deleted
None.

## 9. Detailed Changes

### Data-model alignment (all files)
The authoritative Phase 2A.3 data model is now stated consistently everywhere:
- CPT `ani_gpa_group` — group templates of Global Attribute definitions.
- Group meta `_ani_gpa_group_attributes` — serialized array of ordered `pa_{slug}`
  references.
- Settings: Theme Mod `anishop_gpa_settings` (`table_style`: default / minimal / boxed).
- No GPA-specific product metadata; the product's native `_product_attributes` is the
  single source of truth (D30).
- No `ani_gpa_group_cat` taxonomy in the core — group categories remain an unresolved
  owner decision (Q1/B2-33).

### Obsolete references removed or marked
- `_ani_gpa_assigned_groups` / `_ani_gpa_selected_values` — removed from
  `project-rules.md` (replaced by the native-template data model); remaining mentions
  exist only in the Phase 2A.3 specification (historical context), `decisions.md`
  (superseded/obsolete decision records D30/D24/D26), and the Phase 1 historical
  `docs/woocommerce-attributes-dev-notes.md` (preserved verbatim).
- `ani_gpa_group_cat` as a core component — removed from `memory-bank.md`,
  `project-rules.md`, `README.md`, `PROJECT-ROADMAP.md`, `test-fixtures.md`; remaining
  mentions are explicit "not part of the core / unresolved Q1" notes, the spec's
  historical context, and `decisions.md` records.
- View Attribute button — removed as a mandatory feature from `project-context.md` and
  `PROJECT-ROADMAP.md` (marked deferred / out of scope per D36); remaining mentions are
  the deferral notes themselves, `decisions.md` records (D36, superseded D10/D18), the
  spec, and the Phase 1 historical dev notes.
- Blocksy custom-layer rendering — removed from `README.md` and `PROJECT-ROADMAP.md`
  (replaced by native Additional Information tab rendering per D33); remaining mentions
  are `decisions.md` (superseded D16/D22), the spec, and `technical-conventions.md`
  (describing existing modules' hooks, not GPA).
- A1/B1 architecture direction — removed from `memory-bank.md` Current State (replaced by
  the approved native-template model); remaining mentions are `decisions.md` (superseded
  D22), the spec, and the Phase 1.1 historical task list in `PROJECT-ROADMAP.md`
  (record of what Phase 1.1 did at the time).
- Old admin controls ("Manage Attribute Groups" + Load, "Load Group Categories",
  per-product group sorting, value selection) — removed from `project-context.md` and
  `PROJECT-ROADMAP.md` mandatory features and Phase 4 tests; replaced by the Product
  Data → Attributes group picker + Load model (D38) and native value/order management.
- Admin UI location — `memory-bank.md` notes the GPA exception: independent top-level
  menu immediately after Products (D35), not an Appearance tab.

### Phase status normalization
All active docs now state: Phase 0, 0.1, 1, 1.1, 1.1.1, 2A.1, 2A.2, 2A.3 COMPLETE;
Phase 2B NOT STARTED (awaiting explicit owner authorization); Phases 3/4/5 NOT STARTED.

### Preserved unchanged (historical)
- `reports/*` — all historical reports preserved verbatim.
- `docs/research/*` — Phase 1 research docs preserved verbatim.
- `docs/references/*` — reference-plugin documentation preserved verbatim.
- `docs/woocommerce-attributes-dev-notes.md` — Phase 1 historical dev notes preserved
  verbatim (its obsolete references are Phase 1-era context).
- `docs/architecture/phase-2a-architecture-specification.md` — the authoritative spec,
  already updated in Phase 2A.3; not modified by this task.
- `.clinerules/decisions.md` — already contains D28–D39 and the superseded table; not
  modified by this task.
- Owner questions Q1–Q4 remain unresolved in all docs.

## 10. Tests and Verification

### Static documentation audit (grep-based)
Re-ran obsolete-term searches across all active docs (excluding `reports/`,
`docs/research/`, `docs/references/`):
- `ani_gpa_group_cat` — remaining hits: `test-fixtures.md` (explicit "not part of the
  core" note), spec (historical context), `project-rules.md` (explicit "not part of the
  core" note), `memory-bank.md` (explicit "no taxonomy in the core" note). All
  legitimate.
- `_ani_gpa_assigned_groups` / `_ani_gpa_selected_values` — remaining hits: Phase 1
  historical dev notes, spec (historical context), `decisions.md` (superseded records).
  All legitimate.
- `View Attribute` — remaining hits: `decisions.md` (D36 + superseded D10/D18),
  `memory-bank.md` (deferral note), `project-context.md` (deferral note), spec,
  `PROJECT-ROADMAP.md` (deferral note), Phase 1 historical dev notes. All legitimate.
- `A1` / `B1` — remaining hits: `decisions.md` (superseded D22), `memory-bank.md`
  (supersession note), spec, `PROJECT-ROADMAP.md` (Phase 1.1 historical task list). All
  legitimate.
- `Load Group Categories` — remaining hits: `decisions.md` (open item), spec. All
  legitimate.
- `Manage Attribute Groups` — no remaining hits in active docs.
- `custom layer` / `custom-layer` — remaining hits: `decisions.md` (superseded D16/D22),
  `technical-conventions.md` (existing modules' hooks), spec. All legitimate.
- `selected values` — remaining hits: `decisions.md` (superseded D26), Phase 1
  historical dev notes. All legitimate.
- `group categories` — remaining hits: `memory-bank.md` (unresolved Q1 note), spec,
  `test-fixtures.md` (unresolved Q1 note). All legitimate.

### Contradiction re-check of key active docs
Re-read `docs/PROJECT-ROADMAP.md`, `README.md`, `.clinerules/project-rules.md`,
`.clinerules/memory-bank.md`, `.clinerules/project-context.md`, and
`docs/testing/test-fixtures.md` after editing: data model, admin UI location, frontend
rendering, phase statuses, and open questions are now consistent across all six.

### Diff review
- `git status --porcelain` — exactly the 6 authorized modified files (plus the new
  report at commit time); no unexpected files.
- `git --no-pager diff` — full diff reviewed line by line; only documentation changes;
  no secrets, credentials, temporary files, or unrelated modifications.

### Git verification
- Branch `chore/align-docs-with-native-template-architecture` created from verified
  `phase-2a-3-native-attribute-template-redesign` at `d531d2e` (clean tree).
- `main` unchanged (verified via `git rev-parse main` before and after).
- No merge performed.

## 11. Verified Results
- All 6 active docs updated to the native-template model — [STATIC CODE EVIDENCE] (full
  diff reviewed).
- Obsolete-term re-audit clean: every remaining mention is historical, explicitly
  superseded, or an unresolved owner question — [STATIC CODE EVIDENCE] (grep output).
- Phase statuses consistent across `PROJECT-ROADMAP.md`, `README.md`,
  `memory-bank.md`, `project-context.md` — [STATIC CODE EVIDENCE] (grep output).
- Working tree contains only the authorized documentation changes — [STATIC CODE
  EVIDENCE] (`git status --porcelain` + `git diff --stat`: 6 files, 69 insertions,
  62 deletions).
- `main` unchanged; no merge performed — [STATIC CODE EVIDENCE] (`git rev-parse`).

## 12. Not Verified / Limitations
- No runtime verification was performed or needed: this task changed no code, no data,
  no database state, and no runtime behavior.
- The live WordPress installation was not touched.

## 13. Risks and Open Questions
- Owner questions remain unresolved (intentionally):
  - **Q1 (B2-33):** Group categories — in scope or not? (No core taxonomy exists until
    resolved.)
  - **Q2 (B2-34):** Group status policy — confirm "only `publish` groups are loadable".
  - **Q3 (B2-35):** Table-style visual design — finalize `minimal` / `boxed` CSS.
  - **Q4 (B2-36):** Admin menu fractional offset — confirm after the Phase 2B collision
    check.
- No other risks identified; documentation-only change with no runtime surface.

## 14. Scope Compliance
Only the authorized documentation alignment was performed. No code, data, database,
theme, or plugin changes. No merge into `main`. No Phase 2B or implementation work
started. Historical reports/research/references preserved verbatim. Owner questions
Q1–Q4 left unresolved.

## 15. Git Information
- **Branch:** `chore/align-docs-with-native-template-architecture`
- **Base branch:** `phase-2a-3-native-attribute-template-redesign` (verified at `d531d2e`, clean tree)
- **Base commit:** `d531d2e`
- **Final commit hash:** `a1c37b9` (work commit: docs + this report); this SHA was
  recorded in the follow-up commit `d858e03`
- **Remote push status:** pushed to `origin/chore/align-docs-with-native-template-architecture`
  and verified via `git ls-remote` — remote branch at `d858e039bba32b56a1e9c5d046fdca74b1dcb0a9`
  (matches local HEAD at verification time)

## 16. Phase / Task Status
Task complete. Project phase status unchanged by this task: Phase 2A.1, 2A.2, 2A.3
COMPLETE; **Phase 2B NOT STARTED — awaiting explicit owner authorization**; Phases 3/4/5
NOT STARTED.

## 17. Recommended Next Step
Owner review of the aligned documentation, then explicit authorization to begin Phase 2B
(runtime verification of the [2B-VERIFY] items in the Phase 2A.3 specification §17) on a
new branch `phase-2b-runtime-verification`.

## 18. Stop Confirmation
Work has stopped after committing and pushing this chore branch. Awaiting the project
owner's next instruction. No further task, phase, or merge was started.


