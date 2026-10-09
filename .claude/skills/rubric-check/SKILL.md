---
name: rubric-check
description: Check Deliverable 1 (Avance 1) or Deliverable 2 (Avance 2) against the course rubric before tagging it. Pass 1 or 2 as the argument.
argument-hint: "[1|2]"
---

# Rubric check

Use the argument to pick the deliverable (`1` = Avance 1, `2` = Avance 2); if none is given, ask. The numbered conditions are in `contexto.md` (section 5 for Avance 1, section 6 for Avance 2) and in `Proyecto 1.pdf`. Only the content of the tag is graded.

For each rubric item: mark every condition pass or fail with evidence (file, command, URL, issue number), give the level it earns, and list what is missing and which issue covers it. Each item has three levels with no in-between values.

## Avance 1 (100 points, tag `v-avance1`)

| Item | Excellent | Regular | Insufficient |
|---|---|---|---|
| Deployed store and catalog (20) | all of 5.1 and 5.2 → 20 | HTTPS valid but 1–2 conditions fail → 12 | no valid HTTPS or 3+ fail → 0 |
| Custom plugin (25) | all 7 of 5.3 → 25 | field readable by REST but 1–2 others fail → 15 | field missing in REST or 3+ fail → 0 |
| Plugins, security and SEO (20) | all 4 of 5.4 → 20 | 1–2 fail → 12 | 3+ fail → 0 |
| Backup and initial quality (10) | all 3 of 5.5 → 10 | one fails, not the restore video → 6 | no restore video or 2+ fail → 0 |
| API and Postman (15) | six results and no secrets in history → 15 | four or five results, no secrets → 9 | three or fewer, or a secret in history → 0 |
| Repository and teamwork (10) | structure 4.1, README 4.3, tag, all changes through reviewed PR, commits from everyone → 10 | one fails → 6 | two or more fail, or no tag → 0 |

Late delivery: accepted up to 24 hours with −15 points.

## Avance 2 (100 points, tag `v-avance2`)

| Item | Excellent | Regular | Insufficient |
|---|---|---|---|
| Contract and API format (15) | Newman with no failures and all 5 of 6.3 → 15 | 1–3 Newman failures or one condition fails → 9 | more than 3 failures or cannot run → 0 |
| Consuming WooCommerce (15) | all 5 of 6.4 → 15 | syncs all products but 1–2 others fail → 9 | does not sync all, or 3+ fail → 0 |
| Webhooks: signature and idempotency (20) | all 6 of 6.5 → 20 | verifies signature and drops duplicates but 1–2 others fail → 12 | no signature check, or duplicates processed → 0 |
| Integrated connector (15) | all 5 of 6.6 → 15 | runs the action but the provider is named in a controller or job, or a provider failure affects the webhook response → 9 | the action does not run → 0 |
| Integration log (10) | all 4 of 6.7 → 10 | logs but fields or a system are missing → 6 | no log, or it stores secrets → 0 |
| AI case (10) | all 5 of 6.8 → 10 | validates against the schema but 6.8.3, 6.8.4 or 6.8.5 fails → 6 | no case, or the answer is used unvalidated → 0 |
| Testing, reproducibility and team (15) | 7 tests exist and pass, startup per 6.2, video per 6.10, no secrets in history, everyone has commits and reviewed PRs → 15 | one fails, not a secret → 9 | two or more fail, or a secret in history → 0 |

Late delivery: not accepted. Before 17:00 on the delivery day the auditing team is added as collaborators with write access.

## Closing the check
Finish with the estimated score per item and in total, the three riskiest gaps, and the issues that cover them. Run the `secrets-hygiene` checks too: a secret in the history zeroes the API/Postman item (Avance 1) or the testing item (Avance 2).
