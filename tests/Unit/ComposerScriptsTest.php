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

use CiHispano\ComposerScripts;
use CiHispano\Config;
use CiHispano\ConsoleLogger;
use CiHispano\Util\FilePermissions;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

final class ComposerScriptsTest extends TestCase
{
    private string $tempRoot;

    private string $originalCwd;

    /**
     * @var resource
     */
    private $output;

    protected function setUp(): void
    {
        $this->originalCwd = \getcwd() ?: __DIR__;
        $this->tempRoot = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-composer-scripts-' . \bin2hex(\random_bytes(8));

        self::assertTrue(\mkdir($this->tempRoot, FilePermissions::DIR_DEFAULT, true));

        $this->output = \fopen('php://memory', 'r+');
        ConsoleLogger::setOutputStream($this->output);
    }

    protected function tearDown(): void
    {
        \chdir($this->originalCwd);
        ConsoleLogger::setOutputStream(null);
        \fclose($this->output);
        $this->removeDirectory($this->tempRoot);
    }

    public function testInstallCreatesBuildAndHooksDirectoriesAndCopiesHooks(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::install($projectRoot));

        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        self::assertDirectoryExists($projectRoot . \DIRECTORY_SEPARATOR . Config::BUILD_DIR);
        self::assertDirectoryExists($hooksDir);

