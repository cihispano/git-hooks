# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Fixed

- **Hook `php` calls no longer depend on word-splitting**:
  - `pre-commit` and `pre-push` now write the `-d xdebug.mode=off -d xdebug.log=` flags
    inline on every `php` invocation instead of expanding `$PHP_XDEBUG_FLAGS`. With the
    variable, `pre-commit` narrowing `IFS` to a single newline (SEC-002 filename
    protection) glued the whole flag list into one argument, so PHP printed
    `PHP: syntax error, unexpected TC_STRING in Unknown on line 7` before every tool run
    and the `-d` overrides never reached PHP.
  - The now unneeded `shellcheck disable=SC2086` annotations were removed.
  - `commit-msg` does not invoke `php` and was left unchanged.

## [0.3.0] - 2026-09-25

### Added

- **Hook backups on install**:
  - `installHook()` now copies the current content of a hook it is about to replace to
    `<hook>.bak` (same directory, suffix defined by the new `Config::BACKUP_SUFFIX`) and
    prints that path in the overwrite warning, so local customizations are never lost
    silently.
  - Added the `HookManager::backupHook()` helper: when the backup copy fails, the install
    aborts instead of overwriting the existing hook.
  - Backups are refreshed on every overwrite and are never touched by `composer uninstall-hooks`.
  - Added regression tests: `testInstallBacksUpModifiedHookBeforeOverwriting`,
    `testInstallHookBacksUpExistingHookBeforeOverwriting`,
    `testInstallHookAbortsWhenTheBackupCannotBeWritten`,
    `testInstallHookRefreshesTheBackupOnEachOverwrite` and `testUninstallKeepsHookBackups`.
- **Documentation**:
  - Documented the backup and non-destructive behavior in `README.md` (features, install
    note, uninstall scope, hook customization) and `docs/INSTALLATION.md` (installer steps,
    uninstall guarantees, recovery from `<hook>.bak`).

### Changed

- **QA tools are now runtime dependencies**:
  - Moved `phpstan/phpstan`, `friendsofphp/php-cs-fixer` and `squizlabs/php_codesniffer`
    from `require-dev` to `require` (constraints unchanged: `^2`, `^3.90`, `^4.0`), so a
    project that only declares `cihispano/git-hooks` in its own `require-dev` gets their
    binaries in `vendor/bin` and the hooks can execute them. Before this change the
    consumer's `vendor/bin` did not contain them at all, and the hooks silently skipped
    those steps.
  - `phpunit/phpunit`, `mikey179/vfsstream`, `codeigniter/coding-standard` and
    `slevomat/coding-standard` remain dev-only.
  - `composer.lock` re-resolved: the three tools and their transitive dependencies moved
    from `packages-dev` to `packages`, with no package changing version.
  - Verified against a fresh CodeIgniter 4 project requiring only this package:
    `composer cs`, `composer sniff`, `composer analyze` and the `pre-commit` hook all run
    and detect violations, and `composer install --no-dev` removes the package and the
    three tools.
- **Documentation**:
  - Documented in `README.md` and `docs/INSTALLATION.md` that the package must always be
    installed with `composer require --dev` (never as a production dependency) and why.
  - The *Integration with Existing Projects* section no longer asks consumers to
    `composer require --dev` PHPStan or PHP CS Fixer: both ship with this package.
  - Noted in `docs/CONFIGURATION.md` which binaries come with the package and which ones
    the project must provide (`phpunit`).

### Fixed

- **Non-destructive uninstall**:
  - `listInstalledHooks()` now selects entries explicitly against `Config::DEFAULT_HOOKS`
    instead of the previous hidden/`.sample` heuristic, so `composer uninstall-hooks` no
    longer deletes files the package did not install (foreign hooks such as
    `post-checkout`, hidden files, nested directories or `.bak` backups).
  - Added regression tests: `testUninstallKeepsForeignHooksNotOwnedByThePackage`,
    `testUninstallKeepsHooksDirectoryWhenOnlyForeignHooksExist` and
    `testListInstalledHooksIgnoresForeignHooksAndBackups`.

## [0.2.0] - 2024-03-30

### Added

