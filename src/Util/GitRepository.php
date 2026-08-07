<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Util;

/**
 * GitRepository.
 *
 * Resolves Git repository paths in a robust way across standard repos,
 * linked worktrees, submodules and custom hooks directories (core.hooksPath).
 */
final class GitRepository
{
    /**
     * Resolved hooks directories, keyed by normalized base path.
     *
     * @var array<string, string>
     */
    private static array $hooksDirectories = [];

    /**
     * Private constructor to prevent instantiation.
     * This class should only be used for its static methods.
     */
    private function __construct()
    {
    }

    /**
     * Resolve the Git hooks directory for the given project.
     *
     * Resolution order:
     *  1. git-first: `git -C <basePath> rev-parse --git-path hooks` — covers
     *     worktrees, core.hooksPath, submodules and custom layouts.
     *  2. Manual fallback (no git or disabled shell_exec): parse `.git`
     *     directory or `.git` file (gitdir: entry, commondir aware).
     *
     * The result is cached per normalized base path so a single install or
     * uninstall run invokes the git binary at most once.
     *
     * @param string|null $basePath Optional base path; defaults to cwd
     */
    public static function resolveHooksDir(?string $basePath = null): string
    {
        $basePath ??= (\getcwd() ?: '.');
        $key = PathBuilder::normalize($basePath);

        if (isset(self::$hooksDirectories[$key])) {
            return self::$hooksDirectories[$key];
        }

        $gitPath = self::resolveViaGit($basePath);

        if (null !== $gitPath) {
            return self::$hooksDirectories[$key] = $gitPath;
        }

        return self::$hooksDirectories[$key] = self::resolveManually($basePath);
    }

    /**
     * Determine whether the given path is inside a Git repository.
     *
     * Detects a `.git` directory (standard repo and submodules) or a `.git`
     * file (linked worktrees). Used to warn consumers who run the install in
     * a non-repository directory, where hooks would never run.
     *
     * @param string|null $basePath Optional base path; defaults to cwd
     */
    public static function isGitRepository(?string $basePath = null): bool
    {
        $root    = $basePath ?? (\getcwd() ?: '.');
        $gitPath = PathBuilder::join($root, '.git');

        return \is_dir($gitPath) || \is_file($gitPath);
    }

    /**
     * Resolve the hooks directory using the git binary.
     *
     * @param string|null $basePath Optional base path; defaults to cwd
     *
     * @return string|null Normalized path, or null when git is unavailable
     *                     or the directory is not a git repository
     */
    private static function resolveViaGit(?string $basePath = null): ?string
    {
        if (! \function_exists('shell_exec')) {
            return null;
        }

        $target = $basePath ?? (\getcwd() ?: '.');

        $isWindows  = PHP_OS_FAMILY === 'Windows';
        $nullDevice = $isWindows ? 'NUL' : '/dev/null';

        $path = @\shell_exec(\sprintf(
            'git -C %s rev-parse --git-path hooks 2>%s',
            \escapeshellarg($target),
            $nullDevice,
        ));

        if (! \is_string($path)) {
            return null;
        }

        $trimmedPath = \trim($path);

        if ('' === $trimmedPath) {
            return null;
        }

        return PathBuilder::isAbsolute($trimmedPath)
            ? PathBuilder::canonicalize($trimmedPath)
            : PathBuilder::canonicalize(PathBuilder::join(PathBuilder::normalize($target), $trimmedPath));
    }

    /**
     * Resolve the hooks directory without relying on the git binary.
     *
     * Handles:
     *  - `.git` as a directory (standard repo, submodules): `<root>/.git/hooks`.
     *  - `.git` as a file (linked worktrees): parse the `gitdir:` entry and,
     *    when present, the `commondir` file (hooks live in the common dir).
     *
     * @param string|null $basePath Optional base path; defaults to cwd
     */
    private static function resolveManually(?string $basePath = null): string
    {
        $root    = $basePath ?? (\getcwd() ?: '.');
        $gitPath = PathBuilder::join($root, '.git');

        if (\is_dir($gitPath)) {
            return PathBuilder::joinMultiple($gitPath, 'hooks');
        }

        if (\is_file($gitPath)) {
            $gitDir = self::parseGitDirFile($gitPath);

            if (null !== $gitDir) {
                $commonDir = self::readCommonDir($gitDir);

                if (null !== $commonDir) {
                    $gitDir = $commonDir;
                }

                return PathBuilder::joinMultiple($gitDir, 'hooks');
            }
        }

        return PathBuilder::joinMultiple($root, '.git', 'hooks');
    }

    /**
     * Parse a `.git` file containing a `gitdir:` entry.
     *
     * @param string $gitFile Absolute path to the `.git` file
     *
     * @return string|null Resolved absolute git dir, or null when invalid
     */
    private static function parseGitDirFile(string $gitFile): ?string
    {
        $contents = @\file_get_contents($gitFile);

        if (false === $contents) {
            return null;
        }

        if (1 !== \preg_match('/^gitdir:\s*(.+)$/m', $contents, $matches)) {
            return null;
        }

        $gitDir = \trim($matches[1]);

        if (! PathBuilder::isAbsolute($gitDir)) {
            $gitDir = PathBuilder::join(\dirname($gitFile), $gitDir);
        }

        return PathBuilder::canonicalize($gitDir);
    }

    /**
     * Read the `commondir` entry of a linked worktree git dir, if present.
     *
     * @param string $gitDir Absolute git dir of the linked worktree
     *
     * @return string|null Resolved common git dir, or null when absent
     */
    private static function readCommonDir(string $gitDir): ?string
    {
        $commonDirFile = PathBuilder::join($gitDir, 'commondir');

        if (! \is_file($commonDirFile)) {
            return null;
        }

        $contents = @\file_get_contents($commonDirFile);

        if (false === $contents) {
            return null;
        }

        $commonDir = \trim($contents);

        if ('' === $commonDir) {
            return null;
        }

        if (! PathBuilder::isAbsolute($commonDir)) {
            $commonDir = PathBuilder::join($gitDir, $commonDir);
        }

        return PathBuilder::canonicalize($commonDir);
    }
}