        foreach (Config::DEFAULT_HOOKS as $hook) {
            self::assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . $hook);
            self::assertSame(
                \file_get_contents(
                    $projectRoot . \DIRECTORY_SEPARATOR .
                    Config::SRC_DIR . \DIRECTORY_SEPARATOR .
                    Config::HOOKS_SOURCE_DIR . \DIRECTORY_SEPARATOR . $hook,
                ),
                \file_get_contents($hooksDir . \DIRECTORY_SEPARATOR . $hook),
            );
        }
    }

    public function testInstallKeepsExistingIdenticalHookContent(): void
    {
        $projectRoot = $this->createProjectStructure();
        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        self::assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));

        $sourceHook = $projectRoot . \DIRECTORY_SEPARATOR .
            Config::SRC_DIR . \DIRECTORY_SEPARATOR . Config::HOOKS_SOURCE_DIR . \DIRECTORY_SEPARATOR . 'pre-commit';
        $destinationHook = $hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit';

        \copy($sourceHook, $destinationHook);
        $originalContents = \file_get_contents($destinationHook);

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::install($projectRoot));

        self::assertSame($originalContents, \file_get_contents($destinationHook));
    }

    public function testInstallOverwritesDifferentExistingHook(): void
    {
        $projectRoot = $this->createProjectStructure();
        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        self::assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));

        $destinationHook = $hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit';
        \file_put_contents($destinationHook, '# stale hook');

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::install($projectRoot));

        self::assertSame(
            \file_get_contents(
                $projectRoot . \DIRECTORY_SEPARATOR .
                Config::SRC_DIR . \DIRECTORY_SEPARATOR .
                Config::HOOKS_SOURCE_DIR . \DIRECTORY_SEPARATOR . 'pre-commit',
            ),
            \file_get_contents($destinationHook),
        );
    }

    public function testInstallCopiesPackageHooksToConsumerProject(): void
    {
        $projectRoot = $this->createConsumerStructure();

        self::assertDirectoryExists($this->packageHooksSourceDir());

        foreach (Config::DEFAULT_HOOKS as $hook) {
            self::assertFileExists($this->packageHooksSourceDir() . \DIRECTORY_SEPARATOR . $hook);
        }

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::install($projectRoot));

        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        foreach (Config::DEFAULT_HOOKS as $hook) {
            self::assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . $hook);
            self::assertSame(
                \file_get_contents(
                    $this->packageHooksSourceDir() . \DIRECTORY_SEPARATOR . $hook,
                ),
                \file_get_contents($hooksDir . \DIRECTORY_SEPARATOR . $hook),
            );
        }
    }

    public function testInstallThrowsWhenBasePathDoesNotExist(): void
    {
        $missingPath = $this->tempRoot . \DIRECTORY_SEPARATOR . 'missing-project';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Base path does not exist or is not a directory');

        ComposerScripts::install($missingPath);
    }

    public function testInstallThrowsWhenBuildPathExistsAsFile(): void
    {
        $projectRoot = $this->createProjectStructure();
        \file_put_contents($projectRoot . \DIRECTORY_SEPARATOR . Config::BUILD_DIR, 'not a directory');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create build directory');

        \set_error_handler(static fn (): bool => true);

        try {
            ComposerScripts::install($projectRoot);
        } finally {
            \restore_error_handler();
        }
    }

    public function testInstallWrapsFailureWhenHooksDirectoryCannotBeCreated(): void
    {
        $root = $this->createVfsProjectStructure(true, false);

        try {
            $this->runOutsideGitRepository(
                $this->tempRoot,
                static fn (): mixed => ComposerScripts::install($root->url()),
            );
            self::fail('Expected install() to fail when .git/hooks cannot be created.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Failed to install git hooks. Check the errors above.', $exception->getMessage());
            self::assertStringContainsString(
                'Git hooks directory not found and could not be created',
                $exception->getPrevious()?->getMessage() ?? '',
            );
        }
    }

    public function testInstallWrapsFailureWhenHooksDirectoryIsNotWritable(): void
    {
        $root = $this->createVfsProjectStructure();
        $hooksDir = vfsStream::newDirectory('hooks', FilePermissions::DIR_READ_ONLY)->at($root->getChild('.git'));

        self::assertFalse(\is_writable($hooksDir->url()));

        try {
            $this->runOutsideGitRepository(
                $this->tempRoot,
                static fn (): mixed => ComposerScripts::install($root->url()),
            );
            self::fail('Expected install() to fail when .git/hooks is not writable.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Failed to install git hooks. Check the errors above.', $exception->getMessage());
            self::assertStringContainsString(
                'Git hooks directory is not writable',
                $exception->getPrevious()?->getMessage() ?? '',
            );
        }
    }

    public function testInstallResolvesWorktreeGitFileDestination(): void
    {
        $projectRoot = $this->createConsumerStructure(true);
        $mainRepo    = $this->tempRoot . \DIRECTORY_SEPARATOR . 'main-repo';

        self::assertTrue(\mkdir($mainRepo, FilePermissions::DIR_DEFAULT, true));

        $commonGitDir = $mainRepo . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;
        self::assertTrue(\mkdir($commonGitDir, FilePermissions::DIR_DEFAULT, true));

        $worktreeGitDir = $mainRepo .
            \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR .
            'worktrees' . \DIRECTORY_SEPARATOR . 'wt';

        self::assertTrue(\mkdir($mainRepo . \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR . 'worktrees', FilePermissions::DIR_DEFAULT, true));
        self::assertTrue(\mkdir($worktreeGitDir, FilePermissions::DIR_DEFAULT, true));

        \file_put_contents(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git',
            "gitdir: {$worktreeGitDir}\n",
        );

        \file_put_contents($worktreeGitDir . \DIRECTORY_SEPARATOR . 'commondir', '../..' . PHP_EOL);

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::install($projectRoot));

        foreach (Config::DEFAULT_HOOKS as $hook) {
            self::assertFileExists($commonGitDir . \DIRECTORY_SEPARATOR . $hook);
        }
    }

    public function testUninstallRemovesInstalledHooksAndLeavesSamplesUntouched(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::install($projectRoot));

        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit.sample', 'sample');
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . '.keep', 'hidden');

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::uninstall($projectRoot));

        foreach (Config::DEFAULT_HOOKS as $hook) {
            self::assertFileDoesNotExist($hooksDir . \DIRECTORY_SEPARATOR . $hook);
        }

        self::assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit.sample');
        self::assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . '.keep');
    }

    public function testUninstallDoesNothingWhenHooksDirectoryIsMissing(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::uninstall($projectRoot));

        self::assertDirectoryDoesNotExist($projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR);
    }

    public function testUninstallWrapsFailureWhenHooksDirectoryCannotBeRead(): void
    {
        $root = $this->createVfsProjectStructure();
        $hooksDir = vfsStream::newDirectory('hooks', FilePermissions::DIR_NO_ACCESS)->at($root->getChild('.git'));

        self::assertFalse(\is_readable($hooksDir->url()));

        try {
            $this->runOutsideGitRepository(
                $this->tempRoot,
                static fn (): mixed => ComposerScripts::uninstall($root->url()),
            );
            self::fail('Expected uninstall() to fail when the hooks directory cannot be read.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Failed to uninstall git hooks', $exception->getMessage());
            self::assertStringContainsString(
                'Failed to read git hooks directory',
                $exception->getPrevious()?->getMessage() ?? '',
            );
        }
    }

    public function testPostInstallUsesCurrentWorkingDirectory(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->runOutsideGitRepository($projectRoot, static function (): mixed {
            ComposerScripts::postInstall();

            return null;
        });

        foreach (Config::DEFAULT_HOOKS as $hook) {
            self::assertFileExists(
                $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR . \DIRECTORY_SEPARATOR . $hook,
            );
        }
    }

    public function testPostUpdateReinstallsAfterHookChanges(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->runOutsideGitRepository($projectRoot, static fn (): mixed => ComposerScripts::install($projectRoot));

        $installedHook = $projectRoot .
            \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR . \DIRECTORY_SEPARATOR . 'pre-push';

        \file_put_contents($installedHook, '# outdated');

        $this->runOutsideGitRepository($projectRoot, static function (): mixed {
            ComposerScripts::postUpdate();

            return null;
        });

        self::assertSame(
            \file_get_contents(
                $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR . \DIRECTORY_SEPARATOR . 'pre-push',
            ),
            \file_get_contents($installedHook),
        );
    }

    public function testPrivateGetExistingHookFilesFiltersHiddenSampleAndDirectories(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        self::assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit', 'hook');
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit.sample', 'sample');
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . '.hidden', 'hidden');
        self::assertTrue(\mkdir($hooksDir . \DIRECTORY_SEPARATOR . 'nested'));

        $result = $this->invokePrivateStaticMethod(ComposerScripts::class, 'getExistingHookFiles', [$hooksDir]);

        self::assertSame(['pre-commit'], $result);
    }

    public function testPrivateRemoveSingleHookSkipsMissingFiles(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        self::assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));

        $this->invokePrivateStaticMethod(ComposerScripts::class, 'removeSingleHook', ['pre-commit', $hooksDir]);

        self::assertFileDoesNotExist($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit');
    }

    public function testPrivateInstallSingleHookThrowsWhenSourceFileIsMissing(): void
    {
        $projectRoot = $this->createProjectStructure();
        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        self::assertTrue(\mkdir($hooksDir, FilePermissions::DIR_WORLD_WRITABLE, true));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Source hook file not found');

        $this->invokePrivateStaticMethod(ComposerScripts::class, 'installSingleHook', ['missing-hook', $projectRoot]);
    }

    public function testPrivateInstallSingleHookThrowsWhenCopyFails(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to copy hook: pre-commit');

        \set_error_handler(static fn (): bool => true);

        try {
            $this->runOutsideGitRepository(
                $this->tempRoot,
                fn (): mixed => $this->invokePrivateStaticMethod(
                    ComposerScripts::class,
                    'installSingleHook',
                    ['pre-commit', $projectRoot],
                ),
            );
        } finally {
            \restore_error_handler();
        }
    }

    public function testPrivateRemoveSingleHookThrowsWhenUnlinkFails(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        self::assertTrue(
            \mkdir($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit', FilePermissions::DIR_DEFAULT, true),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to remove hook: pre-commit');

        $this->invokePrivateStaticMethod(ComposerScripts::class, 'removeSingleHook', ['pre-commit', $hooksDir]);
    }

    public function testPrivateGetGitHooksDirPrefersDetectedRepositoryPath(): void
    {
        $detectedPath = $this->invokePrivateStaticMethod(ComposerScripts::class, 'getGitHooksDir', [null]);

        self::assertIsString($detectedPath);
        self::assertNotSame('', $detectedPath);
        self::assertStringEndsWith(
            Config::GIT_HOOKS_DIR,
            \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $detectedPath),
        );
    }

    private function createProjectStructure(bool $withHooks = true): string
    {
        $projectRoot = $this->tempRoot . \DIRECTORY_SEPARATOR . 'project-' . \bin2hex(\random_bytes(4));
        $hooksSourceDir = $projectRoot .
            \DIRECTORY_SEPARATOR . Config::SRC_DIR . \DIRECTORY_SEPARATOR . Config::HOOKS_SOURCE_DIR;

        self::assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));
        self::assertTrue(\mkdir($projectRoot . \DIRECTORY_SEPARATOR . '.git', FilePermissions::DIR_DEFAULT, true));

        if ($withHooks) {
            self::assertTrue(\mkdir($hooksSourceDir, FilePermissions::DIR_DEFAULT, true));

            foreach (Config::DEFAULT_HOOKS as $hook) {
                \copy(
                    \dirname(__DIR__, 2) . \DIRECTORY_SEPARATOR .
                        Config::SRC_DIR . \DIRECTORY_SEPARATOR .
                        Config::HOOKS_SOURCE_DIR . \DIRECTORY_SEPARATOR . $hook,
                    $hooksSourceDir . \DIRECTORY_SEPARATOR . $hook,
                );
            }
        }

        return $projectRoot;
    }

    private function createConsumerStructure(bool $withGitFile = false): string
    {
        $projectRoot = $this->tempRoot . \DIRECTORY_SEPARATOR . 'consumer-' . \bin2hex(\random_bytes(4));

        self::assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));

        if ($withGitFile) {
            return $projectRoot;
        }

        self::assertTrue(\mkdir($projectRoot . \DIRECTORY_SEPARATOR . '.git', FilePermissions::DIR_DEFAULT, true));

        return $projectRoot;
    }

    private function packageHooksSourceDir(): string
    {
        return \dirname(__DIR__, 2) .
            \DIRECTORY_SEPARATOR . Config::SRC_DIR .
            \DIRECTORY_SEPARATOR . Config::HOOKS_SOURCE_DIR;
    }

    private function createVfsProjectStructure(
        bool $withHooks = true,
        bool $withGitDirectory = true,
    ): vfsStreamDirectory {
        $root = vfsStream::setup('composer-scripts-' . \bin2hex(\random_bytes(4)));

        if ($withGitDirectory) {
            vfsStream::newDirectory('.git')->at($root);
        } else {
            vfsStream::newFile('.git')->withContent('not-a-directory')->at($root);
        }

        if ($withHooks) {
            $hooksRoot = vfsStream::newDirectory(Config::SRC_DIR)->at($root);
            $hooksSource = vfsStream::newDirectory(Config::HOOKS_SOURCE_DIR)->at($hooksRoot);

            foreach (Config::DEFAULT_HOOKS as $hook) {
                vfsStream::newFile($hook)
                    ->withContent("#!/usr/bin/env php\necho '{$hook}';")
                    ->at($hooksSource);
            }
        }

        return $root;
    }

    /**
     * @template T
     *
     * @param \Closure():T $callback
     *
     * @return T
     */
    private function runOutsideGitRepository(string $projectRoot, \Closure $callback): mixed
    {
        \chdir($projectRoot);

        try {
            return $callback();
        } finally {
            \chdir($this->originalCwd);
        }
    }

    /**
     * @param array<int, mixed> $arguments
     */
    private function invokePrivateStaticMethod(string $className, string $methodName, array $arguments): mixed
    {
        $reflection = new \ReflectionMethod($className, $methodName);

        return $reflection->invokeArgs(null, $arguments);
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
