<?php
/*
 * Copyright (c) 2025.
 * This file is part of CiHispano Breadcrumbs library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, please view  the LICENSE file that was distributed with this source code.
 *
 */

declare(strict_types=1);

require_once 'vendor/autoload.php';

use PhpCsFixer\Finder;
use PhpCsFixer\Config;

$finder = Finder::create()
    ->in([__DIR__, './src'])
    ->files()
    ->exclude(['build', 'vendor'])
    ->ignoreVCSIgnored(true)
    ->ignoreDotFiles(false)
    ->append([__FILE__]);

return (new Config())
    ->setRiskyAllowed(true)
    ->setCacheFile('build/.php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true, // Prepare the code for PHP 8.4 (Hooks, etc.)
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        // Allows instantiation and access to members without additional parentheses (PHP 8.4)
        'new_with_parentheses' => [
            'anonymous_class' => false,
            'named_class' => false,
        ],

        // Trailing commas in everything PHP 8.x allows (arrays, arguments, parameters)
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters', 'match'],
        ],

        // Style and Cleanliness
        'modernize_strpos' => true, 
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],
        
        // Import Management
        'no_unused_imports' => true,
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_functions' => true,
            'import_constants' => true,
        ],

        // Order of elements (Updated for Property Hooks 8.4)
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'method_public',
                'method_protected',
                'method_private',
            ],
            'sort_algorithm' => 'none',
        ],

        // (Optional, but trending in 8.x)
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    ])
->setFinder($finder);
