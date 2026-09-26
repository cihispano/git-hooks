<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Installer;

use CiHispano\Config;
use CiHispano\Config\ProjectConfig;
use CiHispano\ConsoleLogger;
use CiHispano\Util\FileComparator;
use CiHispano\Util\Filesystem;
use CiHispano\Util\GitRepository;
use CiHispano\Util\PathBuilder;
use RuntimeException;

/**
 * HookManager.
 *
 * Owns the Git hooks lifecycle: resolving the package source directory and
 * the project hooks directory, listing the hooks this package owns,
 * installing them (with skip-identical detection and a `.bak` backup of any
 * hook that is about to change) and removing them, plus the directories the
 * hooks rely on at run time (the QA build cache).
 *
 * The lifecycle is deliberately non-destructive: uninstall only touches the
 * entries listed in Config::DEFAULT_HOOKS, and install never discards the
 * previous content of a replaced hook.
 *
 * All filesystem writes go through the injected Filesystem collaborator,
 * content comparison through the FileComparator utility and Git resolution
 * through GitRepository, so this class contains hook domain logic only and
 * ComposerScripts can stay a thin facade.
 */
final class HookManager
{
    /**
     * @param Filesystem  $filesystem           Filesystem collaborator (mockable in tests)
     * @param string|null $hooksSourceDirectory Optional override of the package hooks
     *                                          source directory, mainly for tests; when
     *                                          null the installed package directory is used
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ?string $hooksSourceDirectory = null,
    ) {
    }

    /**
     * Whether the given path (or the current working directory when null)
     * lives inside a Git repository.
     */
    public function isGitRepository(?string $basePath = null): bool
    {
        return GitRepository::isGitRepository($basePath);
    }

    /**
     * Ensure that the 'build' cache directory exists in the project root.
     *
     * Static analysis tools (PHPStan, PHP CS Fixer, PHPUnit) are configured
     * to write their temporary/cache files into this folder. Creating it
     * automatically prevents "directory not found" errors during hook execution.
     *
     * @param string|null $basePath the project's base directory (defaults to cwd)
     *
     * @throws RuntimeException if the directory doesn't exist and cannot be created
     */
    public function ensureBuildDirectory(?string $basePath = null): void
    {
        $basePath ??= \getcwd() ?: '.';

        if (! $this->filesystem->isDirectory($basePath)) {
            throw new RuntimeException(
                "Base path does not exist or is not a directory: {$basePath}",
            );
        }

        $buildDir  = ProjectConfig::load($basePath)->buildDir();
        $buildPath = PathBuilder::join($basePath, $buildDir);

        if ($this->filesystem->isDirectory($buildPath)) {
            return;
        }

        if (! $this->filesystem->makeDirectory($buildPath)) {
            throw new RuntimeException(
                \sprintf(
                    'Failed to create build directory at "%s". Check permissions or filesystem state.',
                    $buildPath,
                ),
            );
        }
    }

    /**
     * Validate that the Git hooks directory exists, creating it if possible.
     *
     * @param string|null $basePath Optional base path for resolution
     *
     * @throws RuntimeException
     */
    public function ensureHooksDirectoryExists(?string $basePath = null): void
    {
        $gitHooksDir = $this->resolveHooksDirectory($basePath);

        if ($this->filesystem->isDirectory($gitHooksDir)) {
            return;
        }

        $created = $this->filesystem->makeDirectory($gitHooksDir, true);

        if (! $created && ! $this->filesystem->isDirectory($gitHooksDir)) {
            throw new RuntimeException(
                'Git hooks directory not found and could not be created. '
                . "Make sure you are in a git repository: {$gitHooksDir}",
            );
        }

        ConsoleLogger::info("Created missing hooks directory: {$gitHooksDir}", true);
    }

