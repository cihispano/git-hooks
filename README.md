# Git Hooks for CodeIgniter 4

[![Latest Version](https://img.shields.io/packagist/v/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![Total Downloads](https://img.shields.io/packagist/dt/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![License](https://img.shields.io/packagist/l/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![PHP Version](https://img.shields.io/packagist/php-v/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)

Automated Git Hooks for CodeIgniter 4 projects. This package ensures your code meets the highest quality standards by running automated checks before every commit.

## ✨ Features

- 🔍 **PHP Syntax Check** - Validates PHP syntax (lint) on all staged files.
- 📊 **PHPStan Analysis** - Performs deep static analysis to find potential bugs (Level: Max).
- 👃 **PHP_CodeSniffer** - Validates PSR-12 compliance and coding standards.
- 🎨 **PHP CS Fixer** - Automatically formats code to follow defined styles.
- 🎯 **Smart Scope** - Only analyzes staged files to keep your workflow fast.
- 🌈 **Termwind Output** - Beautiful, colorful console feedback with icons.
- 🔧 **Zero Config** - Works out of the box with sensible defaults for CI4.

## 📋 Requirements

- **PHP 8.4** or higher
- **Git 2.0** or higher
- **Composer 2.0** or higher

## 📦 Installation

Install the package as a development dependency:

```bash
composer require --dev cihispano/git-hooks
```

The hooks will be installed automatically after installation.

### Manual Installation

If you need to reinstall the hooks:

```bash
composer install-hooks
```

## 🚀 Usage

Once installed, the hooks work automatically.

### `pre-commit`

Every time you commit code, the `pre-commit` hook will:

1. ✅ Check PHP syntax on all staged `.php` files
2. ✅ Run PHPStan analysis (if installed)
3. ✅ Check PSR-12 compliance with PHP_CodeSniffer (if installed)
4. ✅ Verify formatting with PHP CS Fixer (if installed)

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

This repository ships its own PHPUnit suite for the package itself. The installed `pre-push` hook is also designed for consumer projects and will run PHPUnit there when it is available. If PHPUnit is not installed in the target project, the hook skips that step and continues with the remaining checks.

### Example Output

```bash
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Starting CodeIgniter pre-commit checks...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[1/4] Checking PHP syntax...
✓ Syntax check passed

[2/4] Running PHPStan analysis...
✓ Analysis passed

[3/4] Sniffing code style (PHPCS)...
✓ PSR-12 compliance verified

[4/4] Verifying formatting (PHP CS Fixer)...
✓ Style check passed

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ All checks passed! Proceeding with commit...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### When a Check Fails

If any check fails, the commit will be blocked:

```bash
[4/4] Verifying formatting (PHP CS Fixer)...
✗ Code style issues in: app/Controllers/Home.php
Run: php vendor/bin/php-cs-fixer fix app/Controllers/Home.php
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

### Customizing the Hooks

The hooks are located in your project's `.git/hooks/` directory after installation. You can modify them if needed, but keep in mind they will be overwritten when you update the package.

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
        "cs": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/php-cs-fixer fix --ansi --verbose --dry-run --diff",
        "cs:fix": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/php-cs-fixer fix --ansi --verbose --diff",
        "sniff": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/phpcs",
        "sniff:fix": "@php -d xdebug.mode=off -d xdebug.log= vendor/bin/phpcbf",
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
composer sniff
composer cs
composer cs:fix
composer sniff:fix
composer test
composer test:coverage
```

## 🔧 Integration with Existing Projects

### With PHPStan

Add PHPStan to your project:

```bash
composer require --dev phpstan/phpstan
```

Create `phpstan.neon`:

```neon
parameters:
    level: max
    paths:
        - app
```

### With PHP CS Fixer

Add PHP CS Fixer to your project:

```bash
composer require --dev friendsofphp/php-cs-fixer
```

Create `.php-cs-fixer.dist.php`:

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

For a full local validation pass, run `composer check:all`. If you want an HTML coverage report, run `composer test:coverage` and open the generated files under `build/coverage/`.

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
