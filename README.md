# Git Hooks for CodeIgniter 4

[![Latest Version](https://img.shields.io/packagist/v/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![Total Downloads](https://img.shields.io/packagist/dt/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![License](https://img.shields.io/packagist/l/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![PHP Version](https://img.shields.io/packagist/php-v/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)

Automated Git Hooks for CodeIgniter 4 projects. This package ensures your code
meets the highest quality standards by running automated checks before every commit.

## ✨ Features

- 🔍 **PHP Syntax Check** - Validates PHP syntax (lint) on all staged files.
- 📊 **PHPStan Analysis** - Performs deep static analysis to find potential bugs (Level 10).
- 👃 **PHP_CodeSniffer** - Validates PSR-12 compliance and coding standards.
- 🎨 **PHP CS Fixer** - Automatically formats code to follow defined styles.
- 🎯 **Smart Scope** - Only analyzes staged files to keep your workflow fast.
- ⚡ **One Call Per Tool** - All staged files are checked in a single invocation per tool
  (PHPStan analyses them together), so commit time stays flat as the changeset grows.
- 🛡️ **Non-destructive** - `install-hooks` backs up a changed hook to `<hook>.bak` before
  overwriting it, and `uninstall-hooks` only removes the hooks this package ships.
- 🌈 **Native ANSI Output** - Beautiful, colorful console feedback with icons (respects `NO_COLOR`).
- 🔧 **Zero Config** - Works out of the box with sensible defaults for CI4.

## 🗺️ Roadmap

Planned, not yet available:

- **Extended `git-hooks.json`** - Tool toggles (`phpstan`, `phpcs`, `php_cs_fixer`, `phpunit`),
  `commit_msg` overrides (`min_length`, `max_length`, `types`), and a **tool/config
  allowlist** to decide which project configuration files and `vendor/bin` binaries the
  hooks may run (trust gate for SEC-001).
- **PHP-based hooks** - Replace the current shell scripts with PHP bootstrap scripts that
  delegate to the package classes.
- **`NO_COLOR` in shell hooks** - Make the installed shell hooks honor `NO_COLOR` (today it
  is respected by the installer/uninstaller console output, not by the hook scripts).

See [`docs/CONFIGURATION.md`](docs/CONFIGURATION.md) for the configuration schema and defaults.

## 📋 Requirements

- **PHP 8.1** to **8.4**
- **Git 2.0** or higher
- **Composer 2.0** or higher

## Compatibility policy

- Runtime compatibility: the package is supported on PHP 8.1 through 8.4.
- Development dependency resolution: `composer.lock` is generated with `config.platform.php=8.1.0`.
- CI validation: tests and static analysis run on PHP 8.1, 8.2, 8.3, and 8.4 in both GitHub Actions and GitLab CI.
- Coding style checks (`composer sniff` and `composer cs`) run on PHP 8.1 to keep formatter and sniffer output aligne
  with the minimum supported runtime.
- When running `composer cs` on PHP newer than 8.1, PHP CS Fixer may show a warning. This is expected; use PHP 8.1
  locally if you want warning-free style checks.

## 📦 Installation

Install the package as a development dependency:

```bash
composer require --dev cihispano/git-hooks
```

> ⚠️ **Always `--dev`, never as a production dependency.**
> This package is a development tool: it installs Git hooks that run the QA toolchain
> before every `commit`/`push`, and it now pulls that toolchain (`phpstan/phpstan`,
> `friendsofphp/php-cs-fixer`, `squizlabs/php_codesniffer`) as **runtime** dependencies so
> the hooks always find their binaries in the consumer's `vendor/bin`. Moving those tools
> to `require` is also why the package itself must stay in `require-dev`: putting it in
> `require` would ship the hooks *and* the three QA tools (plus their transitive
> dependencies) to every production install, and `composer install --no-dev` would no
> longer be able to exclude them. With `--dev`, a production `composer install --no-dev`
> installs neither the hooks nor the tools.

Then install the hooks into the current repository:

```bash
composer install-hooks
```

> The hooks are **not** installed automatically on `composer install`/`update`; run
> `composer install-hooks` once per repository (and again after updating the package)
> to install or refresh them. Use `composer uninstall-hooks` to remove them.
>
> Both operations are **non-destructive**: a hook whose content changed is copied to
> `<hook>.bak` before being overwritten, and only the hooks shipped by this package are
> ever removed.

### Install Location

Hooks are copied to the repository's hooks directory, resolved in the following order:

1. `git -C <directory> rev-parse --git-path hooks` — always preferred, so git decides
   the location for worktrees, submodules, and `core.hooksPath`.
2. Manual fallback that parses `.git` (directory or `gitdir:` file) and linked-worktree
   `commondir` files.

The hooks source is always read from the installed package root, so the install works
identically whether the package lives at the project root or under `vendor/`.

## 🚀 Usage

Once installed, the hooks work automatically.

### `pre-commit`

Every time you commit code, the `pre-commit` hook will:

1. ✅ Check PHP syntax on all staged `.php` files
2. ✅ Verify formatting with PHP CS Fixer (if installed)
3. ✅ Check PSR-12 compliance with PHP_CodeSniffer (if installed)
4. ✅ Run PHPStan analysis (if installed)

### `commit-msg`

The `commit-msg` hook validates the first line of your commit message:

1. ✅ Minimum 10 characters
2. ✅ Maximum 100 characters
3. ✅ Conventional Commits format

See [`docs/CONVENTIONAL_COMMITS.md`](docs/CONVENTIONAL_COMMITS.md) for examples and guidance.

### `pre-push`

Before pushing, the `pre-push` hook runs a full-project validation:

1. ✅ PHPUnit, if available in the target project
2. ✅ Full PHPStan analysis

This repository ships its own PHPUnit suite for the package itself. The installed `pre-push` hook is also
designed for consumer projects and will run PHPUnit there when it is available. If PHPUnit is not installed
in the target project, the hook skips that step and continues with the remaining checks.

### Example Output

```bash
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
   CiHispano: Running Centralized Quality Checks
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  [1/4] Checking PHP syntax...
✔ Syntax is valid

  [2/4] Validating code style...
✔ Coding style verified

  [3/4] Sniffing code standards...
✔ Standards check passed

  [4/4] Running static analysis...
✔ Static analysis completed

  ✔ All checks passed! Proceeding with commit.
```

### When a Check Fails

If any check fails, the commit will be blocked:

```bash
  [2/4] Validating code style...
✘ Style violations found.
```

Fix the issues and try again:

```bash
# Fix code style automatically
composer cs:fix

# Stage the fixed files
git add .

# Try committing again
git commit -S -m "Your message"
```

## 🛠️ Configuration

### Project configuration (`git-hooks.json`)

An optional `git-hooks.json` file in the project root controls installer behavior:

```json
{
    "auto_install": false,
    "build_dir": "build"
}
```

- `auto_install` (`bool`, default `false`): install hooks automatically on
  `composer install`/`update`. Without it (or with `false`), use
  `composer install-hooks` explicitly.
- `build_dir` (`string`, default `build`): QA cache directory (PHPStan, PHP CS Fixer, PHPUnit).
- `composer init-hooks` generates the file with defaults.
- Invalid JSON or wrong value types fail loudly — no silent fallback.

> In consumer projects, automatic installation requires wiring the composer events in the
> project's own `composer.json` (scripts do not propagate from dependencies):
> `"post-install-cmd": "CiHispano\\ComposerScripts::postInstall"` (and `post-update-cmd`).

### Skipping Hooks (Not Recommended)

If you need to commit without running the hooks:

```bash
git commit --no-verify -m "Emergency fix"
```

⚠️ **Warning:** Only use this in emergencies. Your code should always pass the quality checks.

### Uninstalling Hooks

To remove the Git hooks:

```bash
composer uninstall-hooks
```

The uninstaller only removes the hooks this package ships (`pre-commit`, `commit-msg` and
`pre-push`). Everything else in the hooks directory is left untouched: hooks you or another
tool installed (`post-checkout`, `pre-rebase`, ...), `*.sample` files, hidden files, nested
directories and the `<hook>.bak` backups written on install.

### Customizing the Hooks

The hooks are located in your repository's hooks directory (usually `.git/hooks/`, but
git-first resolution honors `core.hooksPath` and worktrees) after installation. You can
modify them if needed, but keep in mind they will be overwritten when you update the package.

Before overwriting a hook whose content differs from the package version, the installer
copies the current file to `<hook>.bak` in the same directory
(e.g. `.git/hooks/pre-commit.bak`) and prints that path in the warning. Your local edits
are therefore never lost silently; restore them with:

```bash
cp "$(git rev-parse --git-path hooks)/pre-commit.bak" "$(git rev-parse --git-path hooks)/pre-commit"
```

Hooks that are already identical are skipped, so no backup is written for them.

## 🔒 Trust boundary

The hooks execute **repository-controlled code** with **your user's privileges**:

- `pre-commit` runs the QA tools from `vendor/bin` (`php-cs-fixer`, `phpcs`, `phpstan`)
  against the staged files, using the project's own configs when present
  (`.php-cs-fixer(.dist).php`, `phpcs.xml(.dist)`, `phpstan.neon(.dist)`, `phpunit.xml(.dist)`).
- `pre-push` runs the project's PHPUnit suite and a full PHPStan analysis.

A malicious config file or `vendor/bin` tool in a checked-out branch can therefore run
code on every developer's machine at the next `commit`/`push`. Install these hooks **only
in repositories you already trust** — the repo config overrides the package defaults by
design, and the trust model is documented in
[`docs/INSTALLATION.md`](docs/INSTALLATION.md#trust-boundary).

At present this is documentation only; a `git-hooks.json` allowlist to gate which
configs and binaries the hooks may run is planned (see [Roadmap](#%EF%B8%8F-roadmap)).

## 📊 Composer Scripts

This package provides the following Composer scripts:

```json
{
    "scripts": {
        "install-hooks": "CiHispano\\ComposerScripts::install",
        "uninstall-hooks": "CiHispano\\ComposerScripts::uninstall",
        "analyze": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/phpstan analyze --verbose",
        "check:all": [
            "@analyze",
            "@sniff",
            "@cs",
            "@test"
        ],
        "clear:cache": [
            "@php -r \"if (file_exists('build/.php-cs-fixer.cache')) unlink('build/.php-cs-fixer.cache');\"",
            "@php -r \"if (file_exists('build/phpstan.cache')) unlink('build/phpstan.cache');\"",
            "@php -r \"echo 'Cache cleared successfully' . PHP_EOL;\""
        ],
        "cs": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/php-cs-fixer fix --ansi --verbose --dry-run --diff",
        "cs:fix": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/php-cs-fixer fix --ansi --verbose --diff",
        "reset": [
            "@php scripts/reset.php",
            "@composer install --no-interaction --optimize-autoloader"
        ],
        "sniff": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/phpcs",
        "sniff:fix": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/phpcbf",
        "style": "@cs:fix",
        "test": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/phpunit --configuration phpunit.xml.dist --colors=always",
        "test:coverage": "@php -d xdebug.mode=coverage -d xdebug.start_with_request=yes vendor/bin/phpunit --configuration phpunit.xml.dist --colors=always --coverage-text --coverage-html build/coverage"
    }
}
```

Add these to your `composer.json` to access them easily:

```bash
composer install-hooks
composer uninstall-hooks
composer analyze
composer check:all
composer clear:cache
composer sniff
composer cs
composer cs:fix
composer sniff:fix
composer style
composer reset
composer test
composer test:coverage
```

> `composer reset` removes installed dependencies and QA cache artifacts (`vendor/`,
> `build/`, `.php-cs-fixer.cache`, `.phpunit.result.cache`) and reinstalls everything
> from scratch, keeping `composer.lock` for reproducible validation. Run it before
> validating each feature, fix, or bug.

## 🔧 Integration with Existing Projects

### With PHPStan

PHPStan ships with this package as a runtime dependency, so there is nothing extra to
install: `vendor/bin/phpstan` is already available after
`composer require --dev cihispano/git-hooks`.

Create `phpstan.neon` (optional — the hook falls back to the package default when the
project has none):

```neon
parameters:
    level: max
    paths:
        - app
```

### With PHP CS Fixer

PHP CS Fixer and PHP_CodeSniffer (`vendor/bin/php-cs-fixer` and `vendor/bin/phpcs`) also
ship with this package — no extra `composer require` needed.

Create `.php-cs-fixer.dist.php` (optional — the hook falls back to the package default
when the project has none):

```php
<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/app')
    ->name('*.php');

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder);
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a pull request or merge request.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/cihispano/git-hooks.git
cd git-hooks

# Install dependencies
composer install

# Run all active quality checks
composer check:all

# Or run them individually
composer analyze
composer sniff
composer cs
composer test

# Fix code style
composer cs:fix

# Generate coverage locally
composer test:coverage
```

### Test Suite

- `composer analyze`
- `composer sniff`
- `composer cs`
- `composer test`

For a full local validation pass, run `composer check:all`. If you want an HTML
coverage report, run `composer test:coverage` and open the generated files under
`build/coverage/`.

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🔒 Security

If you discover any security-related issues, please email <security@cihispano.org> instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 👥 Credits

- [Jorge Armando Pacheco](https://github.com/jarmandopacheco)
- [All Contributors](../../contributors)

## 🌟 Support

If you find this package helpful, please consider:

- ⭐ Starring the repository
- 🐛 Reporting bugs
- 💡 Suggesting new features
- 📖 Improving documentation
- 🔀 Contributing code

## 📚 Related Packages

- [codeigniter4/framework](https://github.com/codeigniter4/CodeIgniter4) - The CodeIgniter 4 framework
- [phpstan/phpstan](https://github.com/phpstan/phpstan) - PHP Static Analysis Tool
- [friendsofphp/php-cs-fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) - PHP Coding Standards Fixer

---

Made with ❤️ for the CodeIgniter community
