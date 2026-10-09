---
name: secrets-hygiene
description: How secrets stay out of the Libreria-web repository, how to search the working tree and the Git history for them, and what to do if one is found. Use before any commit, pull request, tag or documentation change.
---

# Secrets hygiene

A secret that appears in any commit of the history counts as compromised, even if it was deleted afterwards. It must be revoked and regenerated, and it zeroes the API/Postman item in Avance 1 or the testing item in Avance 2.

## What counts as a secret
Passwords, WooCommerce consumer keys and secrets (`ck_...`, `cs_...`), webhook secrets, AI provider keys, tokens, `Authorization` headers with real values, the instructor's credentials, and real values in Postman environments.

## Where they live
- `.env` files and local Postman environments only, both git-ignored. Never read or print them.
- The repository only has `.env.example` and `postman/*.postman_environment.example.json`, with the variable names and empty values.
- The instructor's administrator login and API keys go only in the campus submission text field.
- Docs, issues, pull requests and screenshots show no real values.

## Search (read-only)
- Working tree: search for `ck_`, `cs_`, `password`, `secret`, `token`, `api_key`, `Authorization`.
- Tracked files that should not be: `git ls-files | grep -E "\.env$|postman_environment\.json$"`.
- History, all branches and tags: `git log --all -p -S"<pattern>"`, and `git log --all --diff-filter=A --name-only` to spot `.env`-like files that were ever added.
- Report file, commit and kind of secret; never copy the value.

## If one is found
1. Revoke it and generate a new one (WooCommerce > Settings > Advanced > REST API, or the provider's panel).
2. Remove it from the tree. Rewriting history does not make it safe again; the revocation does.
3. Tell the team (the PM and the team leader first) and note it in the pull request.
4. Re-run the search to confirm nothing else leaked.
