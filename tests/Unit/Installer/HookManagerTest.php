<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Unit\Installer;

use CiHispano\Config;
use CiHispano\ConsoleLogger;
use CiHispano\Installer\HookManager;
use CiHispano\Util\FilePermissions;
use CiHispano\Util\Filesystem;
use CiHispano\Util\PathBuilder;
use FilesystemIterator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * @internal
 */
final class HookManagerTest extends TestCase
{
    private string $tempRoot;
    private Filesystem $filesystem;

    /**
     * @var resource
     */
    private $output;

    protected function setUp(): void
    {
        $this->tempRoot = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-hook-manager-' . \bin2hex(\random_bytes(8));

        $this->assertTrue(\mkdir($this->tempRoot, FilePermissions::DIR_DEFAULT, true));

        $stream = \fopen('php://memory', 'r+b');
        $this->assertIsResource($stream);

        $this->output     = $stream;
        $this->filesystem = new Filesystem();

        ConsoleLogger::setOutputStream($stream);
    }

    protected function tearDown(): void
    {
        ConsoleLogger::setOutputStream(null);
        \fclose($this->output);
        $this->removeDirectory($this->tempRoot);
    }

    public function testListSourceHooksReturnsExactlyTheDefaultHooks(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();

        $extras = ['README.md', 'pre-rebase', '.hidden', 'pre-commit.sample', 'notes.bak'];

        foreach ($extras as $extra) {
            $this->assertNotFalse(
                \file_put_contents($sourceDirectory . \DIRECTORY_SEPARATOR . $extra, 'extra'),
            );
        }

        $manager = new HookManager($this->filesystem, $sourceDirectory);
        $hooks   = $manager->listSourceHooks();

        $expected = Config::DEFAULT_HOOKS;
        $actual   = $hooks;

        \sort($expected);
        \sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function testListSourceHooksUsesThePackageSourceDirectoryByDefault(): void
    {
        $manager = new HookManager($this->filesystem);
        $hooks   = $manager->listSourceHooks();

        $expected = Config::DEFAULT_HOOKS;
        $actual   = $hooks;

        \sort($expected);
        \sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function testListSourceHooksThrowsWhenSourceDirectoryDoesNotExist(): void
    {
        $missing = $this->tempRoot . \DIRECTORY_SEPARATOR . 'absent-source';
        $manager = new HookManager($this->filesystem, $missing);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Hooks source directory does not exist');

        $manager->listSourceHooks();
    }

    public function testListSourceHooksThrowsWhenSourceDirectoryHoldsNoHooks(): void
    {
        $empty = $this->tempRoot . \DIRECTORY_SEPARATOR . 'empty-source';
        $this->assertTrue(\mkdir($empty));

        $manager = new HookManager($this->filesystem, $empty);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No valid hook files found in');

        $manager->listSourceHooks();
    }

    public function testListSourceHooksThrowsWhenSourceDirectoryCannotBeRead(): void
    {
        $root    = vfsStream::setup('hook-manager-source');
        $blocked = vfsStream::newDirectory('hooks', FilePermissions::DIR_NO_ACCESS)->at($root);

        $this->assertIsNotReadable($blocked->url());

        $manager = new HookManager($this->filesystem, $blocked->url());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to read hooks source directory');

        $manager->listSourceHooks();
    }

    public function testListInstalledHooksFiltersHiddenSampleAndDirectories(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));
        $this->assertNotFalse(\file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit', 'hook'));
        $this->assertNotFalse(
            \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit.sample', 'sample'),
        );
        $this->assertNotFalse(\file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . '.hidden', 'hidden'));
        $this->assertTrue(\mkdir($hooksDir . \DIRECTORY_SEPARATOR . 'nested'));

        $manager = new HookManager($this->filesystem);

        $this->assertSame(['pre-commit'], $manager->listInstalledHooks($hooksDir));
    }

