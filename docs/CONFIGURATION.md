# Configuration

This page describes how the package behaves today and how it is configured.

## Current state (0.1.0 development)

Project-level configuration via a `git-hooks.json` file is **planned** (see the restructuring plan, task: "per-project git-hooks.json with opt-in auto-install"). It is **not** available yet. This section documents what the package uses today.

## Tool discovery and config fallbacks

Each hook looks for the tool's configuration in the **target project** first, and falls back to the package-shared defaults only when none exists in the project root:

| Tool              | Priority                                                             | Package fallback                    |
| ----------------- | -------------------------------------------------------------------- | ----------------------------------- |
| PHP CS Fixer      | `.php-cs-fixer.php` → `.php-cs-fixer.dist.php`                       | package `.php-cs-fixer.dist.php`    |
| PHP_CodeSniffer   | `phpcs.xml` → `phpcs.xml.dist`                                       | package `phpcs.xml.dist`            |
| PHPStan           | `phpstan.neon` → `phpstan.neon.dist`                                 | package `phpstan.neon.dist`         |
| PHPUnit           | `phpunit.xml` → `phpunit.xml.dist`                                   | package `phpunit.xml.dist`          |

Tools are executed only when the corresponding binary exists in the project's `vendor/bin` (`php-cs-fixer`, `phpcs`, `phpstan`, `phpunit`). Missing tools are skipped with a warning instead of blocking the commit or push.

## Staged-files scope

- `pre-commit` only inspects files staged for the commit (`git diff --cached --name-only --diff-filter=ACMR`, filtered to `*.php`).
- `pre-push` runs a full-project analysis (PHPUnit on the whole suite and PHPStan over the project root).

## Commit message validation (commit-msg)

Currently hardcoded in the hook:

- Conventional Commits format: `type(scope): description`.
- First line between 10 and 100 characters.
- Allowed types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `perf`, `ci`, `build`, `revert`.

See [CONVENTIONAL_COMMITS.md](CONVENTIONAL_COMMITS.md) for the full rules.

## Skipping hooks

See [Installation](INSTALLATION.md) — `git commit --no-verify`.

## Future: `git-hooks.json`

Planned project-level file to control:

- `auto_install` (opt-in hook installation on install/update)
- `build_dir`
- tool toggles (`phpstan`, `phpcs`, `php_cs_fixer`, `phpunit`)
- `commit_msg` overrides (`min_length`, `max_length`, `types`)

When implemented, this page will show the schema and defaults.
