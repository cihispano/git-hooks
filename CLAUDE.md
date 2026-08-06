# CLAUDE.md

Guidance for Claude Code sessions working in this repository.

## PR Conventions

- Before changing codeflow, study the repository first, then propose changes; never act alone on branches.
- The user creates issues, PRs, and manages merges.
- Keep branches short-lived, cut from `develop`.

## QA

- `composer check:all` — full validation (PHPStan level 10 + PHPCS + PHP CS Fixer + PHPUnit).
- `composer test` — PHPUnit suite.
- `composer analyze` — PHPStan.
- `composer sniff` — PHP_CodeSniffer (PSR-12).
- `composer cs` / `composer cs:fix` — PHP CS Fixer.
- `composer sniff:fix` — phpcs auto-fix.

## GitFlow

- Default base: `develop`. `main` only for releases.
- Branches: `feature/*`, `chore/*`, `fix/*`.
- Commits and MRs follow Conventional Commits: `type(scope): subject`.
- MRs target `develop`; merges handled by the user.

## Issue & MR Templates

- **Issue templates** live in `.gitlab/issue_templates/`:
  - `Task-Chore.md`, `FeatureRequest.md`, `BugReport.md`
- **MR templates** live in `.gitlab/merge_request_templates/` — `Default.md`
- Follow the existing front-matter + Markdown structure; keep user-facing text in English.

## Repository structure

- `src/` — package source code.
- `src/Hooks/` — distributed Git hook scripts.
- `src/Util/` — reusable utility classes.
- `src/Support/` — shared support classes.
- `tests/` — PHPUnit suite.
- `docs/` — package documentation.

## Local hooks

- `composer install-hooks` / `composer uninstall-hooks` reinstall or remove the packaged hooks.
- `ComposerScripts::postInstall`/`postUpdate` wiring is **not** registered in `composer.json` (`post-install-cmd`/`post-update-cmd` are absent), so hooks are NOT auto-installed on install/update today.
- Commit message validation reference: `docs/CONVENTIONAL_COMMITS.md`.
