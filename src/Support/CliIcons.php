<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Support;

/**
 * CLI icon constants for terminal output.
 *
 * Provides consistent Unicode symbols for use in console messages.
 * All icons are designed to work with common terminal emulators.
 */
final class CliIcons
{
    /**
     * Success/completion indicator.
     *
     * @var non-empty-string
     */
    public const SUCCESS = '✔';

    /**
     * Error/failure indicator.
     *
     * @var non-empty-string
     */
    public const ERROR = '✖';

    /**
     * Warning indicator.
     *
     * @var non-empty-string
     */
    public const WARNING = '⚠';

    /**
     * Informational indicator.
     *
     * @var non-empty-string
     */
    public const INFO = 'ℹ';

    /**
     * Step indicator.
     *
     * @var non-empty-string
     */
    public const STEP = '➤';

    /**
     * Question/confirmation indicator.
     *
     * @var non-empty-string
     */
    public const ASK = '?';

    /**
     * Bullet point for lists.
     *
     * @var non-empty-string
     */
    public const BULLET = '•';

    /**
     * Private constructor prevents instantiation.
     *
     * This class should only be used for its constants.
     */
    private function __construct()
    {
    }
}
