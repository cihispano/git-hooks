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
use CiHispano\Util\GitRepository;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use SplFileInfo;

/**
 * @internal
 */
final class GitRepositoryTest extends TestCase
{
    private string $tempRoot;
    private ?string $inheritedPath = null;

    protected function setUp(): void
    {
        $this->tempRoot = \sys_get_temp_dir() .
            \DIRECTORY_SEPARATOR . 'cihispano-git-repository-' . \bin2hex(\random_bytes(8));

        $this->assertTrue(\mkdir($this->tempRoot, FilePermissions::DIR_DEFAULT, true));
    }

    protected function tearDown(): void
    {
        if (null !== $this->inheritedPath) {
            \putenv('PATH=' . $this->inheritedPath);
            $this->inheritedPath = null;
        }

        $this->removeDirectory($this->tempRoot);
    }

    public function testResolveHooksDirWithGitDirectory(): void
    {
        $projectRoot = $this->createProjectRoot();
        $gitDir      = $projectRoot . \DIRECTORY_SEPARATOR . '.git';

        $this->assertTrue(\mkdir($gitDir, FilePermissions::DIR_DEFAULT, true));

        $this->assertSame(
            $gitDir . \DIRECTORY_SEPARATOR . 'hooks',
            $this->invokeManualResolution($projectRoot),
        );
    }

    public function testResolveHooksDirWithGitFileAbsoluteGitDir(): void
    {
        $projectRoot = $this->createProjectRoot();
        $gitDir      = $this->tempRoot . \DIRECTORY_SEPARATOR . 'elsewhere' . \DIRECTORY_SEPARATOR . '.git';

        $this->assertTrue(\mkdir($gitDir, FilePermissions::DIR_DEFAULT, true));

        \file_put_contents(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git',
            "gitdir: {$gitDir}\n",
        );

        $this->assertSame(
            $gitDir . \DIRECTORY_SEPARATOR . 'hooks',
            $this->invokeManualResolution($projectRoot),
        );
    }

    public function testResolveHooksDirWithGitFileRelativeGitDir(): void
    {
        $projectRoot = $this->createProjectRoot();
        $gitDir      = $this->tempRoot . \DIRECTORY_SEPARATOR . 'nested' . \DIRECTORY_SEPARATOR . '.git';

        $this->assertTrue(\mkdir($gitDir, FilePermissions::DIR_DEFAULT, true));

        \file_put_contents(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git',
            "gitdir: ../nested/.git\n",
        );

        $this->assertSame(
            $gitDir . \DIRECTORY_SEPARATOR . 'hooks',
            $this->invokeManualResolution($projectRoot),
        );
    }

    public function testResolveHooksDirUsesCommonDirForWorktrees(): void
    {
        $projectRoot  = $this->createProjectRoot();
        $commonGitDir = $this->tempRoot .
            \DIRECTORY_SEPARATOR . 'main' . \DIRECTORY_SEPARATOR . '.git';

        $this->assertTrue(\mkdir($commonGitDir, FilePermissions::DIR_DEFAULT, true));

        $worktreeGitDir = $commonGitDir .
            \DIRECTORY_SEPARATOR . 'worktrees' . \DIRECTORY_SEPARATOR . 'wt';

        $this->assertTrue(\mkdir($worktreeGitDir, FilePermissions::DIR_DEFAULT, true));

        \file_put_contents(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git',
            "gitdir: {$worktreeGitDir}\n",
        );

        \file_put_contents($worktreeGitDir . \DIRECTORY_SEPARATOR . 'commondir', '../..' . PHP_EOL);

        $this->assertSame(
            $commonGitDir . \DIRECTORY_SEPARATOR . 'hooks',
            $this->invokeManualResolution($projectRoot),
        );
    }

    public function testResolveHooksDirFallsBackToDefaultGitPathWhenMissing(): void
    {
        $projectRoot = $this->createProjectRoot();

        $this->assertSame(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR . 'hooks',
            $this->invokeManualResolution($projectRoot),
        );
    }

    public function testResolveHooksDirIgnoresInvalidGitFile(): void
    {
        $projectRoot = $this->createProjectRoot();

        \file_put_contents($projectRoot . \DIRECTORY_SEPARATOR . '.git', "not a gitdir entry\n");

        $this->assertSame(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR . 'hooks',
            $this->invokeManualResolution($projectRoot),
        );
    }

    public function testResolveHooksDirWithRealGitRepository(): void
    {
        if (! \function_exists('shell_exec')) {
            $this->markTestSkipped('shell_exec is not available.');
        }

        $projectRoot = $this->createProjectRoot();

        $result = @\shell_exec('git init -q ' . \escapeshellarg($projectRoot) . ' 2>/dev/null');

        if (! \is_string($result) && ! \is_dir($projectRoot . \DIRECTORY_SEPARATOR . '.git')) {
            $this->markTestSkipped('git binary not available.');
        }

        $hooksDir = GitRepository::resolveHooksDir($projectRoot);

        $this->assertSame(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR . 'hooks',
            $hooksDir,
        );
    }

