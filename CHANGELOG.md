# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Added GitLab issue templates (Task/Chore, Feature Request, Bug Report) under `.gitlab/issue_templates/`.

### Changed

- Replaced the Termwind runtime dependency with native ANSI console output that respects `NO_COLOR`.
- Upgraded PHPStan from 1.x to 2.x and set the analysis level to 10.
- Expanded runtime compatibility to PHP 8.1 through 8.4.
- Aligned Composer dependency constraints for cross-version support (Termwind 1.x, PHPUnit 10.x, PHPStan 1.x).
- Updated local and CI compatibility policy to resolve dependencies with `config.platform.php=8.1.0`.
- Updated GitHub Actions and GitLab CI to validate tests and static analysis on PHP 8.1, 8.2, 8.3, and 8.4.
- Kept coding-style checks on PHP 8.1 baseline to avoid version-dependent formatter output.
- Removed typed class constants to maintain source compatibility with PHP 8.1.
- Expanded unit-test coverage for utility/config classes, including private empty constructors.

## [1.0.0] - 2026-03-20

### Added

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

### Changed

- Updated the documented QA workflow to include PHPUnit
- Renamed style-fix scripts to the `cs:fix` and `sniff:fix` convention
