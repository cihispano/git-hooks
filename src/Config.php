<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano;

/**
 * Config.
 *
 * Central configuration constants for Git Hooks library
 */
final class Config
{
    /**
     * Default hash algorithm to use.
     */
    public const string DEFAULT_ALGORITHM = 'sha256';

    /**
     * Git hooks directory relative path.
     */
    public const string GIT_HOOKS_DIR = '.git' . \DIRECTORY_SEPARATOR . 'hooks';

    /**
     * Build directory name.
     */
    public const string BUILD_DIR = 'build';

    /**
     * Source directory name.
     */
    public const string SRC_DIR = 'src';

    /**
     * Hooks source directory name.
     */
    public const string HOOKS_SOURCE_DIR = 'Hooks';

    /**
     * Default hook files available for installation.
     *
     * @var array<string>
     */
    public const array DEFAULT_HOOKS = [
        'pre-commit',
        'commit-msg',
        'pre-push',
    ];

    /**
     * File extensions to exclude from hook installation.
     *
     * @var array<string>
     */
    public const array EXCLUDED_EXTENSIONS = [
        '.sample',
        '.txt',
        '.md',
        '.bak',
    ];

    /**
     * Default separator line length.
     */
    public const int SEPARATOR_LENGTH = 50;

    /**
     * Console color for success messages.
     */
    public const string COLOR_SUCCESS = 'green';

    /**
     * Console color for error messages.
     */
    public const string COLOR_ERROR = 'red';

    /**
     * Console color for warning messages.
     */
    public const string COLOR_WARNING = 'yellow';

    /**
     * Console color for info messages.
     */
    public const string COLOR_INFO = 'cyan';

    /**
     * Maximum allowed hook file size in bytes.
     *
     * Default: 1MB
     */
    public const int MAX_HOOK_FILE_SIZE = 1048576;

    /**
     * Minimum PHP version required.
     */
    public const string MIN_PHP_VERSION = '8.2.0';

    /**
     * Path to Termwind functions file relative to vendor.
     */
    public const string TERMWIND_FUNCTIONS_PATH = '/nunomaduro/termwind/src/Functions.php';

    /**
     * Private constructor to prevent instantiation.
     *
     * This class should only be used for its constants.
     */
    private function __construct()
    {
    }
}
