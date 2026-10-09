---
name: github-issue-format
description: Write GitHub issues, milestones and labels for Libreria-web in the instructor's format. Use whenever creating, editing or reviewing issues, milestones or labels.
---

# GitHub issue and milestone format

## Rules
- English. No Claude signature of any kind. No due dates on milestones. No assignees unless the user asked.
- Show the full draft in the chat first; create or edit only after approval.
- Titles are short and imperative (`Create the child theme with a visible change`).
- An issue belongs to exactly one milestone. Never leave a milestone with a single issue.

## Issue body

```
**Context / User story**
> As a **<role>**, I need <what>, so that <why>.

**Acceptance criteria**
- [ ] <verifiable condition>
- [ ] <verifiable condition>

**Technical details**
- <implementation note: files, classes, hooks, commands>
- Requirements: <RF-xx, RNF-xx> · Brief <section>.
```

- Roles for the story: visitor, customer, product editor, store administrator, API consumer, instructor, team developer, QA, auditing team.
- Criteria are checkable by someone else; the last one is usually `The change is merged through a reviewed pull request.` when code or files change.
- The last bullet is always `Requirements:`. Use RF/RNF ids from the team requirements document and the brief section (`Brief 5.3.3`, `Project 1 brief, section 6.6`). If the item is not in the brief, write `not in the brief; added for scalability` (or performance) and what it relates to.
- Cross-reference other issues with `#N`. Pull requests consume issue numbers, so check the real numbers first.

## Milestone body
Same three sections. The story is written for the person who benefits; the last bullets give the deliverable (`Avance 1 (tag v-avance1)`), the rubric item with its points and the brief section.

## Milestones
Title format is `Mxx · Title` (middle dot). M01 Repository & Team Setup · M02 Hosting & Deployment · M03 Catalog & Child Theme · M04 Custom Plugin · M05 Security, SEO & Backup · M06 API, Postman & Delivery · M07 External Connector · M08 Laravel Setup & Base Artifacts · M09 API Contract · M10 WooCommerce Sync · M11 Webhooks · M12 Connector & Integration Log · M13 AI Address Normalization · M14 Tests, Demo & Delivery · M15 Audit · M16 Capstone Project. M01–M06 are Avance 1, M08–M14 are Avance 2, M07 and M15 are the applied research, M16 is the Capstone.

## Labels
- Area: `store`, `plugin`, `infra`, `repo`.
- Type: `config`, `development`, `testing`, `delivery`. Documents use GitHub's `documentation`.
- Priority: `priority: high` (blocks others or the review), `priority: medium` (needed for the deliverable), `priority: low` (can be last).
- Removed on purpose, do not recreate: `theme`, `engine`, `postman`, `pending-brief`, `good first issue`.

## Creating with gh
- `gh` is `C:\Program Files\GitHub CLI\gh.exe`; repository `Alejandro220106/Libreria-web`.
- `gh issue create -R <repo> --title "<title>" --body-file <file> --milestone "<exact milestone title>" --label "<label>"` (one `--label` per label). Never pass long bodies inline.
- Edit a milestone with `gh api -X PATCH repos/<repo>/milestones/<number> -f title=... -F description=@<file>`.

## Verify afterwards
`gh api "repos/<repo>/issues?state=all&per_page=100"`: the three sections are present, there is a `Requirements:` line, a milestone is set, no assignees, and no text such as `Claude`, `Co-Authored` or `Generated with`.
