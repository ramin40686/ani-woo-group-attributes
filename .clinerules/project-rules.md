# پروژه: ani-group-product-attributes (گروه‌بندی ویژگی‌های آنی)

## محیط و محدودیت‌ها
- این ماژول بخشی از قالب فرزند Blocksy Child است و نباید به عنوان افزونه مستقل نصب شود.
- مسیر نصب نهایی: `wp-content/themes/blocksy-child/inc/ani-group-product-attributes/`
- مسیر توسعه (Workspace): `/home/ramin/Downloads/woo-group-attributes/`
- **Child theme (only place for new code):** `/home/ramin/Local Sites/anishop/app/public/wp-content/themes/blocksy-child/`
- **Target WordPress:** `/home/ramin/Local Sites/anishop/app/public/`
- **Never work on:** `/home/ramin/Studio/kafshkohestan/`, `/home/ramin/Studio/my-wordpress-website/`
###  Permanent Filesystem Scope Rule (two-path boundary)

> **PERMANENT RULE (2026-11-16):** All filesystem access — read **and** write — is restricted to exactly **two paths**:
>
> 1. **The active workspace:** `/home/ramin/Downloads/woo-group-attributes/`
> 2. **The target WordPress installation:** `/home/ramin/Local Sites/anishop/app/public/`

> No other path may be read, written, or modified as part of this project. The old workspace (`/home/ramin/Downloads/test-novamira/`, excluding this workspace) is **history only** and must not be accessed. This rule applies to every phase and every session and may only be changed by an explicit owner decision recorded in `decisions.md`.

- زبان‌ها: فارسی (پیش‌فرض) و انگلیسی. تمام رشته‌های قابل ترجمه باید هماهنگ با مازول های قبلی  باشد:
/home/ramin/Local Sites/anishop/app/public/wp-content/themes/blocksy-child/languages/fa_IR.mo
/home/ramin/Local Sites/anishop/app/public/wp-content/themes/blocksy-child/languages/fa_IR.po
- پیشوند تمام کلاس‌ها، توابع و آپشن‌ها:هماهنگ با سایر ماژول ها باشد یا از این پیشوند استفاده شود: `ani_gpa_` یا `Ani_GPA_`.

## استانداردهای کدنویسی
- از استانداردهای WordPress PHP Coding Standards پیروی کن.
- تمام فایل‌های PHP باید در ابتدا `if ( ! defined( 'ABSPATH' ) ) { exit; }` داشته باشند.
- نام‌گذاری کلاس‌ها به صورت `Ani_GPA_Class_Name` و توابع به صورت `ani_gpa_function_name`.
- برای ذخیره داده‌ها از Custom Post Type `ani_gpa_group` استفاده کن. (تاکسونومی `ani_gpa_group_cat` جزو معماری هسته نیست — دسته‌بندی گروه‌ها در نسخه اولیه خارج از محدوده است (D40).)
- متادیتای گروه: `_ani_gpa_group_attributes` (آرایه مرتب‌شده از ارجاعات `pa_{slug}` ویژگی‌های سراسری). هیچ متادیتای اختصاصی GPA روی محصول ذخیره نمی‌شود؛ داده بومی `_product_attributes` محصول منبع یکتای حقیقت است (D30).

## قوانین امنیتی
- تمام خروجی‌ها با `esc_html()`، `esc_attr()` یا `wp_kses_post()` escape شوند.
- در ذخیره متادیتا از nonce و بررسی `current_user_can( 'edit_product', $post_id )` استفاده شود.
- از `sanitize_text_field()` و `wp_unslash()` برای ورودی‌ها استفاده شود.

## بین‌المللی‌سازی

- از فایل `.pot` در پوشه `languages/` استفاده شود.


## یکپارچگی با Blocksy
- استایل‌ها نباید با استایل‌های قالب تداخل داشته باشند. هماهنگ با سایر مازولهای قالب فرزند، از پیشوند `.ani-gpa-` برای تمام کلاس‌های CSS استفاده کن.
- در صورت نیاز به بازنویسی قالب، از فایل‌های template در پوشه `templates/` استفاده کن و از `get_stylesheet_directory()` برای مسیر استفاده کن.

