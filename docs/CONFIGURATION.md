# Configuration

This page describes how the package behaves today and how it is configured.

## Project configuration (`git-hooks.json`)

An optional `git-hooks.json` file in the **project root** lets you control installer behavior:

```json
{
    "auto_install": false,
    "build_dir": "build"
}
```

| Key            | Type     | Default | Description                                        |
| -------------- | -------- | ------- | -------------------------------------------------- |
| `auto_install` | `bool`   | `false` | Auto-install hooks on `composer install`/`update`. |
| `build_dir`    | `string` | `build` | QA cache directory (PHPStan, CS Fixer, PHPUnit).   |

- Without the file (or with defaults), hooks are **not** auto-installed: use `composer install-hooks` explicitly.
- Unknown keys are ignored (forward compatibility). Invalid JSON or wrong value types fail loudly — there is no silent fallback.
- `composer init-hooks` generates the file above with defaults.

To enable automatic installation in a consumer project, set `"auto_install": true`
and wire the composer events (composer scripts do not propagate from dependencies):

```json
{
    "scripts": {
        "post-install-cmd": "CiHispano\\ComposerScripts::postInstall",
        "post-update-cmd": "CiHispano\\ComposerScripts::postUpdate"
    }
}
```

## Tool discovery and config fallbacks

Each hook looks for the tool's configuration in the **target project** first, and
falls back to the package-shared defaults only when none exists in the project root:

| Tool              | Priority                                                             | Package fallback                    |
| ----------------- | -------------------------------------------------------------------- | ----------------------------------- |
| PHP CS Fixer      | `.php-cs-fixer.php` → `.php-cs-fixer.dist.php`                       | package `.php-cs-fixer.dist.php`    |
| PHP_CodeSniffer   | `phpcs.xml` → `phpcs.xml.dist`                                       | package `phpcs.xml.dist`            |
| PHPStan           | `phpstan.neon` → `phpstan.neon.dist`                                 | package `phpstan.neon.dist`         |
| PHPUnit           | `phpunit.xml` → `phpunit.xml.dist`                                   | package `phpunit.xml.dist`          |

Tools are executed only when the corresponding binary exists in the project's
`vendor/bin` (`php-cs-fixer`, `phpcs`, `phpstan`, `phpunit`). Missing tools are
skipped with a warning instead of blocking the commit or push.

`php-cs-fixer`, `phpcs` and `phpstan` are **installed with this package** (they are its
runtime dependencies), so a project only has to add `cihispano/git-hooks` to its own
`require-dev` to get them. `phpunit` is the exception: it stays a dev dependency of the
package and must be provided by the project itself if `pre-push` is to run the suite.

## Staged-files scope

- `pre-commit` only inspects files staged for the commit
  (`git diff --cached --name-only --diff-filter=ACMR`, filtered to `*.php`).
- The whole staged set reaches each tool in **one call per commit**: `php-cs-fixer`,
  `phpcs` and `phpstan` receive every staged file as a single invocation (3 calls
  instead of one per file), and PHPStan analyses them together so cross-file types
  resolve and every offending file is reported in one pass. `php -l` is the
  exception — the CLI accepts a single path per invocation, so lint runs once per file.
- Files staged but no longer present in the worktree are skipped, so a tool is never
  invoked with an empty path list.
- `pre-push` runs a full-project analysis (PHPUnit on the whole suite and PHPStan over the project root).

## Commit message validation (commit-msg)

Currently hardcoded in the hook:

- Conventional Commits format: `type(scope): description`.
- First line between 10 and 100 characters.
- Allowed types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `perf`, `ci`, `build`, `revert`.

See [CONVENTIONAL_COMMITS.md](CONVENTIONAL_COMMITS.md) for the full rules.

## Skipping hooks

See [Installation](INSTALLATION.md) — `git commit --no-verify`.

## Future: extended `git-hooks.json`

Planned project-level keys (not available yet):

- tool toggles (`phpstan`, `phpcs`, `php_cs_fixer`, `phpunit`)
- `commit_msg` overrides (`min_length`, `max_length`, `types`)
- a **tool/config allowlist**: the set of project config files and `vendor/bin` binaries
  the hooks may execute, closing the [trust boundary](INSTALLATION.md#trust-boundary)
  (SEC-001) with an opt-in gate instead of documentation only.

## Trust boundary (current behavior)

Today the hooks run, on every `commit`/`push`, the repository's configuration files and
`vendor/bin` tools with the developer's user privileges — the repository config takes
priority over the package defaults. This is by design and is **documented only** for
now; see [Installation — Trust boundary](INSTALLATION.md#trust-boundary) for the
full model and the planned `git-hooks.json` allowlist.
