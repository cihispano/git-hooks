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
use ReflectionClass;

/**
 * @internal
 */
final class ConfigTest extends TestCase
{
    public function testConfigConstantsHaveExpectedValues(): void
    {
        $this->assertSame('sha256', Config::DEFAULT_ALGORITHM);
        $this->assertSame('.git' . \DIRECTORY_SEPARATOR . 'hooks', Config::GIT_HOOKS_DIR);
        $this->assertSame('build', Config::BUILD_DIR);
        $this->assertSame('src', Config::SRC_DIR);
        $this->assertSame('Hooks', Config::HOOKS_SOURCE_DIR);
        $this->assertSame(50, Config::SEPARATOR_LENGTH);
        $this->assertSame('green', Config::COLOR_SUCCESS);
        $this->assertSame('red', Config::COLOR_ERROR);
        $this->assertSame('yellow', Config::COLOR_WARNING);
        $this->assertSame('cyan', Config::COLOR_INFO);
        $this->assertSame(1048576, Config::MAX_HOOK_FILE_SIZE);
        $this->assertSame('8.1.0', Config::MIN_PHP_VERSION);
    }

    public function testDefaultHooksContainExpectedHookNames(): void
    {
        $this->assertSame([
            'pre-commit',
            'commit-msg',
            'pre-push',
        ], Config::DEFAULT_HOOKS);
    }

    public function testExcludedExtensionsContainExpectedEntries(): void
    {
        $this->assertSame([
            '.sample',
            '.txt',
            '.md',
            '.bak',
        ], Config::EXCLUDED_EXTENSIONS);
    }

    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new ReflectionClass(Config::class);

        $this->assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            $this->assertFalse($constructor->isPublic(), 'Constructor should not be public if it exists');

            $instance = $reflection->newInstanceWithoutConstructor();
            $constructor->setAccessible(true);
            $constructor->invoke($instance);

            $this->assertInstanceOf(Config::class, $instance);
        }
    }
}
