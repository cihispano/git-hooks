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
use PHPUnit\Framework\TestCase;

final class FileComparatorTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        $this->tempRoot = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-file-comparator-' . \bin2hex(\random_bytes(8));

        self::assertTrue(\mkdir($this->tempRoot, FilePermissions::DIR_DEFAULT, true));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempRoot);
    }

    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(FileComparator::class);

        self::assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            self::assertFalse($constructor->isPublic(), 'Constructor should not be public if it exists');
        }
    }

    public function testAreIdenticalReturnsTrueForEqualFiles(): void
    {
        $file1 = $this->createFile('file1.txt', 'same content');
        $file2 = $this->createFile('file2.txt', 'same content');

        self::assertTrue(FileComparator::areIdentical($file1, $file2));
    }

    public function testAreIdenticalReturnsFalseForDifferentFiles(): void
    {
        $file1 = $this->createFile('file1.txt', 'alpha');
        $file2 = $this->createFile('file2.txt', 'beta');

        self::assertFalse(FileComparator::areIdentical($file1, $file2));
    }

    public function testGetHashReturnsExpectedSha256Hash(): void
    {
        $file = $this->createFile('hash.txt', 'content to hash');
        $expectedHash = \hash_file(Config::DEFAULT_ALGORITHM, $file);

        self::assertIsString($expectedHash);
        self::assertSame($expectedHash, FileComparator::getHash($file));
    }

    public function testGetHashThrowsWhenFileDoesNotExist(): void
    {
        $missingFile = $this->tempRoot . \DIRECTORY_SEPARATOR . 'missing.txt';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        FileComparator::getHash($missingFile);
    }

    public function testMatchesHashReturnsTrueWhenHashMatches(): void
    {
        $file = $this->createFile('match.txt', 'match me');
        $expectedHash = FileComparator::getHash($file);

        self::assertTrue(FileComparator::matchesHash($file, $expectedHash));
    }

    public function testMatchesHashReturnsFalseWhenHashDoesNotMatch(): void
    {
        $file = $this->createFile('mismatch.txt', 'mismatch me');

        self::assertFalse(FileComparator::matchesHash($file, \str_repeat('a', 64)));
    }

    public function testAreAllIdenticalReturnsTrueWhenAllFilesMatch(): void
    {
        $files = [
            $this->createFile('one.txt', 'same'),
            $this->createFile('two.txt', 'same'),
            $this->createFile('three.txt', 'same'),
        ];

        self::assertTrue(FileComparator::areAllIdentical($files));
    }

    public function testAreAllIdenticalReturnsFalseWhenOneFileDiffers(): void
    {
        $files = [
            $this->createFile('one.txt', 'same'),
            $this->createFile('two.txt', 'same'),
            $this->createFile('three.txt', 'different'),
        ];

        self::assertFalse(FileComparator::areAllIdentical($files));
    }

    public function testAreAllIdenticalThrowsWhenLessThanTwoFilesProvided(): void
    {
        $file = $this->createFile('single.txt', 'single');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least 2 files required for comparison');

        FileComparator::areAllIdentical([$file]);
    }

    public function testGetSupportedAlgorithmsContainsDefaultAlgorithm(): void
    {
        $algorithms = FileComparator::getSupportedAlgorithms();

        self::assertIsArray($algorithms);
        self::assertContains(Config::DEFAULT_ALGORITHM, $algorithms);
    }

    private function createFile(string $name, string $contents): string
    {
        $path = $this->tempRoot . \DIRECTORY_SEPARATOR . $name;

        self::assertNotFalse(\file_put_contents($path, $contents));

        return $path;
    }

    private function removeDirectory(string $path): void
    {
        if ('' === $path || !\file_exists($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                \rmdir($item->getPathname());

                continue;
            }

            \unlink($item->getPathname());
        }

        \rmdir($path);
    }
}
