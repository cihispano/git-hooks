<?php

declare(strict_types=1);

/**
 * This file is part of CiHispano Git Hooks library.
 *
 * Copyright (c) 2026 CiHispano <administracion@cihispano.org>
 *
 * For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

/**
 * One-shot development reset.
 *
 * Removes installed dependencies and generated/cached artifacts so the project
 * can be reinstalled from scratch. The lock file is kept so validation always
 * runs against the same resolved dependency set. Runs before `composer install`, keeping
 * the working tree clean for the validation step that follows every feature/fix/bug.
 */

$root = dirname(__DIR__);

$targets = [
    'vendor',
    'build',
];

$files = [
    '.php-cs-fixer.cache',
    '.phpunit.result.cache',
    '.phpunit.cache',
];

$removed = [];

foreach ($targets as $target) {
    $path = $root . DIRECTORY_SEPARATOR . $target;

    if (is_dir($path)) {
        deleteDirectory($path);
        $removed[] = $target . '/';

        continue;
    }

    if (is_file($path)) {
        unlink($path);
        $removed[] = $target;
    }
}

foreach ($files as $file) {
    $path = $root . DIRECTORY_SEPARATOR . $file;

    if (is_dir($path)) {
        deleteDirectory($path);
        $removed[] = $file . '/';

        continue;
    }

    if (is_file($path)) {
        unlink($path);
        $removed[] = $file;
    }
}

if ($removed === []) {
    echo '[reset] Nothing to clean. Project already clean.', PHP_EOL;

    exit(0);
}

echo '[reset] Removed:', PHP_EOL;
foreach ($removed as $name) {
    echo '  - ', $name, PHP_EOL;
}

echo '[reset] Done. Run `composer install` (or `composer reset`) to reinstall.', PHP_EOL;


/**
 * @phpstan-ignore-next-line (declared after use; evaluated at runtime)
 */
function deleteDirectory(string $path): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());

            continue;
        }

        unlink($item->getPathname());
    }

    rmdir($path);
}