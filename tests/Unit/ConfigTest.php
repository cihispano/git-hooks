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
    public function testConfigConstantsHaveExpectedTypes(): void
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

    public function testConfigConstantsHaveExpectedValues(): void
    {
        self::assertSame('sha256', Config::DEFAULT_ALGORITHM);
        self::assertSame('.git' . \DIRECTORY_SEPARATOR . 'hooks', Config::GIT_HOOKS_DIR);
        self::assertSame('build', Config::BUILD_DIR);
        self::assertSame('src', Config::SRC_DIR);
        self::assertSame('Hooks', Config::HOOKS_SOURCE_DIR);
        self::assertSame(50, Config::SEPARATOR_LENGTH);
        self::assertSame('green', Config::COLOR_SUCCESS);
        self::assertSame('red', Config::COLOR_ERROR);
        self::assertSame('yellow', Config::COLOR_WARNING);
        self::assertSame('cyan', Config::COLOR_INFO);
        self::assertSame(1048576, Config::MAX_HOOK_FILE_SIZE);
        self::assertSame('8.1.0', Config::MIN_PHP_VERSION);
        self::assertSame('/nunomaduro/termwind/src/Functions.php', Config::TERMWIND_FUNCTIONS_PATH);
    }

    public function testDefaultHooksContainExpectedHookNames(): void
    {
        self::assertSame([
            'pre-commit',
            'commit-msg',
            'pre-push',
        ], Config::DEFAULT_HOOKS);
    }

    public function testExcludedExtensionsContainExpectedEntries(): void
    {
        self::assertSame([
            '.sample',
            '.txt',
            '.md',
            '.bak',
        ], Config::EXCLUDED_EXTENSIONS);
    }

    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(Config::class);

        self::assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            self::assertFalse($constructor->isPublic(), 'Constructor should not be public if it exists');

            $instance = $reflection->newInstanceWithoutConstructor();
            $constructor->setAccessible(true);
            $constructor->invoke($instance);

            self::assertInstanceOf(Config::class, $instance);
        }
    }
}
