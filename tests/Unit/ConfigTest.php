<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Unit;

use CiHispano\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testConfigConstants(): void
    {
        self::assertIsString(Config::GIT_HOOKS_DIR);
        self::assertIsString(Config::BUILD_DIR);
        self::assertIsString(Config::SRC_DIR);
        self::assertIsString(Config::HOOKS_SOURCE_DIR);
        self::assertIsArray(Config::DEFAULT_HOOKS);
        self::assertIsArray(Config::EXCLUDED_EXTENSIONS);
        self::assertIsInt(Config::SEPARATOR_LENGTH);
        self::assertIsString(Config::COLOR_SUCCESS);
        self::assertIsString(Config::COLOR_ERROR);
        self::assertIsString(Config::COLOR_WARNING);
        self::assertIsString(Config::COLOR_INFO);
        self::assertIsInt(Config::MAX_HOOK_FILE_SIZE);
        self::assertIsString(Config::MIN_PHP_VERSION);
        self::assertIsString(Config::TERMWIND_FUNCTIONS_PATH);
    }
}
