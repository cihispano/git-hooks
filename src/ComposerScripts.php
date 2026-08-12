<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano;

use CiHispano\Config\ProjectConfig;
use CiHispano\Util\FileComparator;
use CiHispano\Util\FilePermissions;
use CiHispano\Util\GitRepository;
use CiHispano\Util\PathBuilder;
use Exception;
use RuntimeException;

/**
 * ComposerScripts.
 *
 * Handles Composer script hooks for Git integration
 */
final class ComposerScripts
{
    /**
     * Install the Git hooks.
     *
     * @param mixed $event Composer event or base path string
     *
     * @throws RuntimeException
     */
    public static function install(mixed $event = null): void
    {
        $basePath = \is_string($event) ? $event : (\getcwd() ?: '.');

        self::ensureBuildDirectory($basePath);

        if (! GitRepository::isGitRepository($basePath)) {
            ConsoleLogger::warning(
                'This directory is not a Git repository. Aborting hook installation.',
                true,
            );

            return;
        }

        ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_INFO);
        ConsoleLogger::header('Installing Git Hooks', Config::COLOR_INFO);
        ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_INFO);
        ConsoleLogger::newLine();

        try {
            self::validateGitHooksDirectoryExists($basePath);
            self::validateGitHooksDirectoryWritable($basePath);
            self::copyHooks($basePath);

            ConsoleLogger::newLine();
            ConsoleLogger::success('Git hooks installed successfully', true);
            ConsoleLogger::newLine();

            ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_INFO);
            ConsoleLogger::info('Hooks will run automatically on git operations', true);
            ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_INFO);
        } catch (Exception $e) {
            ConsoleLogger::newLine();
            ConsoleLogger::error('Error: ' . $e->getMessage());
            ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_ERROR);

            throw new RuntimeException(
                'Failed to install git hooks. Check the errors above.',
                0,
                $e,
            );
        }
    }

    /**
     * Uninstall Git hooks.
     *
     * @param mixed $event Composer event or base path string
     *
     * @throws RuntimeException
     */
    public static function uninstall(mixed $event = null): void
    {
        $basePath = \is_string($event) ? $event : null;

        ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_ERROR);
        ConsoleLogger::header('Uninstalling Git Hooks', Config::COLOR_ERROR);
        ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_ERROR);
        ConsoleLogger::newLine();

        try {
            $gitHooksDir = self::getGitHooksDir($basePath);
            $hooks       = self::getExistingHookFiles($gitHooksDir);

            if (empty($hooks)) {
                ConsoleLogger::info('No hooks to uninstall', true);
                ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_ERROR);

                return;
            }

            foreach ($hooks as $hookFile) {
                self::removeSingleHook($hookFile, $gitHooksDir);
            }

            ConsoleLogger::newLine();
            ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_SUCCESS);
            ConsoleLogger::success('Git hooks uninstalled successfully', true);
            ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_SUCCESS);
        } catch (Exception $e) {
            ConsoleLogger::newLine();
            ConsoleLogger::error('Uninstall error: ' . $e->getMessage());
            ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_ERROR);

            throw new RuntimeException(
                'Failed to uninstall git hooks',
                0,
                $e,
            );
        }
    }

    /**
     * Composer post-install wrapper.
     *
     * Gated by ProjectConfig::autoInstall(): hooks are installed only when the
     * project opts in via git-hooks.json. Explicit installation via the
     * `install-hooks` script is always available.
     */
    public static function postInstall(): void
    {
        self::installIfAutoConfigured();
    }

    /**
     * Composer post-update wrapper.
     *
     * Gated by ProjectConfig::autoInstall(): hooks are installed only when the
     * project opts in via git-hooks.json. Explicit installation via the
     * `install-hooks` script is always available.
     */
    public static function postUpdate(): void
    {
        self::installIfAutoConfigured();
    }

    /**
     * Generate the default git-hooks.json file in the project root.
     *
     * @param string|null $basePath Optional base path for the file
     *
     * @throws RuntimeException If the file already exists or cannot be written
     */
    public static function initHooks(?string $basePath = null): void
    {
        $basePath   = $basePath ?? (\getcwd() ?: '.');
        $configPath = ProjectConfig::writeDefault($basePath);

        ConsoleLogger::success(
            'Created ' . ProjectConfig::FILE_NAME . ' at ' . $configPath,
            true,
        );
    }

    /**
     * Install hooks automatically only when the project opts in.
     */
    private static function installIfAutoConfigured(): void
    {
        $config = ProjectConfig::load();

        if (! $config->autoInstall()) {
            ConsoleLogger::info(
                'Auto-install is disabled. Set "auto_install": true in '
                . ProjectConfig::FILE_NAME . ' to install hooks automatically.',
                true,
            );

            return;
        }

        self::install();
    }

    /**
     * Get existing hook files from the Git hooks directory.
     *
     * @param string $gitHooksDir Path to the hooks directory
     *
     * @return list<string>
     *
     * @throws RuntimeException If the directory exists but cannot be read
     */
    private static function getExistingHookFiles(string $gitHooksDir): array
    {
        if (! \is_dir($gitHooksDir)) {
            return [];
        }

        $entries = @\scandir($gitHooksDir);

        if (false === $entries) {
            throw new RuntimeException(
                'Failed to read git hooks directory: ' . PathBuilder::normalize($gitHooksDir),
            );
        }

        return \array_values(\array_filter(
            $entries,
            static function (string $entry) use ($gitHooksDir): bool {
                $fullPath = PathBuilder::join($gitHooksDir, $entry);

                return ! \str_starts_with($entry, '.')
                    && ! \str_ends_with($entry, '.sample')
                    && \is_file($fullPath);
            },
        ));
    }

    /**
     * Get the hook files from the source directory or fail if none found.
     *
     * @return list<string>
     *
     * @throws RuntimeException
     */
    private static function getHookFilesOrFail(): array
    {
        $hooksSource = self::getHooksSourceDir();

        $entries = @\scandir($hooksSource);

        if (false === $entries) {
            throw new RuntimeException("Unable to read hooks source directory: {$hooksSource}");
        }

        $hooks = \array_values(\array_filter(
            $entries,
            static function (string $entry) use ($hooksSource): bool {
                $fullPath = PathBuilder::join($hooksSource, $entry);

                return ! \str_starts_with($entry, '.')
                    && \is_file($fullPath)
                    && ! \str_contains($entry, '.');
            },
        ));

        if (empty($hooks)) {
            throw new RuntimeException("No valid hook files found in: {$hooksSource}");
        }

        return $hooks;
    }

    /**
     * Validate that Git hooks directory exists or create it if possible.
     *
     * @throws RuntimeException
     */
    private static function validateGitHooksDirectoryExists(?string $basePath = null): void
    {
        $gitHooksDir = self::getGitHooksDir($basePath);

        if (! \is_dir($gitHooksDir)) {
            if (! @\mkdir($gitHooksDir, FilePermissions::DIR_DEFAULT, true) && ! \is_dir($gitHooksDir)) {
                throw new RuntimeException(
                    'Git hooks directory not found and could not be created. '
                    . "Make sure you are in a git repository: {$gitHooksDir}",
                );
            }

            ConsoleLogger::info("Created missing hooks directory: {$gitHooksDir}", true);
        }
    }

    /**
     * Validate that Git hooks directory is writable.
     *
     * @throws RuntimeException
     */
    private static function validateGitHooksDirectoryWritable(?string $basePath = null): void
    {
        $gitHooksDir = self::getGitHooksDir($basePath);

        if (! \is_writable($gitHooksDir)) {
            throw new RuntimeException(
                'Git hooks directory is not writable: ' . PathBuilder::normalize($gitHooksDir),
            );
        }
    }

    /**
     * Copy hooks from source to git directory.
     */
    private static function copyHooks(?string $basePath = null): void
    {
        $hooks       = self::getHookFilesOrFail();
        $hooksDir    = self::getGitHooksDir($basePath);
        $destination = PathBuilder::normalize($hooksDir);

        ConsoleLogger::step(1, 2, "Copying hooks to {$destination}");
        ConsoleLogger::newLine();

        foreach ($hooks as $hookFile) {
            self::installSingleHook($hookFile, $basePath);
        }

        ConsoleLogger::newLine();
        ConsoleLogger::step(2, 2, 'Hooks copied and permissions processed');
    }

    /**
     * Install a single hook file.
     *
     * @throws RuntimeException
     */
    private static function installSingleHook(
        string $fileName,
        ?string $basePath = null,
    ): void {
        $sourcePath = PathBuilder::join(self::getHooksSourceDir(), $fileName);
        $destPath   = PathBuilder::join(self::getGitHooksDir($basePath), $fileName);

        if (! \file_exists($sourcePath)) {
            throw new RuntimeException("Source hook file not found: {$sourcePath}");
        }

        if (\file_exists($destPath)) {
            if (FileComparator::areIdentical($sourcePath, $destPath)) {
                ConsoleLogger::info("Hook '{$fileName}' already up to date", true);

                return;
            }

            ConsoleLogger::warning("Overwriting existing hook: {$fileName}", true);
        }

        if (! \copy($sourcePath, $destPath)) {
            throw new RuntimeException("Failed to copy hook: {$fileName}");
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            if (! \chmod($destPath, FilePermissions::FILE_EXECUTABLE)) {
                ConsoleLogger::warning("Could not set execute permissions for: {$fileName}");
            }
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
    private static function removeSingleHook(
        string $hookFile,
        string $gitHooksDir,
    ): void {
        $hookPath = PathBuilder::join($gitHooksDir, $hookFile);

        if (! \file_exists($hookPath)) {
            ConsoleLogger::info("Hook '{$hookFile}' not found, skipping", true);

            return;
        }

        if (! @\unlink($hookPath)) {
            throw new RuntimeException(
                "Failed to remove hook: {$hookFile}. "
                . 'The file might be in use or you lack sufficient permissions.',
            );
        }

        ConsoleLogger::success("Removed hook: {$hookFile}", true);
    }

    /**
     * Get the hooks source directory from the package.
     *
     * The source always lives inside the installed package (dirname(__DIR__)),
     * never inside the consumer project. Only the destination (getGitHooksDir)
     * depends on the project where hooks are being installed.
     *
     * @throws RuntimeException
     */
    private static function getHooksSourceDir(): string
    {
        $root = \dirname(__DIR__);

        $path = PathBuilder::joinMultiple(
            $root,
            Config::SRC_DIR,
            Config::HOOKS_SOURCE_DIR,
        );

        if (! \is_dir($path)) {
            throw new RuntimeException(
                "Hooks source directory does not exist: {$path}. "
                . 'Check if the library structure is correct.',
            );
        }

        return $path;
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
    private static function getGitHooksDir(?string $basePath = null): string
    {
        return GitRepository::resolveHooksDir($basePath);
    }

    /**
     * Ensure that the 'build' cache directory exists in the project root.
     *
     * Static analysis tools (PHPStan, PHP CS Fixer, PHPUnit) are configured
     * to write their temporary/cache files into this folder. Creating it
     * automatically prevents "directory not found" errors during hook execution.
     *
     * @param string $basePath the project's base directory
     *
     * @throws RuntimeException if the directory doesn't exist and cannot be created
     */
    private static function ensureBuildDirectory(string $basePath): void
    {
        if (! \is_dir($basePath)) {
            throw new RuntimeException("Base path does not exist or is not a directory: {$basePath}");
        }

        $buildDir  = ProjectConfig::load($basePath)->buildDir();
        $buildPath = PathBuilder::join($basePath, $buildDir);

        if (\is_dir($buildPath)) {
            return;
        }

        if (! \mkdir($buildPath, FilePermissions::DIR_DEFAULT, true)) {
            throw new RuntimeException(
                \sprintf(
                    'Failed to create build directory at "%s". Check permissions or filesystem state.',
                    $buildPath,
                ),
            );
        }
    }
}
