# Reporting Template

Reusable end-of-task report template. Save each report as `reports/YYYY-MM-DD-{task-slug}.md`.

## Rules

- Never overwrite an earlier task report.
- Never rewrite historical reports to make them appear created later.
- Create a new report for each independent task.
- Reports must describe **actual** changes and evidence, not assumptions.
- Distinguish clearly: **Implemented / Tested / Verified / Not tested / Not verified**.
- Never present an untested assumption as a verified result.
- Include the final commit hash and remote push status.
- If a task is partially completed, clearly document what remains incomplete.

## Template

Copy everything below the line into a new `reports/YYYY-MM-DD-{task-slug}.md`.

---

# {Task Title}

## 1. Task Title
{Full task title}

## 2. Date
{YYYY-MM-DD}

## 3. Executive Summary
{2–5 sentences: what was done, the outcome, and the current state.}

## 4. Task Scope
{What was in scope for this task.}

## 5. Authorized Work
{The specific work the project owner authorized.}

## 6. Files Created
- `{path}` — {purpose}
(Or "None.")

## 7. Files Modified
- `{path}` — {what changed and why}
(Or "None.")

## 8. Files Deleted
- `{path}` — {reason}
(Or "None.")

## 9. Detailed Changes
{Per-file or per-area description of the changes and the rationale for each.}

## 10. Tests and Verification
{What verification was performed: commands run, checks performed, evidence. Separate static / runtime / browser / Git verification as applicable.}

## 11. Verified Results
- {Result} — {evidence / command output}
(Only items actually verified. Label provenance, e.g. [STATIC CODE EVIDENCE], [CDP/HEADLESS EVIDENCE], [LIVE-VERIFIED].)

## 12. Not Verified / Limitations
- {Item} — {why it could not be verified}
(Or "None.")

## 13. Risks and Open Questions
- {Risk / question}
(Or "None.")

## 14. Scope Compliance
{Confirmation that only authorized work was performed; note anything discovered out of scope and left unchanged.}

## 15. Git Information
- **Branch:** `{branch}`
- **Base branch:** `main`
- **Base commit:** `{hash}`
- **Final commit hash:** `{hash}`
- **Remote push status:** {pushed to `origin/{branch}` and verified via `git ls-remote` / not pushed (reason)}

## 16. Phase / Task Status
{e.g. "Phase 0.1 complete. Phase 1 remains NOT authorized."}

## 17. Recommended Next Step
{The single recommended next step, pending owner authorization.}

## 18. Stop Confirmation
{Confirmation that work has stopped and is awaiting the project owner's next instruction.}
