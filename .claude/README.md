# .claude directory

Local Claude Code setup for Libreria-web. It is excluded from git (`.git/info/exclude`), so it is not part of the graded repository.

## Layout

```
CLAUDE.md                          project instructions, loaded automatically
.claude/
  README.md                        this file
  settings.json                    permissions (secrets are not readable)
  agents/                          subagents for focused work
    issue-writer.md                drafts and creates issues, milestones and labels
    qa-reviewer.md                 read-only review against the rubric and conventions
    wordpress-plugin-dev.md        custom plugin and child theme (tienda/)
    laravel-engine-dev.md          integration engine (motor/)
    secrets-auditor.md             hunts for secrets in the working tree and the history
  skills/                          reusable knowledge, loaded when relevant
    github-issue-format/           the instructor's issue and milestone format
    rubric-check/                  Avance 1 and Avance 2 rubrics as a checklist
    wordpress-plugin-conventions/  plugin and child theme rules
    laravel-engine-conventions/    engine rules from the brief
    secrets-hygiene/               how secrets are kept out of the repository
  commands/                        slash commands
    progress.md                    /progress: status per milestone from GitHub
```

Start Claude Code with the repository as the working directory so these files load.

## Adding things

**Agent**: create `agents/<kebab-name>.md` with the frontmatter `name`, `description` and `tools`, followed by the system prompt. The description says *when* to use the agent. Give read-only agents only `Read, Grep, Glob, Bash`.

**Skill**: create `skills/<kebab-name>/SKILL.md` with the frontmatter `name` and `description`. Keep it short and put long reference material in extra files in the same folder.

**Command**: create `commands/<name>.md`. `$ARGUMENTS` receives whatever follows the command.

**Permissions**: edit `settings.json`. Personal overrides go in `settings.local.json`, which is never shared.

## Conventions

- Everything in this directory is written in English.
- Claude's role here is software engineer and tutor (defined in `CLAUDE.md`). Every agent repeats it in a `Role:` line near the top of its prompt; a new agent must do the same.
- Agents and skills point to `CLAUDE.md` and `contexto.md` instead of copying them, so a rule changes in one place.
- Only technologies named in the project brief (see `CLAUDE.md`).
- Nothing here may contain a secret.

## Ideas for later (not built)

- A hook that blocks commit messages containing a Claude signature.
- A hook that blocks `git push` to `main`.
- Agents for the Capstone: `soap-integration-dev` and `observability-dev`.
- An agent that turns the audit findings of Applied Research 2 into issues.
- Versioning this directory in the repo: it would show up in the instructor's view, so decide it as a team and mention it in the README's AI usage statement.
