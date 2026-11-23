# Commit Conventions

Mandatory commit and push rules for this project. Authoritative policy: `.clinerules/project-rules.md` (§Commit and push requirements).

## Commit message structure

- Format: `type: description`
- `type` is lowercase and reflects the change kind. Types observed in this repository:
  - `docs:` — documentation, rules, reports, decisions.
  - `chore:` — maintenance, tooling, repository setup.
  - (Use other conventional types such as `feat:`, `fix:`, `test:` when they fit, consistent with the repo.)
- `description` is a concise, imperative summary of the change.
- Examples (from this repository):
  - `docs: add decision D13 clarifying Phase 1 authorization`
  - `docs: add git-workflow-and-reporting skill`
  - `chore: initial workspace baseline`

## Commit granularity

- One logical change per commit.
- Group related files that serve a single purpose into one commit.
- Keep commits small and reviewable.
- Do not bundle unrelated changes into one commit.

## Diff inspection requirements

- Before committing, review the **complete** diff:
  - `git --no-pager diff` (unstaged)
  - `git --no-pager diff --staged` (staged)
- Verify that **only authorized files** are staged.
- Stage specific files: `git add <file>` (avoid `git add -A` / `git add .`).

## Exclusion of unrelated files

- Never include unrelated modifications in a task commit.
- If the working tree has unrelated changes, preserve them and exclude them from the commit.

## Secret and credential checks

- Before committing, scan the diff for:
  - Secrets, API keys, tokens, passwords.
  - Credentials or connection strings.
  - Temporary files, generated clutter, editor artifacts.
- If any are found, remove them from the commit and report.

## Push verification

- Push the task branch: `git push -u origin <branch>`.
- **Verify** the push succeeded:
  - `git ls-remote origin <branch>` — confirm the remote ref exists and points to the expected commit.
  - `git --no-pager log --oneline -1 origin/<branch>` — confirm the pushed commit.
- **Never** claim a successful push without verification.

## Failure handling

If a push fails:

1. Record the **actual** error message.
2. Preserve the local work (do not discard commits).
3. Do **not** change credentials or Git configuration to bypass the failure.
4. Do **not** claim the task was pushed.
5. Stop and report the issue to the project owner.
