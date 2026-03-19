<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Util;

/**
 * PathBuilder.
 *
 * Utility class for building and manipulating file system paths
 */
final class PathBuilder
{
    /**
     * Join directory and filename into a complete path.
     */
    public static function join(string $directory, string $fileName): string
    {
        if (\preg_match('#^[a-z]+://#i', $directory)) {
            return \rtrim($directory, '/')
                . '/'
                . \ltrim($fileName, '/');
        }

        $directory = \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $directory);
        $fileName = \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $fileName);

        return \rtrim($directory, \DIRECTORY_SEPARATOR)
            . \DIRECTORY_SEPARATOR
            . \ltrim($fileName, \DIRECTORY_SEPARATOR);
    }

    /**
     * Normalize a path by fixing separators and removing trailing ones.
     */
    public static function normalize(string $path): string
    {
        if (self::isStreamWrapper($path)) {
            return \rtrim($path, '/');
        }

        $path = \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $path);

        return \rtrim($path, \DIRECTORY_SEPARATOR);
    }

    /**
     * Join multiple path segments.
     */
    public static function joinMultiple(string ...$segments): string
    {
        if (empty($segments)) {
            return '';
        }

        $base = \array_shift($segments);
        $path = self::normalize($base);

        foreach ($segments as $segment) {
            $path = self::join($path, $segment);
        }

        return $path;
    }

    /**
     * Check if a path is absolute.
     */
    public static function isAbsolute(string $path): bool
    {
        return \str_starts_with($path, '/') || \preg_match('/^[a-zA-Z]:[\\\\\/]/', $path);
    }

    /**
     * Detect Git hooks directory path (Cross-platform).
     */
    public static function getHooksPath(): ?string
    {
        if (!\function_exists('shell_exec')) {
            return null;
        }

        $isWindows = PHP_OS_FAMILY === 'Windows';
        $nullDevice = $isWindows ? 'NUL' : '/dev/null';

        $path = @\shell_exec("git rev-parse --git-path hooks 2>{$nullDevice}");

        if (!\is_string($path)) {
            return null;
        }

        $trimmedPath = \trim($path);

        if ('' === $trimmedPath) {
            return null;
        }

        return self::normalize($trimmedPath);
    }

    /**
     * Determine whether the given path uses a stream wrapper.
     */
    private static function isStreamWrapper(string $path): bool
    {
        return (bool) \preg_match('#^[a-z][a-z0-9+.-]*://#i', $path);
    }
}
