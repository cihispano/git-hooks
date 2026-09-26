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
use CiHispano\Installer\HookManager;
use CiHispano\Util\Filesystem;
use Exception;
use RuntimeException;

/**
 * ComposerScripts.
 *
 * Handles Composer script hooks for Git integration.
 *
 * This class is a thin facade (thin orchestrator): it exposes the entry
 * points Composer invokes, owns their orchestration and console
 * presentation, and delegates every filesystem, path and Git operation to
 * the HookManager and Filesystem collaborators.
 */
final class ComposerScripts
{
    /**
     * Install the Git hooks.
     *
     * Hooks whose content differs from the package version are backed up to
     * `<hook>.bak` before being overwritten, so local customizations are
     * preserved.
     *
     * @param mixed $event Composer event or base path string
     *
     * @throws RuntimeException
     */
    public static function install(mixed $event = null): void
    {
        $basePath    = \is_string($event) ? $event : null;
        $hookManager = self::createHookManager();

        $hookManager->ensureBuildDirectory($basePath);

        if (! $hookManager->isGitRepository($basePath)) {
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
            $hookManager->ensureHooksDirectoryExists($basePath);
            $hookManager->ensureHooksDirectoryWritable($basePath);
            $hookManager->installHooks($basePath);

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
     * Only the hooks shipped by this package (Config::DEFAULT_HOOKS) are
     * removed: foreign hooks, samples, hidden files and `.bak` backups are
     * left untouched.
     *
     * @param mixed $event Composer event or base path string
     *
     * @throws RuntimeException
     */
    public static function uninstall(mixed $event = null): void
    {
        $basePath    = \is_string($event) ? $event : null;
        $hookManager = self::createHookManager();

        ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_ERROR);
        ConsoleLogger::header('Uninstalling Git Hooks', Config::COLOR_ERROR);
        ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_ERROR);
        ConsoleLogger::newLine();

        try {
            $gitHooksDir = $hookManager->resolveHooksDirectory($basePath);
            $hooks       = $hookManager->listInstalledHooks($gitHooksDir);

            if (empty($hooks)) {
                ConsoleLogger::info('No hooks to uninstall', true);
                ConsoleLogger::separator(Config::SEPARATOR_LENGTH, Config::COLOR_ERROR);

                return;
            }

            foreach ($hooks as $hookFile) {
                $hookManager->removeHook($hookFile, $gitHooksDir);
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
     * Create the collaborators that own filesystem, path and Git work.
     */
    private static function createHookManager(): HookManager
    {
        return new HookManager(new Filesystem());
    }
}
