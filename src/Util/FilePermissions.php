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
 * Unix/Linux file system permissions constants.
 *
 * This class provides standardized permission constants for directories and files
 * following Unix/Linux permission model (owner, group, others).
 *
 * Permission format: [owner][group][others]
 * Each position can be:
 * - r (read): 4
 * - w (write): 2
 * - x (execute): 1
 */
final class FilePermissions
{
    /**
     * Directory permission: No access.
     *
     * Permission: --------- (0000)
     * - Owner: no access
     * - Group: no access
     * - Others: no access
     *
     * Use case: Extreme restriction scenarios, mainly useful in tests that
     * simulate unreadable or inaccessible directories.
     */
    public const int DIR_NO_ACCESS = 0o000;

    /**
     * Directory permission: Read-only access.
     *
     * Permission: r-xr-xr-x (0555)
     * - Owner: read + execute (can list and access)
     * - Group: read + execute (can list and access)
     * - Others: read + execute (can list and access)
     *
     * Use case: Directories where no one should create, delete, or modify files.
     * Note: Without write permission, files cannot be created or deleted in this directory.
     */
    public const int DIR_READ_ONLY = 0o555;

    /**
     * Directory permission: Private access.
     *
     * Permission: rwx------ (0700)
     * - Owner: read + write + execute (full access)
     * - Group: no access
     * - Others: no access
     *
     * Use case: Sensitive directories where only the owner should have access.
     * Examples: User's private data, configuration directories, cache directories.
     */
    public const int DIR_PRIVATE = 0o700;

    /**
     * Directory permission: Default/Standard access.
     *
     * Permission: rwxr-xr-x (0755)
     * - Owner: read + write + execute (full access)
     * - Group: read + execute (can list and access)
     * - Others: read + execute (can list and access)
     *
     * Use case: Most common directory permission for general use.
     * Examples: Application directories, public directories, hook directories.
     */
    public const int DIR_DEFAULT = 0o755;

    /**
     * Directory permission: Shared writable access.
     *
     * Permission: rwxrwxr-x (0775)
     * - Owner: read + write + execute (full access)
     * - Group: read + write + execute (full access)
     * - Others: read + execute (can list and access)
     *
     * Use case: Collaborative directories where group members need to create/modify files.
     * Examples: Shared project directories, team workspaces, upload directories.
     */
    public const int DIR_SHARED_WRITABLE = 0o775;

    /**
     * Directory permission: World writable access.
     *
     * Permission: rwxrwxrwx (0777)
     * - Owner: read + write + execute (full access)
     * - Group: read + write + execute (full access)
     * - Others: read + write + execute (full access)
     *
     * Use case: Temporary test fixtures or permissive development directories.
     * Avoid in production unless you explicitly need unrestricted access.
     */
    public const int DIR_WORLD_WRITABLE = 0o777;

    /**
     * File permission: Private file.
     *
     * Permission: rw------- (0600)
     * - Owner: read + write
     * - Group: no access
     * - Others: no access
     *
     * Use case: Sensitive files that only the owner should access.
     * Examples: Configuration files with credentials, private keys, tokens.
     */
    public const int FILE_PRIVATE = 0o600;

    /**
     * File permission: Shared readable file.
     *
     * Permission: rw-r--r-- (0644)
     * - Owner: read + write
     * - Group: read only
     * - Others: read only
     *
     * Use case: Standard file permission for general files.
     * Examples: Documentation, public configuration files, logs.
     */
    public const int FILE_SHARED_READ = 0o644;

    /**
     * File permission: Executable file.
     *
     * Permission: rwxr-xr-x (0755)
     * - Owner: read + write + execute (full access)
     * - Group: read + execute
     * - Others: read + execute
     *
     * Use case: Scripts, binaries, and executable files.
     * Examples: Shell scripts, Git hooks, CLI tools, binaries.
     */
    public const int FILE_EXECUTABLE = 0o755;

    /**
     * File permission: Read-only file.
     *
     * Permission: r--r--r-- (0444)
     * - Owner: read only
     * - Group: read only
     * - Others: read only
     *
     * Use case: Files that should never be modified.
     * Examples: Read-only configuration, immutable data files.
     */
    public const int FILE_READ_ONLY = 0o444;

    /**
     * Private constructor to prevent instantiation.
     * This class should only be used for its constants.
     */
    private function __construct()
    {
    }
}