## مستندات مرجع
- مستندات رسمی افزونه: `docs/references/group-attributes-reference.md`
- نکات فنی ویژگی‌های WooCommerce: `docs/woocommerce-attributes-dev-notes.md`




## Mandatory Project Execution Rules

> Authoritative, project-wide execution rules (established Phase 0.1, 2026-11-23). These apply to **every** task and phase. Where a rule here is more specific than a general convention, this section governs. The step-by-step operational procedure for applying the Git and reporting rules is in the `git-workflow-and-reporting` skill (`.clinerules/skills/git-workflow-and-reporting/`).

### Project-owner authorization

- The project owner is the **final authority** for project scope, architecture, implementation, and phase transitions.
- Cline must **not** infer authorization from roadmap entries, previous discussions, task completion, or the existence of a prepared phase.
- A new phase may begin **only** after explicit owner authorization.
- Completing one task does **not** automatically authorize the next task.
- Cline must **stop** after completing the assigned task (see End-of-task stop condition).

### Task scope control

- Execute **only** the task explicitly assigned.
- Do **not** expand scope independently.
- Do **not** implement features while performing documentation or investigation tasks.
- Do **not** change production code unless explicitly authorized.
- Do **not** modify unrelated modules, snapshots, configuration, or project files.
- If an additional issue is discovered, **document it and request authorization** instead of fixing it outside the assigned scope.

### Branch and Git management

- **Never** work directly on `main`.
- Every independent phase uses its own branch: `phase-{N}-{slug}` (e.g. `phase-1-investigation`), created from `main`.
- Maintenance and documentation tasks use dedicated chore branches: `chore/{slug}` (e.g. `chore/project-workflow-standardization`).
- Every completed task is **committed and pushed** to its corresponding remote branch.
- **Never** merge into `main` without explicit project-owner authorization.
- **Never** force-push.
- **Never** delete branches without explicit authorization.
- **Never** reset, overwrite, or discard user changes.
- **Never** include unrelated changes in a task commit.
- Do **not** create a Pull Request unless explicitly requested or required by an established repository workflow.
- Do **not** merge a Pull Request without explicit authorization.
- These rules extend the `Git Workflow & Repository` section below.

### Reporting requirements

- Every independent task and every project phase produces a dedicated report under `reports/` (naming: `YYYY-MM-DD-{task-slug}.md`).
- Reports are **committed and pushed** with the corresponding task changes.
- Reports must reflect **actual repository state and verified evidence**, not assumptions.
- Reports distinguish Implemented / Tested / Verified / Not tested / Not verified.

### Commit and push requirements

- Review the **complete diff** before committing.
- Verify that **only authorized files** are included.
- Check for secrets, credentials, temporary files, generated clutter, and unrelated modifications.
- Use descriptive commit messages consistent with the repository's conventions (`type: description`).
- Push the task branch.
- **Verify** the remote branch and pushed commit.
- **Never** claim a successful push without verification.
- If push fails: record the actual error, preserve the local work, do **not** change credentials or Git configuration to bypass the failure, do **not** claim the task was pushed, and stop to report the issue.

### End-of-task stop condition

After completing the assigned task:
1. Finish the report.
2. Commit and push the authorized changes.
3. Verify the final Git state.
4. Provide a concise completion summary.
5. **Stop** and wait for the project owner's next instruction.

Do **not** automatically begin another phase or task.


##  Phase Discipline

- Phases are defined in `PROJECT-ROADMAP.md` (authoritative).
- **Do not advance to the next phase without explicit owner approval.**
- Phase 1 is investigation only — no implementation.
- **No implementation before architecture approval (Phase 2).**
- No speculative code changes. No implementation code in Phase 0/1.

##  Inspection Before Modification

- Always inspect the live state before changing anything.
- **Source-of-truth order:** live site → real WP/Woo data → browser/CDP output → WordPress Studio MCP → WP-CLI.
- Never determine site state from assumptions, memory, sample code, or stale data.
- Report findings with evidence and exact file paths.
- Separate fact from assumption; mark unverified items `NOT VERIFIED`.

##  Change Discipline

