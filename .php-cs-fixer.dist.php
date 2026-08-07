<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

use CodeIgniter\CodingStandard\CodeIgniter4;
use Nexus\CsConfig\Factory;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
    ])
    ->files()
    ->exclude([
        'vendor',
        'writable',
        'builds',
        'build',
        'docs',
        '.git',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->ignoreVCSIgnored(false)
    ->append([__FILE__]);

return Factory::create(
    new CodeIgniter4(),
    // CiHispano overrides on top of the CodeIgniter4 ruleset
    [
        'header_comment' => [
            'header'       => "Copyright (c) 2026.\nThis file is part of CiHispano Git Hooks library.\n\n@copyright CiHispano <administracion@cihispano.org>\n@license For the full copyright and license information, see the LICENSE file distributed with this source code.",
            'comment_type' => 'comment',
            'separate'     => 'both',
        ],
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope'   => 'namespaced',
            'strict'  => true,
        ],
    ],
    [
        'finder'    => $finder,
        'cacheFile' => __DIR__ . '/build/.php-cs-fixer.cache',
    ],
)->forProjects();
