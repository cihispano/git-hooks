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

use CiHispano\Util\FilePermissions;
use CiHispano\Util\Filesystem;
use FilesystemIterator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * @internal
 */
final class FilesystemTest extends TestCase
{
    private string $tempRoot;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tempRoot = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-filesystem-' . \bin2hex(\random_bytes(8));

        $this->assertTrue(\mkdir($this->tempRoot, FilePermissions::DIR_DEFAULT, true));

        $this->filesystem = new Filesystem();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempRoot);
    }

    public function testExistsIsDirectoryAndIsFileReportPathTypes(): void
    {
        $directory = $this->tempRoot . \DIRECTORY_SEPARATOR . 'sub';
        $file      = $this->tempRoot . \DIRECTORY_SEPARATOR . 'file.txt';

        $this->assertTrue(\mkdir($directory));
        $this->assertNotFalse(\file_put_contents($file, 'content'));

        $this->assertTrue($this->filesystem->exists($file));
        $this->assertTrue($this->filesystem->exists($directory));
        $this->assertFalse($this->filesystem->exists($file . '.missing'));

        $this->assertTrue($this->filesystem->isDirectory($directory));
        $this->assertFalse($this->filesystem->isDirectory($file));

        $this->assertTrue($this->filesystem->isFile($file));
        $this->assertFalse($this->filesystem->isFile($directory));
        $this->assertFalse($this->filesystem->isFile($file . '.missing'));
    }

    public function testIsWritableReportsWritableAndReadOnlyPaths(): void
    {
        $this->assertTrue($this->filesystem->isWritable($this->tempRoot));

        $root               = vfsStream::setup('filesystem-writable');
        $vfsStreamDirectory = vfsStream::newDirectory(
            'readonly',
            FilePermissions::DIR_READ_ONLY,
        )->at($root);

        $this->assertIsNotWritable($vfsStreamDirectory->url());
        $this->assertFalse($this->filesystem->isWritable($vfsStreamDirectory->url()));
    }

    public function testListDirectoryReturnsSortedEntriesIncludingDotPlaceholders(): void
    {
        $directory = $this->tempRoot . \DIRECTORY_SEPARATOR . 'listing';

        $this->assertTrue(\mkdir($directory));
        $this->assertNotFalse(\file_put_contents($directory . \DIRECTORY_SEPARATOR . 'b.txt', 'b'));
        $this->assertNotFalse(\file_put_contents($directory . \DIRECTORY_SEPARATOR . 'a.txt', 'a'));

        $this->assertSame(
            ['.', '..', 'a.txt', 'b.txt'],
            $this->filesystem->listDirectory($directory),
        );
    }

    public function testListDirectoryReturnsFalseWhenDirectoryCannotBeRead(): void
    {
        $missing = $this->tempRoot . \DIRECTORY_SEPARATOR . 'missing-directory';

        $this->assertFalse($this->filesystem->listDirectory($missing));
    }

    public function testMakeDirectoryCreatesMissingParents(): void
    {
        $target = $this->tempRoot . \DIRECTORY_SEPARATOR . 'level1' . \DIRECTORY_SEPARATOR . 'level2';

        $this->assertTrue($this->filesystem->makeDirectory($target));
        $this->assertTrue($this->filesystem->isDirectory($target));
    }

    public function testMakeDirectoryReturnsFalseWhenPathIsBlockedByAFile(): void
    {
        $blocker = $this->tempRoot . \DIRECTORY_SEPARATOR . 'blocker';
        $this->assertNotFalse(\file_put_contents($blocker, 'not a directory'));

        \set_error_handler(static fn (): bool => true);

        try {
            $this->assertFalse($this->filesystem->makeDirectory($blocker . \DIRECTORY_SEPARATOR . 'child'));
        } finally {
            \restore_error_handler();
        }
    }

    public function testMakeDirectoryQuietSuppressesTheNativeWarning(): void
    {
        $blocker = $this->tempRoot . \DIRECTORY_SEPARATOR . 'quiet-blocker';
        $this->assertNotFalse(\file_put_contents($blocker, 'not a directory'));

        // No error handler is installed: the call must stay silent under PHPUnit.
        $this->assertFalse($this->filesystem->makeDirectory($blocker . \DIRECTORY_SEPARATOR . 'child', true));
    }

    public function testCopyCopiesFileContents(): void
    {
        $source      = $this->tempRoot . \DIRECTORY_SEPARATOR . 'source.txt';
        $destination = $this->tempRoot . \DIRECTORY_SEPARATOR . 'destination.txt';

        $this->assertNotFalse(\file_put_contents($source, 'copied contents'));
        $this->assertTrue($this->filesystem->copy($source, $destination));
        $this->assertSame('copied contents', \file_get_contents($destination));
    }

    public function testCopyReturnsFalseWhenSourceDoesNotExist(): void
    {
        $missing     = $this->tempRoot . \DIRECTORY_SEPARATOR . 'missing.txt';
        $destination = $this->tempRoot . \DIRECTORY_SEPARATOR . 'destination.txt';

        \set_error_handler(static fn (): bool => true);

        try {
            $this->assertFalse($this->filesystem->copy($missing, $destination));
        } finally {
            \restore_error_handler();
        }
    }

    public function testDeleteRemovesExistingFile(): void
    {
        $file = $this->tempRoot . \DIRECTORY_SEPARATOR . 'delete-me.txt';
        $this->assertNotFalse(\file_put_contents($file, 'x'));

        $this->assertTrue($this->filesystem->delete($file));
        $this->assertFalse($this->filesystem->exists($file));
    }

    public function testDeleteReturnsFalseForDirectories(): void
    {
        $directory = $this->tempRoot . \DIRECTORY_SEPARATOR . 'delete-me-dir';
        $this->assertTrue(\mkdir($directory));

        $this->assertFalse($this->filesystem->delete($directory));
        $this->assertTrue($this->filesystem->isDirectory($directory));
    }

    public function testMakeExecutableAppliesExecutablePermissions(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Executable permissions are not meaningful on Windows.');
        }

        $file = $this->tempRoot . \DIRECTORY_SEPARATOR . 'run-me.sh';
        $this->assertNotFalse(\file_put_contents($file, '#!/bin/sh'));
        $this->assertTrue(\chmod($file, FilePermissions::FILE_SHARED_READ));

        $this->assertTrue($this->filesystem->makeExecutable($file));

        $permissions = \fileperms($file);
        $this->assertIsInt($permissions);
        $this->assertSame(FilePermissions::FILE_EXECUTABLE, $permissions & 0o777);
    }

    public function testFilesAreIdenticalComparesFileContents(): void
    {
        $first  = $this->tempRoot . \DIRECTORY_SEPARATOR . 'first.txt';
        $second = $this->tempRoot . \DIRECTORY_SEPARATOR . 'second.txt';
        $third  = $this->tempRoot . \DIRECTORY_SEPARATOR . 'third.txt';

        $this->assertNotFalse(\file_put_contents($first, 'same'));
        $this->assertNotFalse(\file_put_contents($second, 'same'));
        $this->assertNotFalse(\file_put_contents($third, 'different'));

        $this->assertTrue($this->filesystem->filesAreIdentical($first, $second));
        $this->assertFalse($this->filesystem->filesAreIdentical($first, $third));
    }

    public function testFilesAreIdenticalThrowsWhenFileDoesNotExist(): void
    {
        $missing = $this->tempRoot . \DIRECTORY_SEPARATOR . 'missing.txt';
        $second  = $this->tempRoot . \DIRECTORY_SEPARATOR . 'second.txt';

        $this->assertNotFalse(\file_put_contents($second, 'content'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File not found');

        $this->filesystem->filesAreIdentical($missing, $second);
    }

    public function testClassCanBeReplacedByAPhpUnitDouble(): void
    {
        $double = $this->createMock(Filesystem::class);
        $double->method('exists')->willReturn(true);

        $this->assertTrue($double->exists('virtual://hook'));
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
