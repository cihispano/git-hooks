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

> **Note:** hooks are **not** auto-installed by default. The `post-install-cmd` /
> `post-update-cmd` events are registered but gated by `"auto_install": true` in
> `git-hooks.json` (see [Configuration](CONFIGURATION.md)). In consumer projects
> the events must also be wired in the project's own `composer.json`. Hooks are
> installed only when you run the Composer script explicitly or opt in.

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
4. Reuses an existing hook when its content is identical (no file is touched, no backup is written).
5. When an existing hook changed, first copies its current content to `<hook>.bak` in the same
   directory, prints that path in the warning, and only then overwrites it (`chmod 755`).

## Uninstall the hooks

```bash
composer uninstall-hooks
```

The uninstaller is **non-destructive**: it only removes the three hooks this package ships
(`pre-commit`, `commit-msg`, `pre-push`). Sample files (`*.sample`), hidden files, nested
directories, backups (`*.bak`) and any hook not shipped by this package (e.g. `post-checkout`
or hooks installed by another tool) are left untouched.

## Verify the installation

```bash
git rev-parse --git-path hooks
ls "$(git rev-parse --git-path hooks)"
```

The `ls` output should list at least `pre-commit`, `commit-msg`, and `pre-push`. After an
install that replaced a changed hook, its `<hook>.bak` backup is listed next to it; those
backups are yours to keep and are never removed by the uninstaller.

## Trust boundary

Before installing these hooks, understand **what they execute and with whose privileges**:

- On every `git commit`, `pre-commit` runs the tools present in the project's
  `vendor/bin` (`php-cs-fixer`, `phpcs`, `phpstan`) against the staged files, using the
  project's own configuration (`phpcs.xml(.dist)`, `phpstan.neon(.dist)`,
  `.php-cs-fixer(.dist).php`, `phpunit.xml(.dist)`) when present, falling back to the
  package defaults otherwise.
- On every `git push`, `pre-push` runs the project's PHPUnit suite and a full project
  PHPStan analysis.
- These tools execute **repository-controlled code** (configuration files and
  `vendor/bin/*` binaries) with **your user's privileges**, on every commit and push of
  any branch you check out.

Because of this, these hooks must be treated like any pre-commit CI: installing them in a
repository you do not trust is equivalent to letting that repository run code on your
machine. **Only install the hooks in repositories you and your team already trust.**

As a rule of thumb:

- The repo configuration wins over the package defaults. This is the intended behavior:
  the package brings sensible defaults, but your project is allowed to override them.
- A malicious `.php-cs-fixer.php` or `phpstan.neon` in a branch you check out can already
  achieve code execution through the tools. Reviewing changes to these files is part of
  the normal code review gate.

For the 0.1.0 line this is **documentation only**: there is no allowlist gate yet. A
project allowlist (`git-hooks.json`) to restrict which configs and binaries the hooks may
run is planned for future releases.

### `composer install-hooks` may override local hooks

The installer only overwrites a hook when its content **changed**. A local customization
you wrote on top of an installed hook is preserved as long as the package hook did not
change; after a package update the installed hook is refreshed.

Nothing is lost when that happens: the previous content is copied to `<hook>.bak`
(e.g. `.git/hooks/pre-commit.bak`) right before the overwrite, and the console warning
names that file. Re-apply your local edits from the backup after updates, or move them to
a wrapper hook so they survive every install.

Uninstalling afterwards keeps the backups as well — `composer uninstall-hooks` only
deletes the hooks listed in `Config::DEFAULT_HOOKS` (`pre-commit`, `commit-msg`,
`pre-push`).

## Local validation contract

The project's CI (GitHub Actions and GitLab CI) runs exactly the same gates you get
locally with the `composer` scripts. Before opening a merge request, replicate the CI
from a clean state:

```bash
composer reset
composer check:all
```

`composer reset` removes `vendor/` and QA caches (keeping `composer.lock` for
reproducibility) and reinstalls; `composer check:all` runs **PHPStan 10**, **PHPCS**,
**PHP CS Fixer**, and **PHPUnit**. In addition, CI validates that the distributed shell
hooks pass `shellcheck` and that the hook smoke tests executed (they are never skipped),
so the three gates are:

| Gate | Local | CI |
| ------ | ------- | ---- |
| Static analysis (PHPStan, level 10) | `composer analyze` | `composer analyze` |
| Standards (PHPCS + PHP CS Fixer) | `composer sniff` + `composer cs` | same |
| Tests (PHPUnit) | `composer test` | `composer test` |
| Hooks shell syntax + shellcheck | `sh -n src/Hooks/*` + `shellcheck` | job `hooks:` |
| Hook smoke tests (must run) | `vendor/bin/phpunit ... HooksSmokeTest.php` | explicit step |

## Skip validation (emergency only)

```bash
git commit --no-verify -m "hotfix"
```

Use this only for emergencies; skipping the checks lowers the quality gates.

## Refer

- Hook behavior: see [Usage](https://gitlab.com/cihispano.org/git-hooks#usage) in the README
- Commit rules: see [CONVENTIONAL_COMMITS.md](CONVENTIONAL_COMMITS.md)
