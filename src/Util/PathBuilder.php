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
 * PathBuilder.
 *
 * Utility class for building and manipulating file system paths
 */
final class PathBuilder
{
    /**
     * Private constructor to prevent instantiation.
     * This class should only be used for its constants.
     */
    private function __construct()
    {
    }

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
        $fileName  = \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $fileName);

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
     * Canonicalize a path by resolving '.' and '..' segments lexically.
     *
     * Unlike realpath(), it does not require the file to exist and does not
     * traverse symlinks. Absolute and relative paths are preserved. An empty
     * input stays empty, and a non-empty input that collapses to nothing (for
     * example `'hooks/..'`) resolves to the current directory ('.').
     */
    public static function canonicalize(string $path): string
    {
        if (self::isStreamWrapper($path)) {
            return self::normalize($path);
        }

        $drivePrefix  = '';
        $windowsMatch = [];

        if (\preg_match('/^(.):[\\\\\/](.*)$/', $path, $windowsMatch) === 1) {
            $drivePrefix = \strtoupper($windowsMatch[1]) . ':' . \DIRECTORY_SEPARATOR;
            $path        = $windowsMatch[2];
        }

        $isAbsolute = self::isAbsolute($path);
        $segments   = \array_filter(
            \preg_split('#[\\\\/]#', $path) ?: [],
            static fn (string $segment): bool => '' !== $segment && '.' !== $segment,
        );

        $resolved = [];

        foreach ($segments as $segment) {
            if ('..' === $segment) {
                if ([] !== $resolved && \end($resolved) !== '..') {
                    \array_pop($resolved);

                    continue;
                }

                if (! $isAbsolute) {
                    $resolved[] = $segment;
                }

                continue;
            }

            $resolved[] = $segment;
        }

        $prefix = $drivePrefix !== '' ? $drivePrefix : ($isAbsolute ? \DIRECTORY_SEPARATOR : '');

        $result = $prefix . \implode(\DIRECTORY_SEPARATOR, $resolved);

        if ('' === $prefix && '' === $result && '' !== $path) {
            return '.';
        }

        return $result;
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
     * Determine whether the given path uses a stream wrapper.
     */
    private static function isStreamWrapper(string $path): bool
    {
        return (bool) \preg_match('#^[a-z][a-z0-9+.-]*://#i', $path);
    }
}
