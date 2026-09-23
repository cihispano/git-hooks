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

use RuntimeException;

/**
 * Filesystem.
 *
 * Single boundary around the native filesystem functions used by the
 * installer (file_exists, is_dir, is_file, is_writable, mkdir, scandir,
 * copy, unlink, chmod...).
 *
 * Every raw filesystem call lives here so collaborators such as HookManager
 * hold domain logic only, and so tests can either exercise this class against
 * real or vfs paths, or replace it with a PHPUnit test double.
 *
 * The class is deliberately not final: PHPUnit cannot mock final classes and
 * mockability is part of its design contract.
 */
class Filesystem
{
    /**
     * Whether the given path exists (file or directory).
     */
    public function exists(string $path): bool
    {
        return \file_exists($path);
    }

    /**
     * Whether the given path is an existing directory.
     *
     * The filesystem can change between calls, so PHPStan must not assume the
     * returned value stays constant (re-checks after a failed mkdir rely on it).
     *
     * @phpstan-impure
     */
    public function isDirectory(string $path): bool
    {
        return \is_dir($path);
    }

    /**
     * Whether the given path is an existing regular file.
     */
    public function isFile(string $path): bool
    {
        return \is_file($path);
    }

    /**
     * Whether the process can write into the given path.
     */
    public function isWritable(string $path): bool
    {
        return \is_writable($path);
    }

    /**
     * List the entries of a directory in native scandir() order.
     *
     * The dot entries (`.` and `..`) are part of the result, mirroring the
     * raw scandir() behaviour the callers already filter.
     *
     * @return false|list<string> false when the directory cannot be read
     */
    public function listDirectory(string $directory): array|false
    {
        $entries = @\scandir($directory);

        if (false === $entries) {
            return false;
        }

        return $entries;
    }

    /**
     * Create a directory and its parents using FilePermissions::DIR_DEFAULT.
     *
     * @param bool $quiet Whether the native mkdir() warning must be suppressed
     *                    on failure (callers decide how loudly failures surface)
     *
     * @return bool true when the directory exists after the call
     */
    public function makeDirectory(string $directory, bool $quiet = false): bool
    {
        if ($quiet) {
            return @\mkdir($directory, FilePermissions::DIR_DEFAULT, true);
        }

        return \mkdir($directory, FilePermissions::DIR_DEFAULT, true);
    }

    /**
     * Copy a file to a new destination.
     */
    public function copy(string $source, string $destination): bool
    {
        return \copy($source, $destination);
    }

    /**
     * Delete a file.
     *
     * The native warning is suppressed because callers report the failure
     * through the returned boolean.
     */
    public function delete(string $path): bool
    {
        return @\unlink($path);
    }

    /**
     * Make a file executable using FilePermissions::FILE_EXECUTABLE.
     *
     * On Windows the executable bit is not meaningful, so the call becomes a
     * successful no-op (the installer never reports a chmod failure there).
     */
    public function makeExecutable(string $path): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return true;
        }

        return \chmod($path, FilePermissions::FILE_EXECUTABLE);
    }

    /**
     * Whether two files hold identical contents (hash-based comparison).
     *
     * @throws RuntimeException when either file cannot be read or hashed
     */
    public function filesAreIdentical(string $first, string $second): bool
    {
        return FileComparator::areIdentical($first, $second);
    }
}