    public function testResolveHooksDirWithRealGitRepositoryAndCoreHooksPath(): void
    {
        if (! \function_exists('shell_exec')) {
            $this->markTestSkipped('shell_exec is not available.');
        }

        $projectRoot = $this->createProjectRoot();
        $customHooks = $projectRoot . \DIRECTORY_SEPARATOR . '.githooks';

        $this->assertTrue(\mkdir($customHooks, FilePermissions::DIR_DEFAULT, true));

        $result = @\shell_exec('git init -q ' . \escapeshellarg($projectRoot) . ' 2>/dev/null');

        if (! \is_string($result) && ! \is_dir($projectRoot . \DIRECTORY_SEPARATOR . '.git')) {
            $this->markTestSkipped('git binary not available.');
        }

        @\shell_exec(\sprintf(
            'git -C %s config core.hooksPath .githooks 2>/dev/null',
            \escapeshellarg($projectRoot),
        ));

        $hooksDir = GitRepository::resolveHooksDir($projectRoot);

        $this->assertSame($customHooks, $hooksDir);
    }

    public function testResolveHooksDirUsesGitBinaryAbsoluteResult(): void
    {
        $projectRoot = $this->createProjectRoot();
        $customHooks = $projectRoot . \DIRECTORY_SEPARATOR . 'custom-hooks';

        $this->withGitShim($customHooks);

        $this->assertSame($customHooks, GitRepository::resolveHooksDir($projectRoot));
    }

    public function testResolveHooksDirResolvesRelativeGitOutputToProjectRoot(): void
    {
        $projectRoot = $this->createProjectRoot();

        $this->withGitShim('.githooks');

        $this->assertSame(
            $projectRoot . \DIRECTORY_SEPARATOR . '.githooks',
            GitRepository::resolveHooksDir($projectRoot),
        );
    }

    public function testResolveHooksDirFallsBackWhenGitOutputIsEmpty(): void
    {
        $projectRoot = $this->createProjectRoot();

        $this->withGitShim('');

        $this->assertSame(
            $projectRoot . \DIRECTORY_SEPARATOR . '.git' . \DIRECTORY_SEPARATOR . 'hooks',
            GitRepository::resolveHooksDir($projectRoot),
        );
    }

    public function testIsGitRepositoryDetectsGitDirectory(): void
    {
        $projectRoot = $this->createProjectRoot();

        $this->assertTrue(\mkdir($projectRoot . \DIRECTORY_SEPARATOR . '.git', FilePermissions::DIR_DEFAULT, true));

        $this->assertTrue(GitRepository::isGitRepository($projectRoot));
    }

    public function testIsGitRepositoryDetectsGitFile(): void
    {
        $projectRoot = $this->createProjectRoot();

        \file_put_contents($projectRoot . \DIRECTORY_SEPARATOR . '.git', "gitdir: /elsewhere/.git\n");

        $this->assertTrue(GitRepository::isGitRepository($projectRoot));
    }

    public function testIsGitRepositoryFalseOutsideRepository(): void
    {
        $projectRoot = $this->createProjectRoot();

        $this->assertFalse(GitRepository::isGitRepository($projectRoot));
    }

    private function withGitShim(string $output): void
    {
        if (! \function_exists('shell_exec')) {
            $this->markTestSkipped('shell_exec is not available.');
        }

        $binDir = $this->tempRoot . \DIRECTORY_SEPARATOR . 'simulated-bin';

        if (! \is_dir($binDir)) {
            $this->assertTrue(\mkdir($binDir, FilePermissions::DIR_DEFAULT, true));
        }

        $executable = $binDir . \DIRECTORY_SEPARATOR . 'git';

        \file_put_contents(
            $executable,
            \sprintf("#!/bin/sh\nprintf '%%s' %s\n", \escapeshellarg($output)),
        );

        $this->assertTrue(\chmod($executable, FilePermissions::FILE_EXECUTABLE));

        $currentPath         = \getenv('PATH');
        $this->inheritedPath = $currentPath ?: null;
        \putenv('PATH=' . $binDir . \PATH_SEPARATOR . ($currentPath ?: ''));
    }

    private function createProjectRoot(): string
    {
        $projectRoot = $this->tempRoot . \DIRECTORY_SEPARATOR . 'project-' . \bin2hex(\random_bytes(4));

        $this->assertTrue(\mkdir($projectRoot, FilePermissions::DIR_DEFAULT, true));

        return $projectRoot;
    }

    private function invokeManualResolution(string $basePath): string
    {
        $reflection = new ReflectionMethod(GitRepository::class, 'resolveManually');
        $reflection->setAccessible(true);

        $result = $reflection->invoke(null, $basePath);

        if (! \is_string($result)) {
            $this->fail('Expected resolveManually() to return a string.');
        }

        return $result;
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
