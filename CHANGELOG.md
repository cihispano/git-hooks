# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Added `.gitattributes` with `export-ignore` so the published Composer artifact
  ships only runtime files (`.php-cs-fixer.dist.php`, `phpcs.xml.dist`,
  `phpstan.neon.dist`, `phpunit.xml.dist`, `src/`, hooks) and drops `tests/`,
  `docs/`, `scripts/`, CI directories and dev-only metadata.
- Added CI jobs that validate the distributed shell hooks: `sh -n` plus
  `shellcheck` (zero violations) in both GitHub Actions and GitLab CI.
- The `HooksSmokeTest` suite now runs explicitly in both pipelines with
  `--fail-on-skipped`, so the real packaged hooks are always exercised in CI.
- Documented the **trust boundary** (SEC-001) in `docs/INSTALLATION.md`, `docs/CONFIGURATION.md`
  and `README.md`: installed hooks execute the repository's QA configs and `vendor/bin` tools
  with the developer's privileges on every `commit`/`push`, so consumers should only install
  them in repositories they trust. For 0.1.x this is documentation only — a `git-hooks.json`
  tool/config allowlist is planned (README Roadmap updated).
- Added `tests/Unit/HooksSmokeTest.php`: a shell smoke test that installs the real packaged
  `pre-commit` hook into a temporary Git repository, stages PHP files whose names contain
  spaces and shell metacharacters, and asserts every QA tool receives each filename as a
  single intact argument (SEC-002).
- Added a `composer reset` command (`scripts/reset.php`) that removes dependencies and QA
  cache artifacts (`vendor/`, `build/`, `.php-cs-fixer.cache`, `.phpunit.result.cache`),
  then reinstalls from scratch keeping `composer.lock` for reproducible validation — run
  it before validating every feature/fix/bug.
- Added CodeIgniter4 coding standard as a dev dependency.
- Added coding standard ownership split: PHP CS Fixer (CodeIgniter coding standard) owns
  all mechanically fixable rules; PHP_CodeSniffer only keeps non-auto-fixable rules.
- Added curated Slevomat sniffs to the PHPCS ruleset: implicit array creation, constructor
  property promotion, and namespace/root mapping.
- Added GitLab issue templates (Task/Chore, Feature Request, Bug Report) under `.gitlab/issue_templates/`.
- Added GitLab merge request template under `.gitlab/merge_request_templates/`.
- Added `docs/INSTALLATION.md` with install, hook install/uninstall, verification, and emergency skip guidance.
- Added `docs/CONFIGURATION.md` documenting current behavior: tool config fallbacks, staged-files scope, and commit-msg rules (planned `git-hooks.json` included).
- Added `GitRepository::resolveHooksDir()` helper resolving the hooks directory with git-first
  detection (`git -C <dir> rev-parse --git-path hooks`) and a manual fallback that parses `.git`
  directory, `.git` file (`gitdir:` entry), and linked-worktree `commondir` files.
- Expanded unit-test coverage for `PathBuilder::canonicalize()` (stream wrappers, Windows drive
  prefixes, `.`/`..` segments in absolute and relative paths) and `FileComparator` failure paths
  (unreadable files and failed hash calculation), raising the class coverage ratio to 62.5% (5/8).
- Updated `README.md` installation docs: hooks are not auto-installed on `composer install`/`update`
  (run `composer install-hooks` explicitly), and the hooks destination is resolved git-first,
  honoring worktrees and `core.hooksPath`.
- Clarified package documentation and templates: fixed malformed PHPDoc in `ComposerScripts`,
  made `postInstall`/`postUpdate` docblocks reflect that they are not wired to `composer.json`,
  reported the resolved hooks directory in the installer output, documented the legacy/unused
  `Config` constants accurately, aligned the README pre-commit step order and output with the
  real hook scripts, completed the README composer scripts list (`clear:cache`, `style`), added
  a README Roadmap for planned features, and fixed the verify command and template structure in
  the install docs and bug-report template.
- Added review hardening: `GitRepository::resolveHooksDir()` results are cached per base path so
  a single install/uninstall run invokes the git binary at most once, `GitRepository::isGitRepository()`
  drives a console warning when hooks are installed outside a Git repository, `PathBuilder::canonicalize()`
  now resolves non-empty paths that collapse to nothing to the current directory ('.'), and the
  git-first resolution tests run against a simulated `git` binary so class coverage no longer
  depends on a real git being installed.