    public function testListInstalledHooksIgnoresForeignHooksAndBackups(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertNotFalse(\file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . $hook, 'hook'));
        }

        $foreign = [
            'post-checkout',
            'post-merge',
            'pre-commit' . Config::BACKUP_SUFFIX,
            'pre-commit.sample',
            '.hidden',
        ];

        foreach ($foreign as $entry) {
            $this->assertNotFalse(\file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . $entry, 'kept'));
        }

        $this->assertTrue(\mkdir($hooksDir . \DIRECTORY_SEPARATOR . 'nested'));

        $manager   = new HookManager($this->filesystem);
        $installed = $manager->listInstalledHooks($hooksDir);
        $expected  = Config::DEFAULT_HOOKS;

        \sort($installed);
        \sort($expected);

        $this->assertSame($expected, $installed);
    }

    public function testListInstalledHooksReturnsEmptyListWhenDirectoryIsMissing(): void
    {
        $missing = $this->tempRoot . \DIRECTORY_SEPARATOR . 'absent-hooks';
        $manager = new HookManager($this->filesystem);

        $this->assertSame([], $manager->listInstalledHooks($missing));
    }

    public function testListInstalledHooksThrowsWhenDirectoryCannotBeRead(): void
    {
        $root    = vfsStream::setup('hook-manager-installed');
        $blocked = vfsStream::newDirectory('hooks', FilePermissions::DIR_NO_ACCESS)->at($root);

        $this->assertIsNotReadable($blocked->url());

        $manager = new HookManager($this->filesystem);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to read git hooks directory');

        $manager->listInstalledHooks($blocked->url());
    }

    public function testRemoveHookSkipsMissingFiles(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));

        $manager = new HookManager($this->filesystem);
        $manager->removeHook('pre-commit', $hooksDir);

        $this->assertFileDoesNotExist($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit');

        \rewind($this->output);
        $output = \stream_get_contents($this->output);
        $this->assertStringContainsString("Hook 'pre-commit' not found, skipping", $output);
    }

    public function testRemoveHookDeletesExistingHookFile(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));
        $this->assertNotFalse(\file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit', 'hook'));

        $manager = new HookManager($this->filesystem);
        $manager->removeHook('pre-commit', $hooksDir);

        $this->assertFileDoesNotExist($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit');

        \rewind($this->output);
        $output = \stream_get_contents($this->output);
        $this->assertStringContainsString('Removed hook: pre-commit', $output);
    }

    public function testRemoveHookThrowsWhenTargetIsADirectory(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(
            \mkdir($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit', FilePermissions::DIR_DEFAULT, true),
        );

        $manager = new HookManager($this->filesystem);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to remove hook: pre-commit');

        $manager->removeHook('pre-commit', $hooksDir);
    }

    public function testInstallHookThrowsWhenSourceHookIsMissing(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject();
        $manager         = new HookManager($this->filesystem, $sourceDirectory);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Source hook file not found');

        $manager->installHook('missing-hook', $project);
    }

    public function testInstallHookThrowsWhenCopyFails(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject(false);
        $manager         = new HookManager($this->filesystem, $sourceDirectory);

        \set_error_handler(static fn (): bool => true);

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Failed to copy hook: pre-commit');

            $manager->installHook('pre-commit', $project);
        } finally {
            \restore_error_handler();
        }
    }

    public function testInstallHookSkipsIdenticalDestination(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject();
        $destination     = PathBuilder::join($this->hooksDirectory($project), 'pre-commit');

        $this->assertTrue(
            \copy($sourceDirectory . \DIRECTORY_SEPARATOR . 'pre-commit', $destination),
        );

        $manager = new HookManager($this->filesystem, $sourceDirectory);
        $manager->installHook('pre-commit', $project);

        \rewind($this->output);
        $output = \stream_get_contents($this->output);

        $this->assertStringContainsString("Hook 'pre-commit' already up to date", $output);
        $this->assertStringNotContainsString('Installed hook: pre-commit', $output);
        $this->assertFileDoesNotExist($destination . Config::BACKUP_SUFFIX);
        $this->assertSame(
            \file_get_contents($sourceDirectory . \DIRECTORY_SEPARATOR . 'pre-commit'),
            \file_get_contents($destination),
        );
    }

    public function testInstallHookOverwritesDifferentDestination(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject();
        $destination     = PathBuilder::join($this->hooksDirectory($project), 'pre-commit');

        $this->assertNotFalse(\file_put_contents($destination, '# stale hook'));

        $manager = new HookManager($this->filesystem, $sourceDirectory);
        $manager->installHook('pre-commit', $project);

        \rewind($this->output);
        $output = \stream_get_contents($this->output);

        $this->assertStringContainsString('Overwriting existing hook: pre-commit', $output);
        $this->assertStringContainsString('Installed hook: pre-commit', $output);
        $this->assertSame(
            \file_get_contents($sourceDirectory . \DIRECTORY_SEPARATOR . 'pre-commit'),
            \file_get_contents($destination),
        );

        if (PHP_OS_FAMILY !== 'Windows') {
            $permissions = \fileperms($destination);
            $this->assertIsInt($permissions);
            $this->assertSame(FilePermissions::FILE_EXECUTABLE, $permissions & 0o777);
        }
    }

    public function testInstallHookBacksUpExistingHookBeforeOverwriting(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject();
        $destination     = PathBuilder::join($this->hooksDirectory($project), 'pre-commit');
        $backup          = $destination . Config::BACKUP_SUFFIX;

        $this->assertNotFalse(\file_put_contents($destination, '# locally customized hook'));

        $manager = new HookManager($this->filesystem, $sourceDirectory);
        $manager->installHook('pre-commit', $project);

        $this->assertFileExists($backup);
        $this->assertSame('# locally customized hook', \file_get_contents($backup));
        $this->assertSame(
            \file_get_contents($sourceDirectory . \DIRECTORY_SEPARATOR . 'pre-commit'),
            \file_get_contents($destination),
        );

        \rewind($this->output);
        $output = \stream_get_contents($this->output);

        $this->assertStringContainsString('Overwriting existing hook: pre-commit', $output);
        $this->assertStringContainsString('pre-commit' . Config::BACKUP_SUFFIX, $output);
    }

    public function testInstallHookWarnsWhenExecutePermissionsCannotBeSet(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject();
        $destination     = PathBuilder::join($this->hooksDirectory($project), 'pre-commit');

        $this->assertNotFalse(\file_put_contents($destination, '# stale hook'));

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('isDirectory')->willReturn(true);
        $filesystem->method('exists')->willReturn(true);
        $filesystem->method('copy')->willReturn(true);
        $filesystem->method('makeExecutable')->willReturn(false);

        $manager = new HookManager($filesystem, $sourceDirectory);
        $manager->installHook('pre-commit', $project);

        \rewind($this->output);
        $output = \stream_get_contents($this->output);

        $this->assertStringContainsString('Could not set execute permissions for: pre-commit', $output);
        $this->assertStringContainsString('Installed hook: pre-commit', $output);
    }

    public function testInstallHookAbortsWhenTheBackupCannotBeWritten(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject();
        $destination     = PathBuilder::join($this->hooksDirectory($project), 'pre-commit');

        $this->assertNotFalse(\file_put_contents($destination, '# locally customized hook'));

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('isDirectory')->willReturn(true);
        $filesystem->method('exists')->willReturn(true);
        $filesystem->method('copy')->willReturn(false);

        $manager = new HookManager($filesystem, $sourceDirectory);

        try {
            $manager->installHook('pre-commit', $project);
            $this->fail('Expected installHook() to abort when the backup cannot be written.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString(
                'Failed to back up existing hook: pre-commit',
                $exception->getMessage(),
            );
        }

        $this->assertSame('# locally customized hook', \file_get_contents($destination));
        $this->assertFileDoesNotExist($destination . Config::BACKUP_SUFFIX);
    }

    public function testInstallHookRefreshesTheBackupOnEachOverwrite(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject();
        $destination     = PathBuilder::join($this->hooksDirectory($project), 'pre-commit');
        $backup          = $destination . Config::BACKUP_SUFFIX;

        $manager = new HookManager($this->filesystem, $sourceDirectory);

        foreach (['first customization', 'second customization'] as $customization) {
            $this->assertNotFalse(\file_put_contents($destination, $customization));

            $manager->installHook('pre-commit', $project);

            $this->assertSame($customization, \file_get_contents($backup));
        }
    }

    public function testInstallHooksCopiesEveryDefaultHook(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject();
        $hooksDir        = $this->hooksDirectory($project);

        $manager = new HookManager($this->filesystem, $sourceDirectory);
        $manager->installHooks($project);

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . $hook);
        }

        \rewind($this->output);
        $output = \stream_get_contents($this->output);

        $this->assertStringContainsString('Copying hooks to', $output);
        $this->assertStringContainsString('Hooks copied and permissions processed', $output);
    }

    public function testInstallHookPropagatesFailuresFromTheFilesystemDouble(): void
    {
        $sourceDirectory = $this->createHooksSourceDirectory();
        $project         = $this->createProject(false);

        $filesystem = $this->createMock(Filesystem::class);
        $manager    = new HookManager($filesystem, $sourceDirectory);

        $sourcePath = PathBuilder::join($sourceDirectory, 'pre-commit');
        $destPath   = PathBuilder::join($manager->resolveHooksDirectory($project), 'pre-commit');

        $filesystem->method('isDirectory')->willReturn(true);
        $filesystem->method('exists')->willReturnMap([
            [$sourcePath, true],
            [$destPath, false],
        ]);
        $filesystem->method('copy')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to copy hook: pre-commit');

        $manager->installHook('pre-commit', $project);
    }

    public function testResolveHooksDirectoryPrefersDetectedRepositoryPath(): void
    {
        $manager      = new HookManager($this->filesystem);
        $detectedPath = $manager->resolveHooksDirectory(null);

        $this->assertNotSame('', $detectedPath);
        $this->assertStringEndsWith(
            Config::GIT_HOOKS_DIR,
            \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $detectedPath),
        );
    }

    public function testEnsureBuildDirectoryCreatesMissingDirectory(): void
    {
        $project = $this->createPlainDirectory('build-project');
        $manager = new HookManager($this->filesystem);

        $manager->ensureBuildDirectory($project);
        $manager->ensureBuildDirectory($project);

        $this->assertDirectoryExists($project . \DIRECTORY_SEPARATOR . Config::BUILD_DIR);
    }

    public function testEnsureBuildDirectoryThrowsWhenBasePathDoesNotExist(): void
    {
        $missing = $this->tempRoot . \DIRECTORY_SEPARATOR . 'missing-project';
        $manager = new HookManager($this->filesystem);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base path does not exist or is not a directory');

        $manager->ensureBuildDirectory($missing);
    }

    public function testEnsureBuildDirectoryThrowsWhenBuildPathIsBlockedByAFile(): void
    {
        $project = $this->createPlainDirectory('blocked-build-project');

        $this->assertNotFalse(
            \file_put_contents($project . \DIRECTORY_SEPARATOR . Config::BUILD_DIR, 'not a directory'),
        );

        $manager = new HookManager($this->filesystem);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create build directory');

        \set_error_handler(static fn (): bool => true);

        try {
            $manager->ensureBuildDirectory($project);
        } finally {
            \restore_error_handler();
        }
    }

    public function testEnsureHooksDirectoryCreatesMissingDirectory(): void
    {
        $project = $this->createProject(false);
        $manager = new HookManager($this->filesystem);

        $manager->ensureHooksDirectoryExists($project);

        $this->assertDirectoryExists($this->hooksDirectory($project));

        \rewind($this->output);
        $output = \stream_get_contents($this->output);
        $this->assertStringContainsString('Created missing hooks directory', $output);
    }

    public function testEnsureHooksDirectoryThrowsWhenDirectoryCannotBeCreated(): void
    {
        $root = vfsStream::setup('hook-manager-create', null, [
            '.git' => 'not-a-directory',
        ]);

        $manager = new HookManager($this->filesystem);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Git hooks directory not found and could not be created');

        $manager->ensureHooksDirectoryExists($root->url());
    }

    public function testEnsureHooksDirectoryWritableThrowsWhenDirectoryIsReadOnly(): void
    {
        $root = vfsStream::setup('hook-manager-readonly');
        vfsStream::newDirectory('.git')->at($root);

        $gitDir = $root->getChild('.git');
        $this->assertInstanceOf(vfsStreamDirectory::class, $gitDir);

        $hooksDir = vfsStream::newDirectory('hooks', FilePermissions::DIR_READ_ONLY)->at($gitDir);

        $this->assertIsNotWritable($hooksDir->url());

        $manager = new HookManager($this->filesystem);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Git hooks directory is not writable');

        $manager->ensureHooksDirectoryWritable($root->url());
    }

    public function testEnsureHooksDirectoryWritableAcceptsWritableDirectory(): void
    {
        $project = $this->createProject();
        $manager = new HookManager($this->filesystem);

        $manager->ensureHooksDirectoryWritable($project);

        $this->assertTrue($this->filesystem->isWritable($this->hooksDirectory($project)));
    }

    public function testIsGitRepositoryDetectsRepositoriesAndPlainDirectories(): void
    {
        $manager = new HookManager($this->filesystem);
        $plain   = $this->createPlainDirectory('plain-project');

        $this->assertTrue($manager->isGitRepository($this->createProject()));
        $this->assertFalse($manager->isGitRepository($plain));
    }

    private function createHooksSourceDirectory(): string
    {
        $source = $this->tempRoot . \DIRECTORY_SEPARATOR . 'hooks-source';

        $this->assertTrue(\mkdir($source, FilePermissions::DIR_DEFAULT, true));

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertNotFalse(
                \file_put_contents(
                    $source . \DIRECTORY_SEPARATOR . $hook,
                    "#!/bin/sh\necho '{$hook}';",
                ),
            );
        }

        return $source;
    }

    private function createProject(bool $withHooksDirectory = true): string
    {
        $project = $this->tempRoot . \DIRECTORY_SEPARATOR . 'project-' . \bin2hex(\random_bytes(4));

        $this->assertTrue(
            \mkdir($project . \DIRECTORY_SEPARATOR . '.git', FilePermissions::DIR_DEFAULT, true),
        );

        if ($withHooksDirectory) {
            $this->assertTrue(
                \mkdir($project . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR, FilePermissions::DIR_DEFAULT, true),
            );
        }

        return $project;
    }

    private function createPlainDirectory(string $name): string
    {
        $directory = $this->tempRoot . \DIRECTORY_SEPARATOR . $name;

        $this->assertTrue(\mkdir($directory, FilePermissions::DIR_DEFAULT, true));

        return $directory;
    }

    private function hooksDirectory(string $project): string
    {
        return $project . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;
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
