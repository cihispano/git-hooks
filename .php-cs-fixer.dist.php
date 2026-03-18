<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

use PhpCsFixer\Config;
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
    ->append([__FILE__])
;

$config = new Config();
$config
    ->setRiskyAllowed(true)
    ->setCacheFile('build/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,

        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        'header_comment' => [
            'header' => "Copyright (c) 2025.\nThis file is part of CiHispano Git Hooks library.\n\n@copyright CiHispano <administracion@cihispano.org>\n@license For the full copyright and license information, see the LICENSE file distributed with this source code.",
            'comment_type' => 'comment',
            'separate' => 'both',
        ],

        'strict_param' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,

        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'not_operator_with_successor_space' => false,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
            ],
        ],
        'function_declaration' => [
            'closure_function_spacing' => 'one',
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
        'single_trait_insert_per_statement' => true,
        'single_line_empty_body' => false,
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof' => true,
        'trim_array_spaces' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
            ],
        ],
        'concat_space' => [
            'spacing' => 'one',
        ],
        'type_declaration_spaces' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'no_spaces_around_offset' => true,
        'whitespace_after_comma_in_array' => true,
        'native_function_invocation' => [
            'include' => ['@all'],
            'scope' => 'namespaced',
            'strict' => true,
        ],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/build/.php-cs-fixer.cache')
;

return $config;
