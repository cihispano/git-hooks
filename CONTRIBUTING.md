# Contributing

Thank you for contributing to `cihispano/git-hooks`.

This guide explains how to work on the package locally, run validations, understand the hook flow, and keep contributions consistent with the current repository workflow.

## Requirements

- PHP 8.1 to 8.4
- Composer 2
- Git

## Repository Structure

- `src/`: package source code
- `src/Hooks/`: distributed Git hook scripts
- `src/Support/`: shared support classes
- `src/Util/`: reusable utility classes
- `tests/`: PHPUnit test suite
- `scripts/`: development-only tooling (e.g. `scripts/reset.php` for clean reinstalls)
- `docs/`: package documentation
- `.github/workflows/`: CI workflow definitions used by this repository

## Local Setup

Clone the repository and install dependencies:

```bash
git clone https://github.com/cihispano/git-hooks.git
cd git-hooks
composer install
```

The package exposes Composer scripts for the full local workflow:

```bash
composer analyze
composer sniff
composer cs
composer cs:fix
composer reset
composer sniff:fix
composer test
composer test:coverage
composer check:all
```

## GitFlow

This repository follows a task-based flow around `develop`:

1. Start from an updated `develop`.
2. Create a dedicated branch for your task or fix.
3. Make focused changes in that branch only.
4. Run the required validations locally.
5. Open a pull request or merge request targeting `develop`.
6. Merge only after review and successful CI.

Do not work directly on `develop` or `main` for feature work, fixes, or documentation changes.

Recommended branch naming examples:

- `feature/add-ci-workflow`
- `fix/hook-path-detection`
- `docs/update-contributing-guide`
- `chore/improve-qa-scripts`

Before starting a new branch:

```bash
git checkout develop
git pull
git checkout -b feature/your-task-name
```

When your work is ready:

```bash
git add .
git commit -S -m "type(scope): short description"
git push -u origin feature/your-task-name
```

Then open a pull request or merge request to `develop`.

## Validation Workflow

Use these commands while working on changes:

- `composer analyze`: run PHPStan static analysis
- `composer sniff`: run PHP_CodeSniffer checks
- `composer cs`: verify formatting with PHP CS Fixer
- `composer cs:fix`: apply formatting fixes
- `composer sniff:fix`: auto-fix PHPCS violations when possible
- `composer test`: run the PHPUnit test suite
- `composer test:coverage`: generate coverage output under `build/coverage/`
- `composer check:all`: run the main local quality gates in sequence

Before opening a pull request or merge request, run:

```bash
composer check:all
```

If you changed behavior, hook logic, or utility classes, also run:

```bash
composer test
```

## Git Hooks Workflow

This package installs three Git hooks into the consumer repository:

- `pre-commit`: validates staged PHP files with syntax checks, PHPStan, PHPCS, and PHP CS Fixer when those tools are available
- `commit-msg`: validates the first commit line against the Conventional Commits rules used by the project
- `pre-push`: runs PHPUnit and full-project PHPStan analysis when available

When developing this package itself, you can reinstall the packaged hooks with:

```bash
composer install-hooks
```

To remove them:

```bash
composer uninstall-hooks
```

Commit messages should follow Conventional Commits. See `docs/CONVENTIONAL_COMMITS.md` for the expected format and examples.

## Testing Guidance

Add or update tests when you change:

- public behavior in `src/`
- filesystem handling
- hook installation or removal logic
- console output behavior
- configuration constants or defaults

The current PHPUnit suite lives under `tests/Unit/` and mirrors the package structure where practical.

## Continuous Integration

This repository currently uses GitHub Actions to validate the package on pushes and pull requests, but the same branching and validation flow also maps cleanly to GitLab with merge requests and GitLab CI/CD. CI should mirror the same main checks used locally:

- Composer validation
- static analysis
- coding standards
- formatting checks
- automated tests

Keeping local commands and CI aligned helps avoid surprises when opening a pull request or merge request.

## Contribution Expectations

- Keep changes focused and easy to review
- Prefer small, isolated commits
- Update documentation when behavior or developer workflow changes
- Add or adjust tests for functional changes
- Avoid unrelated refactors in the same contribution

If you modify scripts, package configuration, or developer tooling, make sure the README and contributor-facing docs stay accurate.

## Pull Requests And Merge Requests

Before submitting a pull request or merge request:

1. Install dependencies with `composer install`.
2. Run `composer check:all`.
3. Run `composer test` if your changes affect behavior.
4. Update docs when commands, hooks, or workflows change.
5. Use a Conventional Commit message for your branch work.
6. Confirm your branch targets `develop`.

Thank you for helping improve the package.