- **Small, verifiable tasks** over large autonomous tasks.
- **Minimal code changes; no unnecessary refactoring.**
- **Never modify** WordPress core, Blocksy parent, or Blocksy Companion files.
- All new code isolated in `blocksy-child`.
- **Preserve existing child-theme modules** (list in `technical-conventions.md` §2) — do not modify them without explicit approval.
- No new plugins, no JavaScript frameworks, no page builders.
- **No Blocksy Pro runtime dependency** in the final implementation.

##  Git Workflow & Repository

- **Repository:** `https://github.com/ramin40686/ani-woo-group-attributes` (HTTPS) — the workspace `/home/ramin/Downloads/woo-group-attributes/` is the repo's working copy.
- **Per-phase branches:** every phase starts with a new branch `phase-{N}-{slug}` (e.g. `phase-1-investigation`) created from `main`. All phase work is committed on that branch.
- **Merge policy:** a phase branch is merged into `main` only after the phase is complete **and** the owner has approved it. `main` always reflects the last approved state.
- **Child-theme snapshot:** a copy of the latest live child theme lives in the repo at `snapshots/blocksy-child/` — refresh it whenever the live `blocksy-child` changes.
- **Task reports on GitHub:** every end-of-task report is written to `reports/YYYY-MM-DD-{task-slug}.md` in the workspace and committed + pushed, so the owner and external AI assistants (e.g. ChatGPT) can read it on GitHub.

##  Safety

- **WP-CLI is read-only by default.** Mutating commands (`option update`, `post update`, `plugin install/activate`, `theme activate`, etc.) require explicit task justification + stated target + minimal scope.
- No database writes without explicit task authorization.
- No plugin/theme install/activate/deactivate without explicit task authorization (Pro installation for investigation requires owner approval).
- Temporary diagnostic files: outside production, used only for that task, removed after the task.
- Never use a database write as a shortcut for a frontend problem.

##  Data & Content

- **Real data only.** Never fabricate products, prices, stock, variations, categories, attributes, ratings, or contact info.
- Anishop is a **Persian RTL** store; every frontend change must consider RTL (direction, typography, alignment, spacing, responsive behavior on mobile + desktop).

##  Verification

- **Never mark a test PASS that was not run.**
- Use the right verification: static/syntax, browser/CDP, mobile, desktop, WP/Woo state, regression, console errors, network requests.
- **CDP/headless success does not imply real-mobile success.**
- Provenance labels (never convert one type into another):
  - `[REAL-MOBILE USER-REPORTED EVIDENCE]` — physical device via Local Live Link. Authoritative for mobile-only behavior.
  - `[CDP/HEADLESS EVIDENCE — NOT REAL-MOBILE VERIFIED]` — headless Chrome/curl. Controlled-environment evidence only.
  - `[STATIC CODE EVIDENCE]` — source reading/auditing only.
  - `[DOCUMENTED <date>]` — carried over from the old workspace (verified at the stated date); re-verify before relying on it.

##  Reporting

- Final report per task must separate: **Changed / Verified / Not Verified / Risks & Open Questions**.
- **Every report lists the changed files** (created / modified / deleted) with their paths.
- **Reports are published to GitHub:** in addition to the Cline chat and the VS Code workspace, every end-of-task report is saved to `reports/YYYY-MM-DD-{task-slug}.md` and pushed to the repo (see §Git Workflow & Repository).
- **Report blockers clearly instead of entering repetitive analysis loops.**
- When a decision needs owner input, present the specific question + available evidence.
- Do not claim success for anything not actually tested.

##  Localization

- Admin UI strings: **English source + Persian translation** (`blocksy-child/languages/fa_IR.mo`, text domain `anishop`).
- Frontend UI: Persian, RTL.
- Technical terms (e.g. Renderer, Swatch) may stay English in admin UI (established convention).

## Test Environment Policy (PERMANENT — Phase 1.1, 2026-11-24)

> Authoritative, project-wide testing policy. Applies to **all** future GPA development
> phases. Decision D23. Registry: `docs/testing/test-fixtures.md`.

### Designated test fixtures

Two existing Anishop products are the **permanent** GPA development/testing fixtures:

| Role | Product ID | Name | Type | Real variation children |
|------|-----------|------|------|-------------------------|
| Simple fixture | **13143** | ماشین اصلاح فیلیپس مدل OneBlade QP2824/10 (Philips OneBlade QP2824/10) | simple | n/a |
| Variable fixture | **17028** | گوشی موبایل وان پلاس مدل 15 (OnePlus 15) | variable | **16** |

