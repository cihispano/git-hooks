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
use CiHispano\Config\ProjectConfig;
use CiHispano\ConsoleLogger;
use CiHispano\Tests\Exceptions\AssertionsException;
use CiHispano\Util\FilePermissions;
use Closure;
use FilesystemIterator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;

/**
 * @internal
 */
final class ComposerScriptsTest extends TestCase
{
    use AssertionsException;

    private string $tempRoot;
    private string $originalCwd;

    /**
     * @var resource
     */
    private $output;

    protected function setUp(): void
    {
        $this->originalCwd = \getcwd() ?: __DIR__;
        $this->tempRoot    = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-composer-scripts-' . \bin2hex(\random_bytes(8));

        $this->assertTrue(\mkdir($this->tempRoot, FilePermissions::DIR_DEFAULT, true));

        $stream = \fopen('php://memory', 'r+b');
        $this->assertIsResource($stream);

        $this->output = $stream;
        ConsoleLogger::setOutputStream($stream);
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

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertDirectoryExists($projectRoot . \DIRECTORY_SEPARATOR . Config::BUILD_DIR);
        $this->assertDirectoryExists($hooksDir);

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . $hook);
            $this->assertSame(
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
        $hooksDir    = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));

        $sourceHook = $projectRoot . \DIRECTORY_SEPARATOR .
            Config::SRC_DIR . \DIRECTORY_SEPARATOR . Config::HOOKS_SOURCE_DIR . \DIRECTORY_SEPARATOR . 'pre-commit';
        $destinationHook = $hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit';

        \copy($sourceHook, $destinationHook);
        $originalContents = \file_get_contents($destinationHook);

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        $this->assertSame($originalContents, \file_get_contents($destinationHook));
    }

    public function testInstallOverwritesDifferentExistingHook(): void
    {
        $projectRoot = $this->createProjectStructure();
        $hooksDir    = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));

        $destinationHook = $hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit';
        \file_put_contents($destinationHook, '# stale hook');

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        $this->assertSame(
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

        $this->assertDirectoryExists($this->packageHooksSourceDir());

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertFileExists($this->packageHooksSourceDir() . \DIRECTORY_SEPARATOR . $hook);
        }

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . $hook);
            $this->assertSame(
                \file_get_contents(
                    $this->packageHooksSourceDir() . \DIRECTORY_SEPARATOR . $hook,
                ),
                \file_get_contents($hooksDir . \DIRECTORY_SEPARATOR . $hook),
            );
        }
    }

    public function testInstallAbortsOutsideGitRepositoryWithoutCreatingArtifacts(): void
    {
        $projectRoot = $this->tempRoot . \DIRECTORY_SEPARATOR . 'plain-' . \bin2hex(\random_bytes(4));

        $this->assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        $this->assertDirectoryDoesNotExist($projectRoot . \DIRECTORY_SEPARATOR . '.git');
        $this->assertDirectoryDoesNotExist($projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR);

        \rewind($this->output);
        $output = \stream_get_contents($this->output);
        $this->assertStringContainsString('not a Git repository', $output);
        $this->assertStringNotContainsString('Installing Git Hooks', $output);
        $this->assertStringNotContainsString('Git hooks installed successfully', $output);
        $this->assertStringNotContainsString('Hooks will run automatically', $output);
    }

    public function testInstallAbortsWhenCalledFromNonGitCwd(): void
    {
        $projectRoot = $this->tempRoot . \DIRECTORY_SEPARATOR . 'plain-cwd-' . \bin2hex(\random_bytes(4));

        $this->assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));

        $this->runOutsideGitRepository($projectRoot, static function (): void {
            ComposerScripts::install();
        });

        $this->assertDirectoryDoesNotExist($projectRoot . \DIRECTORY_SEPARATOR . '.git');

        \rewind($this->output);
        $output = \stream_get_contents($this->output);
        $this->assertStringContainsString('not a Git repository', $output);
        $this->assertStringNotContainsString('Git hooks installed successfully', $output);
    }

    public function testInstallThrowsWhenBasePathDoesNotExist(): void
    {
        $missingPath = $this->tempRoot . \DIRECTORY_SEPARATOR . 'missing-project';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Base path does not exist or is not a directory');

        ComposerScripts::install($missingPath);
    }

    public function testInstallThrowsWhenBuildPathExistsAsFile(): void
    {
        $projectRoot = $this->createProjectStructure();
        \file_put_contents($projectRoot . \DIRECTORY_SEPARATOR . Config::BUILD_DIR, 'not a directory');

        $this->expectException(RuntimeException::class);
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
                static function () use ($root): void {
                    ComposerScripts::install($root->url());
                },
            );
            $this->fail('Expected install() to fail when .git/hooks cannot be created.');
        } catch (RuntimeException $exception) {
            $this->assertWrappedException(
                $exception,
                'Failed to install git hooks. Check the errors above.',
                'Git hooks directory not found and could not be created',
            );
        }
    }

    public function testInstallWrapsFailureWhenHooksDirectoryIsNotWritable(): void
    {
        $root   = $this->createVfsProjectStructure();
        $gitDir = $root->getChild('.git');

        $this->assertInstanceOf(vfsStreamDirectory::class, $gitDir);

        $hooksDir = vfsStream::newDirectory('hooks', FilePermissions::DIR_READ_ONLY)->at($gitDir);

        $this->assertIsNotWritable($hooksDir->url());

        try {
            $this->runOutsideGitRepository(
                $this->tempRoot,
                static function () use ($root): void {
                    ComposerScripts::install($root->url());
                },
            );
            $this->fail('Expected install() to fail when .git/hooks is not writable.');
        } catch (RuntimeException $exception) {
            $this->assertWrappedException(
                $exception,
                'Failed to install git hooks. Check the errors above.',
                'Git hooks directory is not writable',
            );
        }
    }

    public function testInstallResolvesWorktreeGitFileDestination(): void
    {
        $projectRoot = $this->createConsumerStructure(true);
        $mainRepo    = $this->tempRoot . \DIRECTORY_SEPARATOR . 'main-repo';

        $this->assertTrue(\mkdir($mainRepo, FilePermissions::DIR_DEFAULT, true));

        $commonGitDir = $mainRepo . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;
        $this->assertTrue(\mkdir($commonGitDir, FilePermissions::DIR_DEFAULT, true));

        $worktreeGitDir = $mainRepo .
            \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR .
            'worktrees' . \DIRECTORY_SEPARATOR . 'wt';

        $this->assertTrue(
            \mkdir(
                $mainRepo . \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR . 'worktrees',
                FilePermissions::DIR_DEFAULT,
                true,
            ),
        );
        $this->assertTrue(\mkdir($worktreeGitDir, FilePermissions::DIR_DEFAULT, true));

        \file_put_contents(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git',
            "gitdir: {$worktreeGitDir}\n",
        );

        \file_put_contents($worktreeGitDir . \DIRECTORY_SEPARATOR . 'commondir', '../..' . PHP_EOL);

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertFileExists($commonGitDir . \DIRECTORY_SEPARATOR . $hook);
        }
    }

    public function testUninstallRemovesInstalledHooksAndLeavesSamplesUntouched(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        $hooksDir = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit.sample', 'sample');
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . '.keep', 'hidden');

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::uninstall($projectRoot);
        });

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertFileDoesNotExist($hooksDir . \DIRECTORY_SEPARATOR . $hook);
        }

        $this->assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit.sample');
        $this->assertFileExists($hooksDir . \DIRECTORY_SEPARATOR . '.keep');
    }

    public function testUninstallDoesNothingWhenHooksDirectoryIsMissing(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::uninstall($projectRoot);
        });

        $this->assertDirectoryDoesNotExist($projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR);
    }

    public function testUninstallWrapsFailureWhenHooksDirectoryCannotBeRead(): void
    {
        $root   = $this->createVfsProjectStructure();
        $gitDir = $root->getChild('.git');

        $this->assertInstanceOf(vfsStreamDirectory::class, $gitDir);

        $hooksDir = vfsStream::newDirectory('hooks', FilePermissions::DIR_NO_ACCESS)->at($gitDir);

        $this->assertIsNotReadable($hooksDir->url());

        try {
            $this->runOutsideGitRepository(
                $this->tempRoot,
                static function () use ($root): void {
                    ComposerScripts::uninstall($root->url());
                },
            );
            $this->fail('Expected uninstall() to fail when the hooks directory cannot be read.');
        } catch (RuntimeException $exception) {
            $this->assertWrappedException(
                $exception,
                'Failed to uninstall git hooks',
                'Failed to read git hooks directory',
            );
        }
    }

    public function testPostInstallUsesCurrentWorkingDirectory(): void
    {
        $projectRoot = $this->createProjectStructure();
        $this->writeConfig($projectRoot, '{"auto_install": true}');

        $this->runOutsideGitRepository($projectRoot, static function (): mixed {
            ComposerScripts::postInstall();

            return null;
        });

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertFileExists(
                $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR . \DIRECTORY_SEPARATOR . $hook,
            );
        }
    }

    public function testPostUpdateReinstallsAfterHookChanges(): void
    {
        $projectRoot = $this->createProjectStructure();
        $this->writeConfig($projectRoot, '{"auto_install": true}');

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        $installedHook = $projectRoot .
            \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR . \DIRECTORY_SEPARATOR . 'pre-push';

        \file_put_contents($installedHook, '# outdated');

        $this->runOutsideGitRepository($projectRoot, static function (): mixed {
            ComposerScripts::postUpdate();

            return null;
        });

        $this->assertSame(
            \file_get_contents(
                $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR . \DIRECTORY_SEPARATOR . 'pre-push',
            ),
            \file_get_contents($installedHook),
        );
    }

    public function testPostInstallSkipsWhenAutoInstallIsNotEnabled(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->runOutsideGitRepository($projectRoot, static function (): mixed {
            ComposerScripts::postInstall();

            return null;
        });

        foreach (Config::DEFAULT_HOOKS as $hook) {
            $this->assertFileDoesNotExist(
                $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR . \DIRECTORY_SEPARATOR . $hook,
            );
        }

        \rewind($this->output);
        $output = \stream_get_contents($this->output);
        $this->assertStringContainsString('Auto-install is disabled', $output);
        $this->assertStringNotContainsString('Git hooks installed successfully', $output);
    }

    public function testPostInstallThrowsOnInvalidConfigWithoutFallingBack(): void
    {
        $projectRoot = $this->createProjectStructure();
        $this->writeConfig($projectRoot, '{"auto_install":');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid git-hooks.json');

        $this->runOutsideGitRepository($projectRoot, static function (): mixed {
            ComposerScripts::postInstall();

            return null;
        });
    }

    public function testInitHooksCreatesDefaultConfigFile(): void
    {
        $projectRoot = $this->tempRoot . \DIRECTORY_SEPARATOR . 'init-' . \bin2hex(\random_bytes(4));

        $this->assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));

        ComposerScripts::initHooks($projectRoot);

        $configPath = $projectRoot . \DIRECTORY_SEPARATOR . ProjectConfig::FILE_NAME;

        $this->assertFileExists($configPath);

        $configContents = (string) \file_get_contents($configPath);
        $decoded        = \json_decode($configContents, true);
        $this->assertIsArray($decoded);
        $this->assertFalse($decoded['auto_install'] ?? null);
        $this->assertSame('build', $decoded['build_dir'] ?? null);

        \rewind($this->output);
        $output = \stream_get_contents($this->output);
        $this->assertStringContainsString('Created git-hooks.json', $output);
    }

    public function testInitHooksThrowsWhenConfigFileAlreadyExists(): void
    {
        $projectRoot = $this->tempRoot . \DIRECTORY_SEPARATOR . 'init-existing-' . \bin2hex(\random_bytes(4));

        $this->assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));
        $this->writeConfig($projectRoot, '{"auto_install": true}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        ComposerScripts::initHooks($projectRoot);
    }

    public function testInstallUsesBuildDirFromProjectConfig(): void
    {
        $projectRoot = $this->createProjectStructure();
        $this->writeConfig($projectRoot, '{"build_dir": "cache"}');

        $this->runOutsideGitRepository($projectRoot, static function () use ($projectRoot): void {
            ComposerScripts::install($projectRoot);
        });

        $this->assertDirectoryExists($projectRoot . \DIRECTORY_SEPARATOR . 'cache');
        $this->assertDirectoryDoesNotExist($projectRoot . \DIRECTORY_SEPARATOR . Config::BUILD_DIR);
    }

    public function testInstallThrowsWhenBuildDirFromConfigIsNotUsable(): void
    {
        $projectRoot = $this->createProjectStructure();
        $this->writeConfig($projectRoot, '{"build_dir": "cache"}');
        \file_put_contents($projectRoot . \DIRECTORY_SEPARATOR . 'cache', 'not a directory');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to create build directory');

        \set_error_handler(static fn (): bool => true);

        try {
            ComposerScripts::install($projectRoot);
        } finally {
            \restore_error_handler();
        }
    }

    public function testPrivateGetExistingHookFilesFiltersHiddenSampleAndDirectories(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit', 'hook');
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit.sample', 'sample');
        \file_put_contents($hooksDir . \DIRECTORY_SEPARATOR . '.hidden', 'hidden');
        $this->assertTrue(\mkdir($hooksDir . \DIRECTORY_SEPARATOR . 'nested'));

        $result = $this->invokePrivateStaticMethod(ComposerScripts::class, 'getExistingHookFiles', [$hooksDir]);

        $this->assertSame(['pre-commit'], $result);
    }

    public function testPrivateRemoveSingleHookSkipsMissingFiles(): void
    {
        $hooksDir = $this->tempRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_DEFAULT, true));

        $this->invokePrivateStaticMethod(ComposerScripts::class, 'removeSingleHook', ['pre-commit', $hooksDir]);

        $this->assertFileDoesNotExist($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit');
    }

    public function testPrivateInstallSingleHookThrowsWhenSourceFileIsMissing(): void
    {
        $projectRoot = $this->createProjectStructure();
        $hooksDir    = $projectRoot . \DIRECTORY_SEPARATOR . Config::GIT_HOOKS_DIR;

        $this->assertTrue(\mkdir($hooksDir, FilePermissions::DIR_WORLD_WRITABLE, true));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Source hook file not found');

        $this->invokePrivateStaticMethod(ComposerScripts::class, 'installSingleHook', ['missing-hook', $projectRoot]);
    }

    public function testPrivateInstallSingleHookThrowsWhenCopyFails(): void
    {
        $projectRoot = $this->createProjectStructure();

        $this->expectException(RuntimeException::class);
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

        $this->assertTrue(
            \mkdir($hooksDir . \DIRECTORY_SEPARATOR . 'pre-commit', FilePermissions::DIR_DEFAULT, true),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to remove hook: pre-commit');

        $this->invokePrivateStaticMethod(ComposerScripts::class, 'removeSingleHook', ['pre-commit', $hooksDir]);
    }

    public function testPrivateGetGitHooksDirPrefersDetectedRepositoryPath(): void
    {
        $detectedPath = $this->invokePrivateStaticMethod(ComposerScripts::class, 'getGitHooksDir', [null]);

        $this->assertIsString($detectedPath);
        $this->assertNotSame('', $detectedPath);
        $this->assertStringEndsWith(
            Config::GIT_HOOKS_DIR,
            \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $detectedPath),
        );
    }

    private function createProjectStructure(bool $withHooks = true): string
    {
        $projectRoot    = $this->tempRoot . \DIRECTORY_SEPARATOR . 'project-' . \bin2hex(\random_bytes(4));
        $hooksSourceDir = $projectRoot .
            \DIRECTORY_SEPARATOR . Config::SRC_DIR . \DIRECTORY_SEPARATOR . Config::HOOKS_SOURCE_DIR;

        $this->assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));
        $this->assertTrue(\mkdir($projectRoot . \DIRECTORY_SEPARATOR . '.git', FilePermissions::DIR_DEFAULT, true));

        if ($withHooks) {
            $this->assertTrue(\mkdir($hooksSourceDir, FilePermissions::DIR_DEFAULT, true));

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

        $this->assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));

        if ($withGitFile) {
            return $projectRoot;
        }

        $this->assertTrue(\mkdir($projectRoot . \DIRECTORY_SEPARATOR . '.git', FilePermissions::DIR_DEFAULT, true));

        return $projectRoot;
    }

    private function packageHooksSourceDir(): string
    {
        return \dirname(__DIR__, 2) .
            \DIRECTORY_SEPARATOR . Config::SRC_DIR .
            \DIRECTORY_SEPARATOR . Config::HOOKS_SOURCE_DIR;
    }

    private function writeConfig(string $projectRoot, string $contents): void
    {
        $this->assertTrue(
            \file_put_contents(
                $projectRoot . \DIRECTORY_SEPARATOR . ProjectConfig::FILE_NAME,
                $contents,
            ) !== false,
        );
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
            $hooksRoot   = vfsStream::newDirectory(Config::SRC_DIR)->at($root);
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
     * @param Closure():T $callback
     *
     * @return T
     */
    private function runOutsideGitRepository(string $projectRoot, Closure $callback): mixed
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
        $reflection = new ReflectionMethod($className, $methodName);

        return $reflection->invokeArgs(null, $arguments);
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
