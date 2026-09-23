# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
