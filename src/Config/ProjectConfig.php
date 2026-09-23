<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Config;

use CiHispano\Config;
use CiHispano\Util\PathBuilder;
use JsonException;
use RuntimeException;

/**
 * ProjectConfig.
 *
 * Loads and validates the optional per-project configuration file (git-hooks.json).
 * A missing file resolves to defaults; an invalid file fails loudly instead of
 * falling back silently.
 */
final class ProjectConfig
{
    /**
     * Configuration file name expected in the project root.
     */
    public const FILE_NAME = 'git-hooks.json';

    /**
     * Default build directory name (matches Config::BUILD_DIR).
     */
    public const DEFAULT_BUILD_DIR = Config::BUILD_DIR;

    private function __construct(
        private readonly bool $autoInstall,
        private readonly string $buildDir,
    ) {
    }

    /**
     * Load the project configuration from the given base path (or the current
     * working directory when no base path is provided).
     *
     * @throws RuntimeException when the file exists but cannot be read, parsed or validated
     */
    public static function load(?string $basePath = null): self
    {
        $basePath   = $basePath ?? (\getcwd() ?: '.');
        $configPath = PathBuilder::join($basePath, self::FILE_NAME);

        if (! \is_file($configPath)) {
            return new self(false, self::DEFAULT_BUILD_DIR);
        }

        $data = self::decode($configPath);

        $autoInstall = $data['auto_install'] ?? false;
        $buildDir    = $data['build_dir'] ?? self::DEFAULT_BUILD_DIR;

        if (! \is_bool($autoInstall)) {
            throw new RuntimeException(
                self::invalidMessage($configPath, '"auto_install" must be a boolean'),
            );
        }

        if (! \is_string($buildDir) || '' === \trim($buildDir)) {
            throw new RuntimeException(
                self::invalidMessage($configPath, '"build_dir" must be a non-empty string'),
            );
        }

        return new self($autoInstall, $buildDir);
    }

    /**
     * Create the default git-hooks.json file in the given base path.
     *
     * @throws RuntimeException when the file already exists or cannot be written
     */
    public static function writeDefault(?string $basePath = null): string
    {
        $basePath   = $basePath ?? (\getcwd() ?: '.');
        $configPath = PathBuilder::join($basePath, self::FILE_NAME);

        if (\file_exists($configPath)) {
            throw new RuntimeException(
                self::FILE_NAME . ' already exists: ' . $configPath,
            );
        }

        $json = \json_encode(
            [
                'auto_install' => false,
                'build_dir'    => self::DEFAULT_BUILD_DIR,
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        if (! \file_put_contents($configPath, $json . PHP_EOL)) {
            throw new RuntimeException('Unable to write ' . self::FILE_NAME . ': ' . $configPath);
        }

        return $configPath;
    }

    /**
     * Whether hooks should be installed automatically on composer install/update.
     */
    public function autoInstall(): bool
    {
        return $this->autoInstall;
    }

    /**
     * Build directory name used for QA cache artifacts.
     */
    public function buildDir(): string
    {
        return $this->buildDir;
    }

    /**
     * Read and decode the configuration file.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException when the file cannot be read, parsed or is not an object
     */
    private static function decode(string $configPath): array
    {
        $contents = @\file_get_contents($configPath);

        if (false === $contents) {
            throw new RuntimeException(
                'Unable to read ' . self::FILE_NAME . ': ' . $configPath,
            );
        }

        try {
            $decoded = \json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException(
                self::invalidMessage($configPath, $exception->getMessage()),
                0,
                $exception,
            );
        }

        if (! \is_array($decoded)) {
            throw new RuntimeException(
                self::invalidMessage($configPath, 'expected a JSON object'),
            );
        }

        if ($decoded !== [] && \array_is_list($decoded)) {
            throw new RuntimeException(
                self::invalidMessage($configPath, 'expected a JSON object'),
            );
        }

        $normalized = [];

        foreach ($decoded as $key => $value) {
            if (! \is_string($key)) {
                throw new RuntimeException(
                    self::invalidMessage($configPath, 'expected a JSON object'),
                );
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private static function invalidMessage(string $configPath, string $reason): string
    {
        return \sprintf(
            'Invalid %s (%s): %s.',
            self::FILE_NAME,
            $configPath,
            $reason,
        );
    }
}
