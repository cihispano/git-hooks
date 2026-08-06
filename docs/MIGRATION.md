# Migration

Notes for moving a consumer project to the 0.1.0 line of this package.

> Status: the 0.1.0 restructuring is still in progress. This page will be completed before the release (task "prepare 0.1.0"). It currently describes the intended migration for the changes that are already merged (hooks PHP rewrite is planned, not yet shipped).

## What changed

- **Runtime:** PHPStan upgraded to 2.x with analysis level 10 (changed).
- **Console output:** Termwind removed; output is native ANSI and respects `NO_COLOR` (changed).
- **Hooks:** from 1.x the hooks were shell scripts; in 0.1.0 the plan is to ship PHP-based hooks (bootstrap PHP scripts that delegate to package classes). **Not yet shipped** — until it lands, hooks remain the 1.x shell scripts.
- **Install:** hooks are not auto-installed (opt-in installation) once the config change lands.

## Migration steps for a consumer

1. Update the dependency:

   ```bash
   composer update cihispano/git-hooks
   ```

2. If hooks are not installed (fresh install), install them:

   ```bash
   composer install-hooks
   ```

3. If hooks are installed from a previous version, **reinstall** to bring the hook scripts up to date:

   ```bash
   composer uninstall-hooks
   composer install-hooks
   ```

4. (Planned, when config lands) create `git-hooks.json` if you want opt-in auto-installation or tool toggles. See [Configuration](CONFIGURATION.md).

## Behavior notes

- With the planned opt-in change, hooks will **not** be installed automatically on `composer install` / `composer update`; run `composer install-hooks`.
- `commit-msg` rules keep the same defaults (Conventional Commits, 10–100 chars) unless overridden via `git-hooks.json`.
- The build directory used for cache (`build/`) is created by the installer in the project root.