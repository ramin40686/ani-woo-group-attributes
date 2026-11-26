# Phase 2B Readiness Audit — GPA Native-Template Architecture

## 1. Task Title
Read-only Phase 2B readiness audit: verify that the approved Phase 2A.3 native-template
architecture, its documentation, the `[2B-VERIFY]` checklist, the test fixtures, and the
safety boundaries are ready for Phase 2B runtime verification.

## 2. Date
2026-11-26

## 3. Executive Summary
This audit is strictly read-only. It reviews the authoritative architecture specification,
the decision records, the project rules, the roadmap, the test-fixture registry, the project
context, the memory bank, and the three most recent task reports, and assesses the project's
readiness for Phase 2B (runtime verification of the `[2B-VERIFY]` items in spec §17).

Findings:
- **Architecture: consistent.** All active documents (spec, decisions, project rules,
  memory bank, project context, roadmap, fixture registry, README) describe the same
  native-template model (D28–D39): groups = templates of Global Attribute definitions;
  Load inserts native attribute rows; the product's native `_product_attributes` is the
  single source of truth; all attributes render through the native Additional Information
  tab; no GPA product metadata; no GPA frontend renderer.
- **Checklist: complete and actionable.** Spec §17 defines 36 items (B2-1 … B2-36):
  **14 require Phase 2B runtime verification** (11 pure `[2B-VERIFY]` + 3
  `[VERIFIED]`-static/`[2B-VERIFY]`-runtime), 18 are `[CONFIRMED]` architectural
  requirements (Phase 3 implementation / Phase 4 verification), and 4 are `[OPEN]` owner
  decisions (B2-33 … B2-36 = Q1–Q4). Every runtime item has a static source-evidence base
  (spec §19) and a defined verification method.
- **Fixtures: ready.** Both permanent fixtures (13143 simple, 17028 variable with 16
  variations) have byte-exact backups present in `backups/gpa-test-fixtures/` (presence
  verified in this audit) and restoration-verified hidden copies (17601, 17602).
- **Q1–Q4: none blocking.** Q4 (menu offset) is resolved by Phase 2B's own collision
  check; Q2 is a confirmation of a policy the spec already assumes; Q1 and Q3 can be
  deferred to Phase 3 scoping.
- **One governance prerequisite identified (new finding, G1):** `main` (`2b4b090`) does
  **not** contain the authoritative spec or the D28–D39 decisions — all Phase 1/2A
  documentation exists only on unmerged phase branches (21 commits ahead on
  `chore/final-documentation-corrections`). Before Phase 2B starts, the owner must decide
  the base branch (merge completed phases into `main` first, or explicitly authorize
  branching from the latest documentation state).

**Conclusion: Phase 2B is READY to begin, subject to (a) explicit owner authorization and
(b) the owner's decision on the base branch (governance prerequisite G1).** No
implementation blockers were identified.

## 4. Task Scope
- Review the mandatory documents (spec, decisions, project rules, roadmap, fixture
  registry, project context, memory bank, the three most recent reports).
- Assess architecture consistency across all active documents.
- Enumerate the complete `[2B-VERIFY]` checklist (B2-1 … B2-36) with readiness notes.
- Review fixture readiness and safety boundaries.
- Assess the impact of Q1–Q4 on Phase 2B (assess, do not resolve).
- Review WooCommerce/Blocksy/persian-woocommerce compatibility prerequisites.
- Define the authorized / prohibited scope for Phase 2B.
- Produce a final readiness assessment.
- Create this report; commit and push the audit branch.

## 5. Authorized Work
The project owner authorized a strictly read-only audit on a dedicated branch
`chore/phase-2b-readiness-audit` created from the verified HEAD of
`chore/final-documentation-corrections`: review documents, assess readiness, produce the
report, commit, and push. No application code, no WordPress/WooCommerce/database/fixture
changes, no Phase 2B runtime verification, no merge into `main`.

## 6. Starting Git State (verified)
| Item | Value | Verified |
|------|-------|----------|
| Starting branch | `chore/final-documentation-corrections` | `git rev-parse` |
| Starting HEAD | `ff9062835559c595b85e7c3fa946d28ab9436a89` | matches the required baseline |
| Working tree at start | clean | `git status --porcelain` (empty) |
| `main` (local = remote) | `2b4b0902fbaee70519df20f1bcc63723e1d591ba` | `git rev-parse main` + `git ls-remote origin main` |
| Audit branch created | `chore/phase-2b-readiness-audit` (from `ff90628`) | `git branch --show-current` |