Both were backed up **before** any modification (raw, byte-exact JSON incl. post, all meta,
all term relationships, and variation children) to `backups/gpa-test-fixtures/` (gitignored —
never committed). Restoration was verified on hidden test copies (17601, 17602) that match the
originals byte-for-byte. See the registry for backup locations and restoration procedure.

### Permanent testing authorization

Within the designated fixtures **and** products explicitly created as GPA test fixtures
(duplicates of the fixtures), Cline is authorized to:

- Add, edit, and remove attributes (global and custom).
- Create new global attributes and taxonomy terms when necessary.
- Create, edit, and delete GPA groups. (Group categories are not in scope for the initial
  version — D40; creating them requires a separate, separately approved feature.)
- Load GPA groups into test products (native attribute row insertion); select, edit, and
  remove attribute values through the native WooCommerce controls; reorder attributes
  through the native controls.
- Change product attributes through the native WooCommerce workflow (no GPA-specific
  product metadata exists — D30).
- Create additional test products by duplicating the fixtures; create as many additional
  test groups, attributes, and products as reasonably needed.

**There is no requirement to clean up these test fixtures after every task or phase.**
Accumulated test data may remain available throughout the project.

### Strict isolation boundary

This authorization applies **ONLY** to the designated fixtures and explicitly-created GPA
test fixtures. It does **NOT** authorize Cline to:

- Modify unrelated live products, customer orders, or customer data.
- Change real store inventory or pricing.
- Modify production product attributes outside the test fixtures.
- Change global WooCommerce settings unrelated to GPA.
- Modify production theme modules or unrelated site configuration.
- Perform bulk operations on the live catalog.

Global attribute definitions and taxonomy terms are shared across products. **Before
creating, renaming, modifying, or deleting any global attribute or term, inspect whether it
is already used by non-test products.** Never rename or delete a shared production attribute
or term merely for testing. Prefer uniquely named test attributes/terms with a clear prefix,
e.g. `GPA TEST — ...`. If an operation could affect non-test products, **stop and report**
before proceeding.

### Test-fixture naming & registry

- Test-only products/attributes/terms use the `GPA TEST — ` prefix.
- Every fixture, backup, test attribute/term, and group is recorded in
  `docs/testing/test-fixtures.md`, which must be kept current as the project evolves.

# General WordPress Rules (global rule bank + reasoning policy)

> **Verified 2026-11-15 (Phase 0.2):** Cline loads a **global rule bank** from
> `/home/ramin/Documents/Cline/Rules/` (setting `clinerules.ruleBankPath`) in **every** workspace.
> It contains 4 generic WordPress rule files that are auto-loaded in full:
> `wordpress-coding-standards.md` (code style, hooks, DB, assets/AJAX, security) ·
> `wordpress-security-and-db.md` (nonces, escaping, `$wpdb->prepare()`, error handling) ·
> `wordpress-assets-and-ajax.md` (enqueue rules, REST-first, nonce verification) ·
> `wordpress-best-practices.md` (child themes, template hierarchy, i18n, testing) —
> plus `studio-mcp.md` (WordPress Studio MCP toolset).
>
> The per-topic summaries previously kept in this file were removed as duplicates of the global
> rule bank (the old workspace's `.clinerules/` holds byte-identical copies of the same 4 rules).
> Project-specific rules live in this file (`project-rules.md`).

## Reasoning Policy (not in the global rule bank — kept here)

- **Routine implementation tasks** (plugins, themes, HTML/CSS, UI, CRUD, API integrations, settings pages): implement the most likely correct solution immediately; avoid lengthy analysis; do not compare alternatives unless requested.
- **Extended reasoning only for:** debugging unknown issues, architecture decisions, database schema design, performance bottlenecks, security reviews, large-scale refactoring, complex asynchronous workflows, caching strategies.
- **Priority order:** 1. Correctness · 2. WordPress coding standards · 3. Security · 4. Maintainability · 5. Performance · 6. Brevity of reasoning.
- Concise explanations unless the user explicitly asks for detailed analysis.
