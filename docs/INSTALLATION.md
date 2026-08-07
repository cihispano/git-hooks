# Installation

Guide for installing the Git Hooks package into a CodeIgniter 4 project.

## Requirements

- PHP 8.1 to 8.4
- Git 2.0 or higher
- Composer 2.0 or higher

## Install the package

Install the package as a development dependency in your project:

```bash
composer require --dev cihispano/git-hooks
```

> **Note for the current 0.1.0 line:** hooks are **not** auto-installed on `composer install` / `composer update`. The `post-install-cmd` / `post-update-cmd` wiring is not registered in the package's `composer.json`. Hooks are installed only when you run the Composer script explicitly.

## Install the hooks

From your project root:

```bash
composer install-hooks
```

The installer:

1. Resolves the Git hooks directory using git-first resolution (`git -C <dir> rev-parse --git-path hooks`),
   with a manual `.git` fallback that also supports worktrees (`gitdir:`/`commondir`) and `core.hooksPath`.
2. Copies the packaged hooks `pre-commit`, `commit-msg`, and `pre-push`.
3. Marks them executable (skipped on Windows).
4. Reuses an existing hook when its content is identical, and overwrites it (`chmod 755`) when it changed.

## Uninstall the hooks

```bash
composer uninstall-hooks
```

Only the packaged hooks are removed. Sample files (`*.sample`) and hidden files are left untouched.

## Verify the installation

```bash
git rev-parse --git-path hooks
ls "$(git rev-parse --git-path hooks)"
```

The `ls` output should list at least `pre-commit`, `commit-msg`, and `pre-push`.

## Skip validation (emergency only)

```bash
git commit --no-verify -m "hotfix"
```

Use this only for emergencies; skipping the checks lowers the quality gates.

## Refer

- Hook behavior: see [Usage](https://gitlab.com/cihispano.org/git-hooks#usage) in the README
- Commit rules: see [CONVENTIONAL_COMMITS.md](CONVENTIONAL_COMMITS.md)