## 7. Documents Reviewed
| Document | Role |
|----------|------|
| `docs/architecture/phase-2a-architecture-specification.md` (1423 lines) | Authoritative Phase 2A.3 design; the contract for Phase 2B/3 |
| `.clinerules/decisions.md` | Decision records D1–D39 (active + superseded/obsolete) |
| `.clinerules/project-rules.md` | Project-wide execution rules + Test Environment Policy |
| `docs/PROJECT-ROADMAP.md` | Phases, mandatory functionality, definition of done |
| `docs/testing/test-fixtures.md` | Permanent fixture registry (D23) |
| `.clinerules/project-context.md` | Project context + mandatory functionality |
| `.clinerules/memory-bank.md` | Durable project state |
| `reports/2026-11-25-phase-2a-3-native-attribute-template-redesign.md` | Phase 2A.3 completion report (supersession matrix) |
| `reports/2026-11-26-align-docs-with-native-template-architecture.md` | Documentation alignment chore report |
| `reports/2026-11-26-final-documentation-corrections.md` | Final documentation corrections report |

## 8. Architecture Consistency

### 8.1 Cross-document consistency matrix

| Concern | Authoritative position (spec + D28–D39) | Consistent across active docs? |
|---------|------------------------------------------|--------------------------------|
| Group concept | Template of Global Attribute definitions (ordered `pa_{slug}` references); Global-only; no terms/values stored | Yes — spec §2, D28, project-context §1.1, roadmap §1.1, memory-bank data model |
| Group storage | CPT `ani_gpa_group` + meta `_ani_gpa_group_attributes` (serialized ordered `pa_{slug}` array) | Yes — spec §3.1, project-rules data-storage rule, memory-bank, test-fixtures registry note |
| Product data | No GPA product metadata; native `_product_attributes` is the single source of truth | Yes — spec §3.4/§3.5, D30, project-rules, memory-bank, project-context |
| Load operation | Inserts native rows via the native `woocommerce_add_attribute` mechanism; no parallel structure | Yes — spec §5.4, D29, project-context §1.3, roadmap §1.3 |
| Group categories | Not in the core; `ani_gpa_group_cat` is not a core taxonomy (Q1 unresolved) | Yes — spec §2.4, project-rules (explicit note), memory-bank, test-fixtures, roadmap §1.2 |
| Frontend rendering | Native Additional Information tab only; no GPA renderer/tables/headings/custom layer/location setting | Yes — spec §9, D33, project-context, roadmap, README |
| Table style | `default`/`minimal`/`boxed` applied uniformly; CSS-only recommended; Theme Mod `anishop_gpa_settings` | Yes — spec §10, D34/D39, project-context §1.4, memory-bank |
| Admin UI | Independent top-level menu immediately after Products (runtime-computed position); product controls inside Product Data → Attributes | Yes — spec §5/§12, D35/D37/D38, memory-bank (GPA exception note) |
| View Attribute button | Deferred / out of scope | Yes — D36, project-context §1.4, roadmap (deferral note) |
| Phase status | 0, 0.1, 1, 1.1, 1.1.1, 2A.1, 2A.2, 2A.3 COMPLETE; 2B NOT STARTED (awaiting explicit owner authorization); 3/4/5 NOT STARTED | Yes — roadmap §3, project-context phase table, memory-bank Current State, README |
| Open questions | Q1–Q4 unresolved | Yes — spec §18.1, decisions Undecided section, memory-bank, both 2026-11-26 reports |

### 8.2 Decision-record integrity
- **Active:** D1–D8, D11, D12, D13, D15, D19, D20, D21, D23, D28–D39.
- **Superseded (preserved verbatim):** D9, D10, D14, D16, D17, D18, D22, D27.
- **Obsolete (preserved verbatim):** D25, D26.
- **Modified (reassessed, open item B2-34):** D24.
- The Phase 2A.3 report §7 supersession matrix covers every affected previous decision;
  the Phase 2A.2 corrections applicability assessment (§8 of that report) confirms no
  generally-applicable security/integrity safeguard was discarded without justification
  (safe unserialization re-scoped to group meta, spec §13.3; re-validation principle
  preserved in the reference-integrity contract, spec §3.3).

### 8.3 Stale-reference status
- The 5 out-of-scope stale active references discovered in the Phase 2A.3 QA (report
  §17.1: project-rules lines 28–29, memory-bank lines 24–26/47–51, roadmap Phase 4 test
  list) were **fixed** by the 2026-11-26 documentation alignment chore.
- The 3 small issues found during the GitHub review of that chore (report Git
  Information, memory-bank "Next" line, roadmap Phase 4 catalog-visibility test) were
  **fixed** by the 2026-11-26 final documentation corrections.
- Remaining mentions of obsolete terms (`_ani_gpa_assigned_groups`,
  `_ani_gpa_selected_values`, `ani_gpa_group_cat`, View Attribute, A1/B1, custom layer)
  exist only in: historical reports, Phase 1 research/dev notes, the spec's historical
  context, and the `decisions.md` superseded/obsolete records — all legitimate.
- **No known stale active references remain.** [STATIC CODE EVIDENCE — re-verified in
  this audit by full reading of all active documents]

### 8.4 Consistency verdict
The documentation set is internally consistent and matches the approved native-template
model. No contradiction was found between the specification, the decision records, the
rules, the roadmap, the fixture registry, and the README.

