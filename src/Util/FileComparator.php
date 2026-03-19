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

use CiHispano\Config;

/**
 * FileComparator.
 *
 * Utility class for comparing files using cryptographic hashes
 */
final class FileComparator
{
    /**
     * Private constructor prevents instantiation.
     *
     * This class should only be used for its constants.
     */
    private function __construct()
    {
    }

    /**
     * Check if two files are identical by comparing their hashes.
     *
     * @param string $file1     Path to first file
     * @param string $file2     Path to second file
     * @param string $algorithm Hash algorithm to use (default: sha256)
     *
     * @return bool True if files are identical
     *
     * @throws \RuntimeException If hash calculation fails
     */
    public static function areIdentical(
        string $file1,
        string $file2,
        string $algorithm = Config::DEFAULT_ALGORITHM,
    ): bool {
        $hash1 = self::getHash($file1, $algorithm);
        $hash2 = self::getHash($file2, $algorithm);

        return $hash1 === $hash2;
    }

    /**
     * Calculate hash of a file.
     *
     * @param string $file      Path to file
     * @param string $algorithm Hash algorithm to use (default: sha256)
     *
     * @return string File hash
     *
     * @throws \RuntimeException If file doesn't exist or hash calculation fails
     */
    public static function getHash(
        string $file,
        string $algorithm = Config::DEFAULT_ALGORITHM,
    ): string {
        if (!\file_exists($file)) {
            throw new \RuntimeException("File not found: {$file}");
        }

        if (!\is_readable($file)) {
            throw new \RuntimeException("File is not readable: {$file}");
        }

        $hash = \hash_file($algorithm, $file);

        if (false === $hash) {
            throw new \RuntimeException(
                "Failed to calculate {$algorithm} hash for: {$file}",
            );
        }

        return $hash;
    }

    /**
     * Check if a file matches a given hash.
     *
     * @param string $file         Path to file
     * @param string $expectedHash Expected hash value
     * @param string $algorithm    Hash algorithm to use (default: sha256)
     *
     * @return bool True if file hash matches expected hash
     *
     * @throws \RuntimeException If hash calculation fails
     */
    public static function matchesHash(
        string $file,
        string $expectedHash,
        string $algorithm = Config::DEFAULT_ALGORITHM,
    ): bool {
        $currentHash = self::getHash($file, $algorithm);

        return \hash_equals($expectedHash, $currentHash);
    }

    /**
     * Compare multiple files and return true if all are identical.
     *
     * @param array<string> $files     List of file paths to compare
     * @param string        $algorithm Hash algorithm to use (default: sha256)
     *
     * @return bool True if all files are identical
     *
     * @throws \RuntimeException         If hash calculation fails
     * @throws \InvalidArgumentException If less than 2 files provided
     */
    public static function areAllIdentical(
        array $files,
        string $algorithm = Config::DEFAULT_ALGORITHM,
    ): bool {
        if (\count($files) < 2) {
            throw new \InvalidArgumentException(
                'At least 2 files required for comparison',
            );
        }

        $firstHash = self::getHash($files[0], $algorithm);

        for ($i = 1; $i < \count($files); ++$i) {
            $currentHash = self::getHash($files[$i], $algorithm);

            if ($firstHash !== $currentHash) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of supported hash algorithms.
     *
     * @return array<string> List of supported algorithms
     */
    public static function getSupportedAlgorithms(): array
    {
        return \hash_algos();
    }
}
