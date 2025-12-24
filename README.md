# Git Hooks

[![Latest Version](https://img.shields.io/packagist/v/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![Total Downloads](https://img.shields.io/packagist/dt/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![License](https://img.shields.io/packagist/l/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)
[![PHP Version](https://img.shields.io/packagist/php-v/cihispano/git-hooks.svg)](https://packagist.org/packages/cihispano/git-hooks)

Automated Git Hooks for CodeIgniter 4 projects with integrated code quality checks. This package automatically installs pre-commit hooks that validate your PHP code before each commit.

## ✨ Features

- 🔍 **PHP Syntax Check (Lint)** - Validates PHP syntax on staged files
- 📊 **PHPStan Static Analysis** - Detects potential errors before runtime
- 🎨 **PHP CS Fixer** - Ensures code follows coding standards
- 🎯 **Smart Analysis** - Only analyzes staged files for better performance
- 🌈 **Colorful Output** - Beautiful console output with icons and colors
- ⚡ **Easy Installation** - Automatic setup via Composer
- 🔧 **Zero Configuration** - Works out of the box

## 📋 Requirements

- PHP 8.1 or higher
- Git 2.0 or higher
- Composer 2.0 or higher

## 📦 Installation

Install via Composer:

```bash
composer require --dev cihispano/git-hooks
```

The hooks will be installed automatically after installation.

### Manual Installation

If you need to reinstall the hooks:

```bash
composer run-script install-git-hooks
```

## 🚀 Usage

Once installed, the hooks work automatically. Every time you commit code, the pre-commit hook will:

1. ✅ Check PHP syntax on all staged `.php` files
2. ✅ Run PHPStan analysis (if installed)
3. ✅ Verify code style with PHP CS Fixer (if installed)

### Example Output

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Starting CodeIgniter pre-commit checks...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[1/3] Checking PHP syntax...
✓ PHP syntax check passed

[2/3] Running PHPStan analysis...
✓ PHPStan analysis passed

[3/3] Checking code style (PHP CS Fixer)...
✓ Code style check passed

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✓ All checks passed! Proceeding with commit...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### When a Check Fails

If any check fails, the commit will be blocked:

```
[3/3] Checking code style (PHP CS Fixer)...
✗ Code style issues in: app/Controllers/Home.php
Run: php vendor/bin/php-cs-fixer fix app/Controllers/Home.php
```

Fix the issues and try again:

```bash
# Fix code style automatically
composer cs-fix

# Stage the fixed files
git add .

# Try committing again
git commit -m "Your message"
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
composer run-script uninstall-git-hooks
```

### Customizing the Hooks

The hooks are located in your project's `.git/hooks/` directory after installation. You can modify them if needed, but keep in mind they will be overwritten when you update the package.

## 📊 Composer Scripts

This package provides the following Composer scripts:

```json
{
    "scripts": {
        "install-git-hooks": "CiHispano\\GitHooks\\ComposerScripts::installGitHooks",
        "uninstall-git-hooks": "CiHispano\\GitHooks\\ComposerScripts::uninstallGitHooks"
    }
}
```

Add these to your `composer.json` to access them easily:

```bash
composer install-git-hooks
composer uninstall-git-hooks
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

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/cihispano/git-hooks.git
cd git-hooks

# Install dependencies
composer install

# Run tests
composer test

# Check code style
composer cs

# Fix code style
composer cs-fix
```

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## 🔒 Security

If you discover any security-related issues, please email security@cihispano.org instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## 👥 Credits

- [Jorge Armando Pacheco](https://github.com/yourusername)
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