- Initial development version
- Basic pre-commit hook structure
- PHP syntax validation
- Set PHP 8.1 and above as required
- Created .editorconfig
- Created composer.json for this library
- Created .gitignore file
- Created CODE_OF_CONDUCT.md file
- Created CHANGELOG.md file
- Created .php-cs-fixer.dist.php file
- Created phpunit.xml.dist file
- Created phpstan.neon.dist file
- Added PHPUnit as a development dependency
- Added unit tests for core classes and utility helpers
- Added Composer scripts for `test` and `test:coverage`

- Added `tests/Unit/Config/ProjectConfigTest.php` covering defaults, valid/invalid
  JSON, type validation, unknown-key tolerance and default-file generation.
- Added `src/Config/ProjectConfig.php`: loads the optional `git-hooks.json` from the
  project root with strict validation (invalid JSON or wrong value types fail loudly,
  no silent fallback), exposes `auto_install` and `build_dir` defaults, and can
  generate the default file (`writeDefault()`).
- Added a `composer init-hooks` script (`ComposerScripts::initHooks`) that generates
  a default `git-hooks.json` in the current directory.

### Changed

- Registered `post-install-cmd` / `post-update-cmd` in `composer.json`, gated by
  `"auto_install": true` in `git-hooks.json`: hooks are never installed implicitly
  unless the project opts in; `composer install-hooks` remains the explicit path.
- `ComposerScripts::ensureBuildDirectory()` now honors the `build_dir` setting from
  `git-hooks.json` instead of the hardcoded `build` constant.
- Documented the `git-hooks.json` schema in `docs/CONFIGURATION.md` and `README.md`,
  including the note that composer events must be wired in the consumer project's own
  `composer.json` (scripts do not propagate from dependencies).
- Updated the README hook output examples (`✔`/`✘` icons, indentation) to match the
  real packaged hooks.
- Documented the local validation contract in `docs/INSTALLATION.md`: `composer reset &&
  composer check:all` replicates exactly what CI runs (PHPStan 10, PHPCS, PHP CS Fixer, PHPUnit),
  plus the shell-hook shellcheck/smoke gates.
- Hardened the distributed shell hooks per the security review:
  - `pre-commit`: staged files are collected with `set -f` and a newline `IFS` so filenames
    containing spaces or shell metacharacters are never globbed or word-split and reach the
    QA tools as single arguments (SEC-002); the staged-file listing also works on a
    repository without an initial commit (no `HEAD` yet).
  - `pre-commit`/`pre-push`: the project root is now resolved with `dirname`/`cd` instead of
    interpolating `$0` into a `php -r` string, so checkout paths containing single quotes no
    longer break (SEC-003).
  - `commit-msg`/`pre-commit`/`pre-push`: `echo -e` replaced with `printf` for POSIX
    portability (SEC-004).
  - All three hooks pass `shellcheck` with zero violations.
- Switched the PHP CS Fixer config to the CodeIgniter coding standard ruleset, keeping the CiHispano header via overrides.
- Fixed the install command when the package is used as a dependency: the hooks source directory
  is now always resolved from the installed package root (`dirname(__DIR__)`) instead of the
  consumer project root, which previously aborted the install with "Hooks source directory does
  not exist".
- `ComposerScripts::getGitHooksDir()` now delegates to `GitRepository::resolveHooksDir()`, so
  destination resolution is always git-first (worktrees, `core.hooksPath`, submodules) with a
  manual fallback, regardless of how the install was invoked.
- Added `PathBuilder::canonicalize()` to resolve `.` and `..` segments in relative git paths
  without requiring the file to exist.
- Replaced the Termwind runtime dependency with native ANSI console output that respects `NO_COLOR`.
- Upgraded PHPStan from 1.x to 2.x and set the analysis level to 10.
- Re-styled source files to the CodeIgniter coding standard (PHP CS Fixer).
- Expanded runtime compatibility to PHP 8.1 through 8.4.
- Aligned Composer dependency constraints for cross-version support (Termwind 1.x, PHPUnit 10.x, PHPStan 1.x).
- Updated local and CI compatibility policy to resolve dependencies with `config.platform.php=8.1.0`.
- Updated GitHub Actions and GitLab CI to validate tests and static analysis on PHP 8.1, 8.2, 8.3, and 8.4.
- Kept coding-style checks on PHP 8.1 baseline to avoid version-dependent formatter output.
- Removed typed class constants to maintain source compatibility with PHP 8.1.
- Expanded unit-test coverage for utility/config classes, including private empty constructors.
- Updated the documented QA workflow to include PHPUnit
- Renamed style-fix scripts to the `cs:fix` and `sniff:fix` convention.