## 9. Complete `[2B-VERIFY]` Checklist (spec §17, B2-1 … B2-36)

**Legend:** `[2B-VERIFY]` = requires Phase 2B runtime verification before Phase 3 ·
`[VERIFIED]` = statically verified against installed source (re-confirm at runtime) ·
`[CONFIRMED]` = confirmed architectural requirement (Phase 3 implementation, Phase 4
verification) · `[OPEN]` = open owner decision.

### 9.1 GPA group CPT and admin menu
| # | Item | Type | Phase 2B readiness note |
|---|------|------|--------------------------|
| B2-1 | Register CPT `ani_gpa_group` per §2.3 (`show_in_menu => false`, `supports => ['title']`, `capability_type => post` + `map_meta_cap`, `rewrite => false`) | [CONFIRMED] | Phase 3 implementation; standard WP API, no runtime risk |
| B2-2 | Add the GPA top-level admin menu immediately after Products via `add_menu_page` with a runtime-computed float position (§12.2); confirm the live sidebar order | [2B-VERIFY] | Phase 2B portion: read-only inspection of the live `$menu` global (Products position + collision check). Menu registration itself is Phase 3; final visual confirmation is Phase 4 |
| B2-3 | Confirm the Products menu position at runtime (read the `$menu` global) and that the chosen offset places GPA directly after it with no collision | [2B-VERIFY] | Read-only `wp eval` listing top-level menu positions/labels; feeds the Q4 (B2-36) owner decision |

### 9.2 Group editor
| # | Item | Type | Phase 2B readiness note |
|---|------|------|--------------------------|
| B2-4 | Group list table (title, attribute count, status, date) with pagination | [CONFIRMED] | Phase 3 |
| B2-5 | Group edit: Global Attribute picker sourced from `wc_get_attribute_taxonomies()` (labels + `pa_{slug}` references); add/remove; drag-and-drop ordering | [CONFIRMED] | Phase 3; registry APIs statically verified (spec §19.1) |
| B2-6 | Group save handler (`save_post_ani_gpa_group`): nonce + `edit_post` cap; sanitize + validate references (§3.3); persist ordered `_ani_gpa_group_attributes` | [CONFIRMED] | Phase 3 |
| B2-7 | Reference validation: `taxonomy_exists( $ref )` + `wc_attribute_taxonomy_id_by_name( $ref ) > 0`; reject/flag invalid; de-duplicate | [VERIFIED] (APIs) / [2B-VERIFY] (runtime) | Runtime re-confirmation against the live registry |
| B2-8 | Stale-reference flagging in the group editor (attribute renamed/deleted since last save) | [CONFIRMED] | Phase 3 |
| B2-9 | Empty group (no attributes) is a valid save state | [CONFIRMED] | Phase 3 |

