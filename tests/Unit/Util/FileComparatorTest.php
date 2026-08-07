<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Unit\Util;

use CiHispano\Config;
use CiHispano\Util\FileComparator;
use CiHispano\Util\FilePermissions;
use FilesystemIterator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

/**
 * @internal
 */
final class FileComparatorTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        $this->tempRoot = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-file-comparator-' . \bin2hex(\random_bytes(8));

        $this->assertTrue(\mkdir($this->tempRoot, FilePermissions::DIR_DEFAULT, true));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempRoot);
    }

    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new ReflectionClass(FileComparator::class);

        $this->assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            $this->assertFalse($constructor->isPublic(), 'Constructor should not be public if it exists');

            $instance = $reflection->newInstanceWithoutConstructor();
            $constructor->setAccessible(true);
            $constructor->invoke($instance);

            $this->assertInstanceOf(FileComparator::class, $instance);
        }
    }

    public function testAreIdenticalReturnsTrueForEqualFiles(): void
    {
        $file1 = $this->createFile('file1.txt', 'same content');
        $file2 = $this->createFile('file2.txt', 'same content');

        $this->assertTrue(FileComparator::areIdentical($file1, $file2));
    }

    public function testAreIdenticalReturnsFalseForDifferentFiles(): void
    {
        $file1 = $this->createFile('file1.txt', 'alpha');
        $file2 = $this->createFile('file2.txt', 'beta');

        $this->assertFalse(FileComparator::areIdentical($file1, $file2));
    }

    public function testGetHashReturnsExpectedSha256Hash(): void
    {
        $file         = $this->createFile('hash.txt', 'content to hash');
        $expectedHash = \hash_file(Config::DEFAULT_ALGORITHM, $file);

        $this->assertIsString($expectedHash);
        $this->assertSame($expectedHash, FileComparator::getHash($file));
    }

    public function testGetHashThrowsWhenFileDoesNotExist(): void
    {
        $missingFile = $this->tempRoot . \DIRECTORY_SEPARATOR . 'missing.txt';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        FileComparator::getHash($missingFile);
    }

    public function testGetHashThrowsWhenFileIsNotReadableFile(): void
    {
        $file = $this->createFile('locked.txt', 'secret');
        $this->assertIsReadable($file);

        $this->assertTrue(\chmod($file, 0000));

        if (\is_readable($file)) {
            $this->markTestSkipped('Running as a user that can read chmod 0000 files');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File is not readable');

        FileComparator::getHash($file);
    }

    public function testGetHashThrowsWhenHashCalculationFails(): void
    {
        $directory = $this->tempRoot . \DIRECTORY_SEPARATOR . 'directory';
        $this->assertTrue(\mkdir($directory));

        \set_error_handler(static fn (): bool => true, \E_NOTICE);

        try {
            try {
                FileComparator::getHash($directory);
                $this->fail('Expected RuntimeException was not thrown');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString(
                    'Failed to calculate sha256 hash',
                    $exception->getMessage(),
                );
            }
        } finally {
            \restore_error_handler();
        }
    }

    public function testMatchesHashReturnsTrueWhenHashMatches(): void
    {
        $file         = $this->createFile('match.txt', 'match me');
        $expectedHash = FileComparator::getHash($file);

        $this->assertTrue(FileComparator::matchesHash($file, $expectedHash));
    }

    public function testMatchesHashReturnsFalseWhenHashDoesNotMatch(): void
    {
        $file = $this->createFile('mismatch.txt', 'mismatch me');

        $this->assertFalse(FileComparator::matchesHash($file, \str_repeat('a', 64)));
    }

    public function testAreAllIdenticalReturnsTrueWhenAllFilesMatch(): void
    {
        $files = [
            $this->createFile('one.txt', 'same'),
            $this->createFile('two.txt', 'same'),
            $this->createFile('three.txt', 'same'),
        ];

        $this->assertTrue(FileComparator::areAllIdentical($files));
    }

    public function testAreAllIdenticalReturnsFalseWhenOneFileDiffers(): void
    {
        $files = [
            $this->createFile('one.txt', 'same'),
            $this->createFile('two.txt', 'same'),
            $this->createFile('three.txt', 'different'),
        ];

        $this->assertFalse(FileComparator::areAllIdentical($files));
    }

    public function testAreAllIdenticalThrowsWhenLessThanTwoFilesProvided(): void
    {
        $file = $this->createFile('single.txt', 'single');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least 2 files required for comparison');

        FileComparator::areAllIdentical([$file]);
    }

    public function testGetSupportedAlgorithmsContainsDefaultAlgorithm(): void
    {
        $algorithms = FileComparator::getSupportedAlgorithms();

        $this->assertContains(Config::DEFAULT_ALGORITHM, $algorithms);
    }

    private function createFile(string $name, string $contents): string
    {
        $path = $this->tempRoot . \DIRECTORY_SEPARATOR . $name;

        $this->assertNotFalse(\file_put_contents($path, $contents));

        return $path;
    }

    private function removeDirectory(string $path): void
    {
        if ('' === $path || ! \file_exists($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item instanceof SplFileInfo) {
                if ($item->isDir()) {
                    \rmdir($item->getPathname());

                    continue;
                }

                \unlink($item->getPathname());
            }
        }

        \rmdir($path);
    }
}
