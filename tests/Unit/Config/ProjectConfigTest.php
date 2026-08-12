<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Unit\Config;

use CiHispano\Config\ProjectConfig;
use CiHispano\Util\FilePermissions;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @internal
 */
final class ProjectConfigTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        $this->tempRoot = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-project-config-' . \bin2hex(\random_bytes(8));

        $this->assertTrue(\mkdir($this->tempRoot, FilePermissions::DIR_DEFAULT, true));
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->tempRoot . \DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            \unlink($file);
        }

        \rmdir($this->tempRoot);
    }

    public function testLoadReturnsDefaultsWhenConfigFileIsMissing(): void
    {
        $config = ProjectConfig::load($this->tempRoot);

        $this->assertFalse($config->autoInstall());
        $this->assertSame('build', $config->buildDir());
    }

    public function testLoadReadsValidValues(): void
    {
        $this->writeConfig('{"auto_install": true, "build_dir": "cache"}');

        $config = ProjectConfig::load($this->tempRoot);

        $this->assertTrue($config->autoInstall());
        $this->assertSame('cache', $config->buildDir());
    }

    public function testLoadIgnoresUnknownKeysForForwardCompatibility(): void
    {
        $this->writeConfig('{"auto_install": true, "tools": {"phpstan": false}}');

        $config = ProjectConfig::load($this->tempRoot);

        $this->assertTrue($config->autoInstall());
        $this->assertSame('build', $config->buildDir());
    }

    public function testLoadAcceptsEmptyObject(): void
    {
        $this->writeConfig('{}');

        $config = ProjectConfig::load($this->tempRoot);

        $this->assertFalse($config->autoInstall());
        $this->assertSame('build', $config->buildDir());
    }

    public function testLoadThrowsOnInvalidJson(): void
    {
        $this->writeConfig('{"auto_install":');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid git-hooks.json');

        ProjectConfig::load($this->tempRoot);
    }

    public function testLoadThrowsWhenJsonIsNotAnObject(): void
    {
        $this->writeConfig('[1, 2, 3]');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected a JSON object');

        ProjectConfig::load($this->tempRoot);
    }

    public function testLoadThrowsWhenAutoInstallIsNotBoolean(): void
    {
        $this->writeConfig('{"auto_install": "true"}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"auto_install" must be a boolean');

        ProjectConfig::load($this->tempRoot);
    }

    public function testLoadThrowsWhenBuildDirIsNotAString(): void
    {
        $this->writeConfig('{"build_dir": 42}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"build_dir" must be a non-empty string');

        ProjectConfig::load($this->tempRoot);
    }

    public function testLoadThrowsWhenBuildDirIsEmpty(): void
    {
        $this->writeConfig('{"build_dir": "  "}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"build_dir" must be a non-empty string');

        ProjectConfig::load($this->tempRoot);
    }

    public function testWriteDefaultCreatesFileWithDefaults(): void
    {
        $path = ProjectConfig::writeDefault($this->tempRoot);

        $this->assertFileExists($path);
        $this->assertSame(
            $this->tempRoot . \DIRECTORY_SEPARATOR . ProjectConfig::FILE_NAME,
            $path,
        );

        $config = ProjectConfig::load($this->tempRoot);

        $this->assertFalse($config->autoInstall());
        $this->assertSame('build', $config->buildDir());
    }

    public function testWriteDefaultThrowsWhenFileAlreadyExists(): void
    {
        $this->writeConfig('{"auto_install": true}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        ProjectConfig::writeDefault($this->tempRoot);
    }

    private function writeConfig(string $contents): void
    {
        $this->assertTrue(
            \file_put_contents(
                $this->tempRoot . \DIRECTORY_SEPARATOR . ProjectConfig::FILE_NAME,
                $contents,
            ) !== false,
        );
    }
}