### 9.3 Product Data → Attributes integration
| # | Item | Type | Phase 2B readiness note |
|---|------|------|--------------------------|
| B2-10 | Render the GPA section via `woocommerce_product_options_attributes` (inside the Attributes tab, after the native rows + Save button) | [VERIFIED] (hook location) | Statically verified (html-product-data-attributes.php:69); runtime re-confirmation is low-cost |
| B2-11 | UI-only "Create group for this product" toggle (no persisted flag) | [CONFIRMED] | Phase 3 |
| B2-12 | Group `<select>` listing `publish` groups only | [CONFIRMED] | Assumes the Q2 policy (see §11) |
| B2-13 | Load REST endpoint (`anishop/v1/gpa/groups/{id}/attributes`): nonce + `edit_product` cap; returns the ordered `pa_{slug}` list (validated) | [CONFIRMED] | Phase 3 |
| B2-14 | Load row insertion reuses the native `woocommerce_add_attribute` AJAX action (reusing WC's `add-attribute` nonce); confirm the exact `i` index computation for sequential multi-row insertion and select2 re-initialization against live WC 11.3.1 | [2B-VERIFY] | Core Phase 2B item; static evidence: meta-boxes-product.js:692, class-wc-ajax.php:779 |
| B2-15 | Duplicate handling: skip a group attribute whose `pa_{slug}` row already exists (by `data-taxonomy`); report skipped attributes | [CONFIRMED] | Phase 3 |
| B2-16 | Stale-reference handling on Load: skip + report | [CONFIRMED] | Phase 3 |
| B2-17 | Multiple-group loading: each Load appends its attributes (subject to duplicate handling) | [CONFIRMED] | Phase 3 |
| B2-18 | Confirm loaded rows are structurally identical to native rows (markup, hidden `attribute_names[i]` / `attribute_position[i]`, `attribute_values[i][]`) | [2B-VERIFY] | Compare native row HTML (html-product-attribute*.php) with the `woocommerce_add_attribute` action output |

### 9.4 Native editing and save behavior
| # | Item | Type | Phase 2B readiness note |
|---|------|------|--------------------------|
| B2-19 | Confirm the native save discards attribute rows with no values (`prepare_attributes` `empty( $options )` skip) and that loaded rows persist only after values are selected | [VERIFIED] (source) / [2B-VERIFY] (runtime) | Fixture-based experiment: add a row, save without values, inspect `_product_attributes` |
| B2-20 | Confirm the "Save attributes" button enable/disable behavior when a loaded row has no value (whether GPA-inserted rows trigger `maybe_disable_save_button`); the architecture does **not** depend on this | [2B-VERIFY] | Informational; static evidence: meta-boxes.js:5,33 |
| B2-21 | Confirm native reordering, remove, and manual add (Global + Custom) coexist with loaded attributes without GPA interference | [2B-VERIFY] | Fixture-based experiment on 13143/17028 |
| B2-22 | Confirm the final displayed attribute order follows the product's native attribute order | [CONFIRMED] | Phase 4 |

### 9.5 Frontend and uniform table styles
| # | Item | Type | Phase 2B readiness note |
|---|------|------|--------------------------|
| B2-23 | Confirm all attributes (GPA-loaded, manual Global, Custom) render in the native Additional Information tab via the verified render chain (§9.3) | [VERIFIED] (chain) / [2B-VERIFY] (runtime) | Frontend inspection of fixture product pages |
| B2-24 | Confirm the CSS-only table-style mechanism: body-class scoping + conditional enqueue; `default` enqueues nothing; `minimal` / `boxed` apply uniformly to all rows | [2B-VERIFY] | Verify the mechanism against live markup (final visual design is Q3) |
| B2-25 | Confirm no duplicate tables, no group headings, no GPA-specific frontend output | [CONFIRMED] | Phase 4 |
| B2-26 | Confirm the exact CSS selectors against live Blocksy 2.1.62 + WC 11.3.1 markup (table inside the Blocksy Additional Information tab wrapper) | [2B-VERIFY] | `inspect_design` / DOM inspection of a live product page |

### 9.6 Compatibility and regression
| # | Item | Type | Phase 2B readiness note |
|---|------|------|--------------------------|
| B2-27 | `persian-woocommerce` runtime compatibility (D19): the CSS-only style + native render coexist correctly | [2B-VERIFY] | Required by D19; must not be claimed from architectural assumption |
| B2-28 | WooCommerce 11.3.1 re-confirmation of the depended-on behaviors (`woocommerce_add_attribute`, `prepare_attributes`, `shop_attributes` template) | [2B-VERIFY] | Re-verify installed version + the three depended-on behaviors |
| B2-29 | Blocksy Free compatibility (native Additional Information tab rendering) | [2B-VERIFY] | Re-verify the render chain on live Blocksy 2.1.62 |
| B2-30 | Regression: all 14 existing child-theme modules remain functional; no duplicate hooks / parallel save handlers | [CONFIRMED] | Phase 4 regression suite |
| B2-31 | Security: nonce/capability enforcement (group save, Load REST, settings save); output escaping; safe unserialization of `_ani_gpa_group_attributes` | [CONFIRMED] | Phase 3/4 |
| B2-32 | RTL + Persian presentation (admin + frontend); real-mobile verification (iPhone 12 Pro) | [CONFIRMED] | Phase 4 (real-mobile mandatory; CDP is not sufficient) |

### 9.7 Open implementation decisions
| # | Item | Type | Phase 2B readiness note |
|---|------|------|--------------------------|
| B2-33 | Group categories: confirm with the owner whether the feature is in scope (ROADMAP §1.2 vs the clarified vision). If in scope, define the taxonomy + "load all groups of a category" action | [OPEN] | Q1 — see §11 |
| B2-34 | Group status policy: confirm the "only `publish` groups are loadable" policy (reassessed from D24/U11) | [OPEN] | Q2 — see §11 |
| B2-35 | Table-style visual design: finalize the `minimal` / `boxed` CSS within the house palette | [OPEN] | Q3 — see §11 |
| B2-36 | Admin menu fractional offset (e.g. `+0.1` vs `+0.5`) after collision check | [OPEN] | Q4 — see §11; resolved by Phase 2B output + owner confirmation |

### 9.8 Checklist summary
| Category | Count | Items |
|----------|-------|-------|
| **Runtime verification required in Phase 2B** | **14** | B2-2, B2-3, B2-7, B2-14, B2-18, B2-19, B2-20, B2-21, B2-23, B2-24, B2-26, B2-27, B2-28, B2-29 |
| `[CONFIRMED]` architectural requirements (Phase 3/4) | 18 | B2-1, B2-4, B2-5, B2-6, B2-8, B2-9, B2-10, B2-11, B2-12, B2-13, B2-15, B2-16, B2-17, B2-22, B2-25, B2-30, B2-31, B2-32 |
| `[OPEN]` owner decisions | 4 | B2-33, B2-34, B2-35, B2-36 (= Q1–Q4) |
| **Total** | **36** | B2-1 … B2-36 |

Every one of the 14 runtime items has: (a) a static source-evidence base with exact
file:line references (spec §19), (b) a defined verification method, and (c) a designated
fixture or read-only inspection target. None has been executed yet. **The checklist is
complete and actionable for Phase 2B.**

## 10. Fixture and Safety Review

### 10.1 Designated fixtures (D23)
| Role | Product | Type | Attributes | Variations |
|------|---------|------|-----------|------------|
| Simple fixture | **13143** (Philips OneBlade QP2824/10) | simple | 13 (5 taxonomy: `pa_brand`, `pa_at_mdl`, `pa_at_mnbe-anrzhi`, `pa_وضعیت-محصول`, `pa_guarantee` + 8 custom Persian-named, incl. multi-value ASCII-comma values) | 0 |
| Variable fixture | **17028** (OnePlus 15) | variable | 2 (both taxonomy + variation-linked: `pa_color`, `pa_memory-capacity`) | **16** (17484–17499) |

Fixture suitability for Phase 2B:
- **13143** exercises both taxonomy and custom attributes (including multi-value custom
  values) — suitable for B2-19 / B2-21 / B2-23 (save behavior, native editing, frontend).
- **17028** exercises variation-linked attributes — suitable for confirming GPA does not
  disturb variation semantics (D15: parent is the primary attribute source; product type
  is never inferred from variation existence).
- Both are `publish` and publicly reachable for frontend inspection.

### 10.2 Backups and restoration (verified in this audit)
- `backups/gpa-test-fixtures/` contains both byte-exact raw JSON backups
  (`product-13143-*.json`, 28,411 bytes; `product-17028-*.json`, 76,868 bytes) —
  **presence verified** by directory listing in this audit [STATIC CODE EVIDENCE].
- Backups are gitignored (never committed).
- Restoration was previously verified on hidden copies **17601** (simple) and **17602**
  (variable): byte-exact meta + terms (+ 16/16 variations) match the originals
  (registry §2).
- The registry (`docs/testing/test-fixtures.md`) is current and consistent with the
  native-template model (CPT not yet registered; `ani_gpa_group_cat` not in the core —
  Q1).

### 10.3 Safety boundaries
- **Isolation (D23):** authorization applies ONLY to 13143/17028 and explicitly created
  GPA test fixtures (duplicates). No unrelated products, orders, inventory, pricing,
  customer data, global settings, or production modules.
- **Shared-attribute caution:** global attribute definitions and terms are shared across
  products; before creating/renaming/deleting any global attribute or term, inspect
  usage on non-test products; prefer the `GPA TEST — ` prefix; stop and report if an
  operation could affect non-test products.
- **WP-CLI read-only by default:** mutating commands require explicit task justification
  + stated target + minimal scope.
- **Two-path filesystem boundary (D1):** workspace + target WordPress installation only.
- **No plugin/theme install/activate/deactivate** without explicit authorization.
- **Phase 2B-specific:** runtime verification is read-only inspection + controlled
  experiments confined to the fixtures (or their duplicates). No GPA implementation code
  is written in Phase 2B. Any fixture modifications made during experiments are permitted
  test data (no cleanup requirement per D23) but must be recorded in the fixture registry.

### 10.4 Fixture verdict
Fixtures are **READY**: present, backed up (verified), restoration-verified, registered,
and suitable for all 14 runtime verification items.

## 11. Q1–Q4 Impact Analysis (assessed, not resolved)

| Q | Item | Impact on Phase 2B | Blocking? |
|---|------|--------------------|-----------|
| **Q1** (B2-33) | Group categories — in scope? | **None on Phase 2B.** The core architecture is independent of categories; no taxonomy exists; none of the 14 runtime items touches categories. If the owner later decides in-scope, Phase 3 adds the taxonomy + "load all groups of a category" action. | **No** — deferrable to Phase 3 scoping |
| **Q2** (B2-34) | Group status policy — confirm "only `publish` groups are loadable" | **Minimal.** The spec already assumes publish-only (B2-12 `[CONFIRMED]`: group select lists only `publish` groups; B2-13 Load REST validates). Phase 2B does not implement the picker, so no Phase 2B work depends on the final answer. If the owner confirms a different policy, B2-12/B2-13 adjust in Phase 3. | **No** — confirmation of an already-assumed policy |
| **Q3** (B2-35) | Table-style visual design — finalize `minimal`/`boxed` CSS | **Partial, non-blocking.** Phase 2B verifies the *mechanism* (body-class scoping, conditional enqueue, exact selectors against live markup — B2-24/B2-26), not the final visual design. The final CSS (colors/spacing within the house palette) is a Phase 3/4 concern. | **No** — mechanism verification proceeds; visual finalization deferrable |
| **Q4** (B2-36) | Admin menu fractional offset — confirm after collision check | **Direct, but produced by Phase 2B.** Phase 2B's read-only `$menu` inspection (B2-2/B2-3) yields the live Products position + collision evidence at candidate offsets; the owner then confirms the offset. Q4 is *resolved by* Phase 2B output + owner confirmation, not a precondition. | **No** — a Phase 2B deliverable, not a precondition |

**Verdict:** none of Q1–Q4 blocks the start of Phase 2B. Q4 is the only question whose
answer is *produced by* Phase 2B (the collision check); the others are confirmations or
deferrals.

## 12. WooCommerce/Blocksy Compatibility Review

### 12.1 Dependency matrix
| Dependency | Live version (LIVE-VERIFIED 2026-11-23) | Depended-on behavior | Static evidence | Runtime re-confirmation |
|-----------|------------------------------------------|----------------------|-----------------|--------------------------|
| WooCommerce | 11.3.1 | `woocommerce_add_attribute` AJAX action (nonce `add-attribute`, cap `edit_products`) | class-wc-ajax.php:779; meta-boxes-product.js:692 | B2-14, B2-28 |
| WooCommerce | 11.3.1 | `prepare_attributes` discards empty-value rows | class-wc-meta-box-product-data.php | B2-19, B2-28 |
| WooCommerce | 11.3.1 | `shop_attributes` frontend template (render chain) | wc-template-functions.php:4261; templates/single-product/product-attributes.php (v9.3.0) | B2-23, B2-28 |
| WooCommerce | 11.3.1 | Global attribute registry APIs + rename/delete migration | wc-attribute-functions.php:55,148,186,199,509,656-714,777 | B2-7 |
| WooCommerce | 11.3.1 | `woocommerce_product_options_attributes` hook location | html-product-data-attributes.php:69 | B2-10 (low-cost) |
| Blocksy (Free) | 2.1.62 | Additional Information tab via `woocommerce_product_tabs` → `woocommerce_product_additional_information()` | blocksy/inc/components/woocommerce/single/tabs.php | B2-29 |
| Blocksy (Free) | 2.1.62 | Live markup for CSS selectors (table inside the tab wrapper) | — | B2-26 |
| persian-woocommerce | 10.0.7 (active) | Attribute-display coexistence (D19) | Prior static finding: does not hook the attribute display path (reduces conflict surface) | B2-27 (required by D19) |
| WordPress core | 7.1.5 | Admin menu ordering mechanism (`$menu` global, float positions, `$_wp_last_object_menu = 25`) | wp-admin/menu.php:121,140-142 | B2-2, B2-3 |
| Child-theme modules | 14 modules (technical-conventions §2) | Coexistence; no duplicate hooks / parallel save handlers | — | B2-30 (Phase 4) |

### 12.2 Compatibility constraints (spec §15)
- Coexist with all 14 existing child-theme modules; MD5-locked files
  (`anishop-mobile-nav.js` / `.css`) untouched.
- No Blocksy Pro runtime dependency.
- No modification of WordPress core, Blocksy parent, Blocksy Companion, or WC core.
- Tolerate existing catalog anomalies (D21): stale `product_type` term counts,
  inconsistent `is_variation` flags, and attribute-data irregularities must not crash GPA.
- Persian RTL: all admin UI + table styles RTL-first; real-mobile verification in Phase 4.

### 12.3 Version-drift risk
All static evidence was gathered against the versions live-verified on 2026-11-23
(WP 7.1.5 / WC 11.3.1 / Blocksy 2.1.62 / persian-woocommerce 10.0.7). **Phase 2B must
re-verify the installed versions at start** (B2-28 includes this); if any dependency has
been updated, the affected static evidence must be re-collected before relying on it.

### 12.4 Compatibility verdict
All dependencies are statically verified with exact file:line evidence; the runtime
re-confirmation of the three core WC behaviors + the Blocksy render chain +
persian-woocommerce coexistence is precisely the core work of Phase 2B (B2-23 … B2-29).
**No compatibility blocker identified.**

## 13. Authorized / Prohibited Scope for Phase 2B

### 13.1 Authorized (upon explicit owner authorization)
1. Read-only inspection of the live WordPress installation: WP-CLI read-only commands,
   WordPress Studio MCP (`site_info`, read-only `wp_cli`, `take_screenshot`,
   `inspect_design`).
2. Runtime verification of the 14 `[2B-VERIFY]` items (B2-2, B2-3, B2-7, B2-14, B2-18,
   B2-19, B2-20, B2-21, B2-23, B2-24, B2-26, B2-27, B2-28, B2-29).
3. Controlled experiments confined to the designated fixtures (13143, 17028) or their
   duplicates: native add-attribute via the product editor, save-discard behavior,
   reordering/removal, manual Global/Custom add, frontend rendering inspection, CSS
   selector inspection.
4. Creating test-only global attributes/terms with the `GPA TEST — ` prefix if needed —
   after inspecting usage on non-test products (D23 isolation rule).
5. Creating a dedicated branch `phase-2b-runtime-verification` (base per owner decision
   G1, §14.3).
6. Producing a Phase 2B report with provenance labels
   ([CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE VERIFIED], [REAL-MOBILE USER-REPORTED
   EVIDENCE], [STATIC CODE EVIDENCE]) and updating the fixture registry with any test
   data created.

### 13.2 Prohibited
1. **No GPA implementation** — no PHP/JS/CSS module code, no CPT registration, no menu
   registration, no REST endpoint, no enqueue (Phase 3 work).
2. No modification of production code: child theme, plugins, WordPress core, Blocksy
   parent/companion, WC core.
3. No modification of the 14 existing child-theme modules.
4. No modification of unrelated products, orders, inventory, pricing, or customer data.
5. No modification of global WooCommerce settings unrelated to GPA.
6. No plugin/theme install/activate/deactivate.
7. No catalog cleanup / delimiter normalization (D20/D21).
8. No modification of the live admin menu (menu verification is read-only: inspect the
   `$menu` global / rendered sidebar; do not register a menu).
9. No merge into `main`; no start of Phase 3.
10. No compatibility claim (especially persian-woocommerce, D19) without runtime testing.

## 14. Final Readiness Assessment

### 14.1 Readiness matrix
| Dimension | Status | Evidence |
|-----------|--------|----------|
| Architecture approved | **READY** | D28–D39; the spec is the contract for Phase 2B/3 (spec closing note) |
| Documentation consistency | **READY** | §8 — all active documents consistent; no known stale active references |
| `[2B-VERIFY]` checklist | **READY** | §9 — 36 items complete; 14 runtime items each with static evidence + defined method |
| Test fixtures | **READY** | §10 — present, backed up (verified), restoration-verified, registered |
| Safety boundaries | **READY** | §10.3 / §13 — isolation, read-only default, two-path boundary |
| Compatibility prerequisites | **READY** | §12 — all dependencies statically verified; runtime re-confirmation is Phase 2B work |
| Open owner decisions | **NON-BLOCKING** | §11 — Q1–Q4 assessed; none blocks Phase 2B start |
| Owner authorization | **PENDING** | Phase 2B requires explicit owner authorization (D13 principle; memory-bank Current State) |
| Base-branch governance | **PENDING (G1)** | §14.3 — new finding; owner decision required |

### 14.2 Conclusion
**Phase 2B is READY to begin**, subject to:
1. **Explicit owner authorization** (mandatory — Phase 2B is NOT STARTED and has not
   been authorized).
2. **Owner decision G1** (base branch, §14.3).

No implementation blockers were identified. The 14 runtime verification items are
well-defined, evidence-backed, and executable within the fixture isolation boundary.

### 14.3 Governance prerequisite G1 (new finding — owner decision required)
**Finding:** `main` (`2b4b090`) does **not** contain the authoritative Phase 2A.3
specification or the D28–D39 decisions. `git ls-tree main -- docs/architecture/` returns
empty; the spec exists only on unmerged phase branches. The latest documentation state is
on `chore/final-documentation-corrections` (`ff90628`), which is **21 commits ahead** of
`main`. Unmerged branches (all pushed to origin): `phase-1-investigation`,
`phase-1-1-decisions-finalization`, `phase-1-1-1-tax-query-verification`,
`phase-2a-architecture-specification`, `phase-2a-1-architecture-corrections`,
`phase-2a-2-final-architecture-consistency`,
`phase-2a-3-native-attribute-template-redesign`,
`chore/align-docs-with-native-template-architecture`,
`chore/final-documentation-corrections`.

**Why it matters:** the Git rules state that phase branches are created from `main`, and
`main` is merged only after owner approval. But Phase 2B verifies the spec, which is not
on `main`. Branching `phase-2b-runtime-verification` from `main` would place Phase 2B on
a tree that lacks the authoritative contract.

**Options for the owner:**
- **Option A:** approve merging the completed phase/chore branches into `main` (in
  dependency order), then create `phase-2b-runtime-verification` from the updated
  `main`.
- **Option B:** explicitly authorize creating `phase-2b-runtime-verification` from
  `chore/final-documentation-corrections` (`ff90628`), the latest documentation state,
  deferring the merge into `main`.

This audit does not resolve G1; it is recorded for the owner's decision alongside the
Phase 2B authorization.

## 15. Files Created
- `reports/2026-11-26-phase-2b-readiness-audit.md` — this report.

## 16. Files Modified
None.

## 17. Files Deleted
None.

## 18. Tests and Verification
- **Git state verified before and after:** starting branch/HEAD/clean tree/`main`
  (`git rev-parse`, `git status --porcelain`, `git ls-remote`); final state re-verified at
  commit time.
- **Full document review:** all 10 mandatory documents read in full (spec 1423 lines;
  decisions; project rules; roadmap; fixture registry; project context; memory bank; 3
  reports).
- **Consistency cross-check:** 11-concern matrix (§8.1) cross-referenced across all
  active documents.
- **Checklist enumeration:** B2-1 … B2-36 extracted from spec §17 (full read of §17/§18);
  counts verified (14 + 18 + 4 = 36).
- **Fixture backup presence:** `ls -la backups/gpa-test-fixtures/` — both JSON backups
  present.
- **Governance check (G1):** `git ls-tree main -- docs/architecture/` (empty),
  `git show main:docs/architecture/phase-2a-architecture-specification.md` (absent),
  `git --no-pager log --oneline main..chore/final-documentation-corrections | wc -l`
  (21), `git --no-pager branch -a` (9 local + 9 remote branches).
- **No runtime verification performed** — this audit is documentation/Git inspection only
  (read-only).

## 19. Verified Results
- Starting Git state matches the required baseline (branch
  `chore/final-documentation-corrections` @ `ff90628`, clean tree, `main` = `2b4b090`
  local = remote) — [STATIC CODE EVIDENCE] (`git rev-parse` / `git ls-remote`).
- All active documents are consistent with the approved native-template model —
  [STATIC CODE EVIDENCE] (full read + §8.1 matrix).
- The `[2B-VERIFY]` checklist is complete: 36 items, 14 requiring Phase 2B runtime
  verification, each with static evidence + a defined method — [STATIC CODE EVIDENCE]
  (spec §17/§18/§19).
- Fixture backups are present in `backups/gpa-test-fixtures/` — [STATIC CODE EVIDENCE]
  (directory listing).
- `main` does not contain the authoritative spec (G1) — [STATIC CODE EVIDENCE]
  (`git ls-tree` / `git show`).
- `main` unchanged throughout the audit; no merge performed — [STATIC CODE EVIDENCE]
  (`git rev-parse main` before and after).

## 20. Not Verified / Limitations
- No runtime verification was performed or needed: this audit is read-only
  documentation/Git inspection.
- The live WordPress installation was not touched (no WP-CLI, no MCP, no browser).
- The 14 `[2B-VERIFY]` items remain **pending** — they are the work of Phase 2B.
- The live fixture state (current attributes of 13143/17028) was not re-inspected in this
  audit; the registry records the state as of Phase 1.1 (2026-11-24). Phase 2B should
  re-confirm fixture state at start.
- G1 (base branch) is identified but not resolved — owner decision required.

## 21. Risks and Open Questions
- **G1 (new):** `main` lacks the authoritative spec; the Phase 2B base branch requires an
  owner decision (§14.3).
- **Q1–Q4 (existing, unchanged):** group categories (Q1), group status policy (Q2),
  table-style visual design (Q3), admin menu fractional offset (Q4). None blocks Phase
  2B (§11).
- **Version drift:** if WC/Blocksy/persian-woocommerce is updated before Phase 2B, the
  static evidence must be re-collected (§12.3).
- **Fixture state drift:** fixture attributes may have changed since the Phase 1.1
  registry entry; re-confirm at Phase 2B start (§20).
- No other risks identified; this audit changed no code, data, or runtime state.

## 22. Scope Compliance
Only the authorized read-only audit and this report were produced. No application code,
no WordPress/WooCommerce/database/fixture changes, no Phase 2B runtime verification, no
merge into `main`, no start of Phase 3. All filesystem access was confined to the two
authorized paths (workspace; the target WordPress installation was not accessed).

## 23. Git Information
- **Branch:** `chore/phase-2b-readiness-audit`
- **Base branch:** `chore/final-documentation-corrections` (verified at
  `ff9062835559c595b85e7c3fa946d28ab9436a89`, clean tree)
- **Base commit:** `ff9062835559c595b85e7c3fa946d28ab9436a89`
- **Work commit:** `905794aaac87bfcd122614c763f8588a834aa21e`
  (`docs: add Phase 2B readiness audit report`)
- **Remote push status:** pushed to `origin/chore/phase-2b-readiness-audit`; remote HEAD
  verified via `git ls-remote` on 2026-11-26 = `905794aaac87bfcd122614c763f8588a834aa21e`
  (matches local branch HEAD at verification time)
- **`main` (local = remote):** `2b4b0902fbaee70519df20f1bcc63723e1d591ba` — unchanged
- **Working tree:** clean at verification time

## 24. Phase / Task Status
Task complete. Project phase status unchanged: Phase 0, 0.1, 1, 1.1, 1.1.1, 2A.1, 2A.2,
2A.3 COMPLETE; **Phase 2B NOT STARTED — awaiting explicit owner authorization**
(readiness: READY, subject to authorization + G1); Phases 3/4/5 NOT STARTED.

## 25. Recommended Next Step
Owner review of this audit, then:
1. Decision on **G1** (base branch: merge completed phases into `main` first, or branch
   from `chore/final-documentation-corrections`).
2. **Explicit authorization** to begin Phase 2B (runtime verification of the 14
   `[2B-VERIFY]` items) on `phase-2b-runtime-verification`.
3. Optionally, answers to Q1–Q4 (none is blocking; Q4 is produced by Phase 2B's
   collision check).

## 26. Stop Confirmation
Work has stopped after completing the audit, creating this report, committing, and
pushing the audit branch. Awaiting the project owner's next instruction. No Phase 2B
runtime verification, no implementation, and no merge was started.