    /**
     * Validate that the Git hooks directory is writable.
     *
     * @param string|null $basePath Optional base path for resolution
     *
     * @throws RuntimeException
     */
    public function ensureHooksDirectoryWritable(?string $basePath = null): void
    {
        $gitHooksDir = $this->resolveHooksDirectory($basePath);

        if (! $this->filesystem->isWritable($gitHooksDir)) {
            throw new RuntimeException(
                'Git hooks directory is not writable: ' . PathBuilder::normalize($gitHooksDir),
            );
        }
    }

    /**
     * Copy hooks from the package source directory to the Git hooks directory.
     *
     * @param string|null $basePath Optional base path for resolution
     *
     * @throws RuntimeException
     */
    public function installHooks(?string $basePath = null): void
    {
        $hooks       = $this->listSourceHooks();
        $hooksDir    = $this->resolveHooksDirectory($basePath);
        $destination = PathBuilder::normalize($hooksDir);

        ConsoleLogger::step(1, 2, "Copying hooks to {$destination}");
        ConsoleLogger::newLine();

        foreach ($hooks as $hookFile) {
            $this->installHook($hookFile, $basePath);
        }

        ConsoleLogger::newLine();
        ConsoleLogger::step(2, 2, 'Hooks copied and permissions processed');
    }

    /**
     * Install a single hook file, skipping identical destinations.
     *
     * When the destination already exists and differs from the package
     * version, its current content is copied to `<hook>.bak` before the
     * overwrite, so a local customization is never lost silently.
     *
     * @param string      $fileName Name of the hook file inside the source directory
     * @param string|null $basePath Optional base path for resolution
     *
     * @throws RuntimeException
     */
    public function installHook(
        string $fileName,
        ?string $basePath = null,
    ): void {
        $sourcePath = PathBuilder::join($this->resolveSourceDirectory(), $fileName);
        $destPath   = PathBuilder::join($this->resolveHooksDirectory($basePath), $fileName);

        if (! $this->filesystem->exists($sourcePath)) {
            throw new RuntimeException("Source hook file not found: {$sourcePath}");
        }

        if ($this->filesystem->exists($destPath)) {
            if (FileComparator::areIdentical($sourcePath, $destPath)) {
                ConsoleLogger::info("Hook '{$fileName}' already up to date", true);

                return;
            }

            $backupPath = $this->backupHook($destPath, $fileName);

            ConsoleLogger::warning(
                "Overwriting existing hook: {$fileName} (previous version saved to {$backupPath})",
                true,
            );
        }

        if (! $this->filesystem->copy($sourcePath, $destPath)) {
            throw new RuntimeException("Failed to copy hook: {$fileName}");
        }

        if (! $this->filesystem->makeExecutable($destPath)) {
            ConsoleLogger::warning("Could not set execute permissions for: {$fileName}");
        }

        ConsoleLogger::success("Installed hook: {$fileName}", true);
    }

    /**
     * Remove a single hook file.
     *
     * @param string $hookFile    Name of the hook file
     * @param string $gitHooksDir Path to the hooks directory
     *
     * @throws RuntimeException If the file exists but cannot be deleted
     */
    public function removeHook(
        string $hookFile,
        string $gitHooksDir,
    ): void {
        $hookPath = PathBuilder::join($gitHooksDir, $hookFile);

        if (! $this->filesystem->exists($hookPath)) {
            ConsoleLogger::info("Hook '{$hookFile}' not found, skipping", true);

            return;
        }

        if (! $this->filesystem->delete($hookPath)) {
            throw new RuntimeException(
                "Failed to remove hook: {$hookFile}. "
                . 'The file might be in use or you lack sufficient permissions.',
            );
        }

        ConsoleLogger::success("Removed hook: {$hookFile}", true);
    }