- **Project Configuration (`git-hooks.json`)**:
  - Added `src/Config/ProjectConfig.php` to load optional project configuration with strict type validation (`auto_install`, `build_dir`).
  - Added `composer init-hooks` command (`ComposerScripts::initHooks`) to generate default `git-hooks.json`.
  - Added unit test suite `tests/Unit/Config/ProjectConfigTest.php`.
- **Architecture & Collaborators**:
  - Added `src/Util/Filesystem.php` as an abstraction for native filesystem operations.
  - Added `src/Installer/HookManager.php` to handle hook listing, copying, removal, and build directory creation.
  - Added unit test suites `tests/Unit/Installer/HookManagerTest.php` and `tests/Unit/Util/FilesystemTest.php`.
- **Security & Smoke Tests**:
  - Added `tests/Unit/HooksSmokeTest.php` to test hook execution on staged files containing spaces and shell metacharacters (SEC-002).
  - Documented trust boundary guidelines (SEC-001) across `README.md`, `docs/INSTALLATION.md`, and `docs/CONFIGURATION.md`.
- **Tooling & Packaging**:
  - Added `.gitattributes` with `export-ignore` to omit development directories (`tests/`, `docs/`, `scripts/`, CI configs) from published Composer archives.
  - Added `composer reset` script (`scripts/reset.php`) for clean, reproducible local verification.
  - Added CI shell validation jobs (`sh -n` and `shellcheck`) in GitHub Actions and GitLab CI.
  - Added CodeIgniter4 coding standard as a dev dependency, split rules between PHP CS Fixer and PHP_CodeSniffer, and added Slevomat sniffs.
  - Added GitLab issue and merge request templates under `.gitlab/`.
  - Added `GitRepository::resolveHooksDir()` for git-first hook location resolution with fallbacks.

### Changed

- **Refactored `ComposerScripts`**:
  - Reduced `ComposerScripts` from 466 to 203 lines, converting it into a thin orchestrator facade.
  - Replaced `str_contains($entry, '.')` heuristic filter with the explicit `Config::DEFAULT_HOOKS` list.
  - Removed private method testing via `ReflectionMethod` in `ComposerScriptsTest`.
- **Composer Event Integration**:
  - `post-install-cmd` and `post-update-cmd` in `composer.json` are now gated by `"auto_install": true` in `git-hooks.json`.
  - `ComposerScripts::ensureBuildDirectory()` now honors custom `build_dir` settings.
- **Shell Hooks Security Hardening**:
  - `pre-commit`: Staged files collected using `set -f` and newline `IFS` to safely handle special character filenames (SEC-002); supports initial commit repositories without `HEAD`.
  - `pre-commit`/`pre-push`: Project root resolved dynamically using `dirname`/`cd` instead of `$0` in `php -r` strings (SEC-003).
  - `commit-msg`/`pre-commit`/`pre-push`: Replaced `echo -e` with POSIX-compliant `printf` (SEC-004).
- **Tooling & Engine Upgrades**:
  - Upgraded PHPStan from 1.x to 2.x (Level 10).
  - Replaced Termwind dependency with native ANSI console output logger (`ConsoleLogger`).
  - Expanded runtime compatibility to PHP 8.1 through 8.4.
  - Renamed style-fix scripts to `cs:fix` and `sniff:fix`.

### Fixed

- Safely abort hook installation with a warning when target directory is not a Git repository instead of installing orphan hooks.
- Fixed header badge color contrast in `ConsoleLogger` (`HEADER_FOREGROUND`) for improved light/dark terminal theme readability.
- Fixed hook source directory resolution when used as a vendor dependency (`dirname(__DIR__)`).
- Switched CI `hooks:shellcheck` job to cached `php:8.1-cli` image.

---

## [0.1.0] - 2024-01-15

### Added

- Initial development release.
- Basic pre-commit hook structure and PHP syntax validation.
- PHP 8.1+ baseline requirements.
- Core configuration files (`.editorconfig`, `.gitignore`, `CODE_OF_CONDUCT.md`, `CHANGELOG.md`).
- Quality assurance configuration (`.php-cs-fixer.dist.php`, `phpcs.xml.dist`, `phpstan.neon.dist`, `phpunit.xml.dist`).
- PHPUnit test suite for core utilities.
- Initial Composer scripts for `test` and `test:coverage`.