    /**
     * Get the hook files owned by this package in the Git hooks directory.
     *
     * Only entries explicitly listed in Config::DEFAULT_HOOKS are returned:
     * the uninstaller must never delete a file it did not install, so foreign
     * hooks (e.g. `post-checkout`), samples, hidden files, nested
     * directories and `.bak` backups all survive an uninstall.
     *
     * @param string $gitHooksDir Path to the hooks directory
     *
     * @return list<string>
     *
     * @throws RuntimeException If the directory exists but cannot be read
     */
    public function listInstalledHooks(string $gitHooksDir): array
    {
        if (! $this->filesystem->isDirectory($gitHooksDir)) {
            return [];
        }

        $entries = $this->filesystem->listDirectory($gitHooksDir);

        if (false === $entries) {
            throw new RuntimeException(
                'Failed to read git hooks directory: ' . PathBuilder::normalize($gitHooksDir),
            );
        }

        return \array_values(\array_filter(
            $entries,
            fn (string $entry): bool => \in_array($entry, Config::DEFAULT_HOOKS, true)
                && $this->filesystem->isFile(PathBuilder::join($gitHooksDir, $entry)),
        ));
    }

    /**
     * Get the hook files available for installation in the package source
     * directory, selected through the explicit Config::DEFAULT_HOOKS list.
     *
     * @return list<string>
     *
     * @throws RuntimeException If the directory is missing or holds no valid hooks
     */
    public function listSourceHooks(): array
    {
        $hooksSource = $this->resolveSourceDirectory();

        $entries = $this->filesystem->listDirectory($hooksSource);

        if (false === $entries) {
            throw new RuntimeException("Unable to read hooks source directory: {$hooksSource}");
        }

        $hooks = \array_values(\array_filter(
            $entries,
            fn (string $entry): bool => \in_array($entry, Config::DEFAULT_HOOKS, true)
                && $this->filesystem->isFile(PathBuilder::join($hooksSource, $entry)),
        ));

        if (empty($hooks)) {
            throw new RuntimeException("No valid hook files found in: {$hooksSource}");
        }

        return $hooks;
    }

    /**
     * Get the Git hooks directory in the project.
     *
     * Delegates to GitRepository::resolveHooksDir() so the resolution is
     * always git-first (worktrees, core.hooksPath, submodules) with a manual
     * fallback, regardless of how the method was invoked.
     *
     * @param string|null $basePath Optional base path for resolution
     *
     * @throws RuntimeException If the path cannot be determined
     */
    public function resolveHooksDirectory(?string $basePath = null): string
    {
        return GitRepository::resolveHooksDir($basePath);
    }

    /**
     * Get the hooks source directory from the package.
     *
     * The source always lives inside the installed package, never inside the
     * consumer project. Only the destination (resolveHooksDirectory) depends
     * on the project where hooks are being installed.
     *
     * @throws RuntimeException
     */
    public function resolveSourceDirectory(): string
    {
        $path = $this->hooksSourceDirectory
            ?? PathBuilder::joinMultiple(
                \dirname(__DIR__, 2),
                Config::SRC_DIR,
                Config::HOOKS_SOURCE_DIR,
            );

        if (! $this->filesystem->isDirectory($path)) {
            throw new RuntimeException(
                "Hooks source directory does not exist: {$path}. "
                . 'Check if the library structure is correct.',
            );
        }

        return $path;
    }

    /**
     * Preserve the current content of an existing hook before it is replaced.
     *
     * @param string $destPath Path of the hook about to be overwritten
     * @param string $fileName Name of the hook file (used in error messages)
     *
     * @return string Path of the written backup file
     *
     * @throws RuntimeException When the backup copy fails, aborting the
     *                          install so the current version is not lost
     */
    private function backupHook(string $destPath, string $fileName): string
    {
        $backupPath = $destPath . Config::BACKUP_SUFFIX;

        if (! $this->filesystem->copy($destPath, $backupPath)) {
            throw new RuntimeException(
                "Failed to back up existing hook: {$fileName}. "
                . "Aborting the install so the current version at {$destPath} is not lost.",
            );
        }

        return $backupPath;
    }
}
