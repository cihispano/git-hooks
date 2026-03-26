<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Unit\Util;

use CiHispano\Util\FilePermissions;
use PHPUnit\Framework\TestCase;

final class FilePermissionsTest extends TestCase
{
    public function testDirNoAccessIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::DIR_NO_ACCESS'));
    }

    public function testDirNoAccessValue(): void
    {
        self::assertSame(0o000, FilePermissions::DIR_NO_ACCESS);
    }

    public function testDirReadOnlyIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::DIR_READ_ONLY'));
    }

    public function testDirReadOnlyValue(): void
    {
        self::assertSame(0o555, FilePermissions::DIR_READ_ONLY);
    }

    public function testDirPrivateIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::DIR_PRIVATE'));
    }

    public function testDirPrivateValue(): void
    {
        self::assertSame(0o700, FilePermissions::DIR_PRIVATE);
    }

    public function testDirDefaultIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::DIR_DEFAULT'));
    }

    public function testDirDefaultValue(): void
    {
        self::assertSame(0o755, FilePermissions::DIR_DEFAULT);
    }

    public function testDirSharedWritableIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::DIR_SHARED_WRITABLE'));
    }

    public function testDirSharedWritableValue(): void
    {
        self::assertSame(0o775, FilePermissions::DIR_SHARED_WRITABLE);
    }

    public function testDirWorldWritableIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::DIR_WORLD_WRITABLE'));
    }

    public function testDirWorldWritableValue(): void
    {
        self::assertSame(0o777, FilePermissions::DIR_WORLD_WRITABLE);
    }

    public function testFilePrivateIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::FILE_PRIVATE'));
    }

    public function testFilePrivateValue(): void
    {
        self::assertSame(0o600, FilePermissions::FILE_PRIVATE);
    }

    public function testFileSharedReadIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::FILE_SHARED_READ'));
    }

    public function testFileSharedReadValue(): void
    {
        self::assertSame(0o644, FilePermissions::FILE_SHARED_READ);
    }

    public function testFileExecutableIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::FILE_EXECUTABLE'));
    }

    public function testFileExecutableValue(): void
    {
        self::assertSame(0o755, FilePermissions::FILE_EXECUTABLE);
    }

    public function testFileReadOnlyIsDefined(): void
    {
        self::assertTrue(\defined(FilePermissions::class . '::FILE_READ_ONLY'));
    }

    public function testFileReadOnlyValue(): void
    {
        self::assertSame(0o444, FilePermissions::FILE_READ_ONLY);
    }

    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(FilePermissions::class);

        self::assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            self::assertFalse(
                $constructor->isPublic(),
                'Constructor should not be public if it exists',
            );

            $instance = $reflection->newInstanceWithoutConstructor();
            $constructor->setAccessible(true);
            $constructor->invoke($instance);

            self::assertInstanceOf(FilePermissions::class, $instance);
        }
    }

    public function testAllConstantsArePublic(): void
    {
        $reflection = new \ReflectionClass(FilePermissions::class);
        $constants = $reflection->getReflectionConstants();

        foreach ($constants as $constant) {
            self::assertTrue(
                $constant->isPublic(),
                "Constant {$constant->getName()} should be public",
            );
        }
    }

    public function testRequiredConstantsExist(): void
    {
        $expected = [
            'DIR_NO_ACCESS',
            'DIR_READ_ONLY',
            'DIR_PRIVATE',
            'DIR_DEFAULT',
            'DIR_SHARED_WRITABLE',
            'DIR_WORLD_WRITABLE',
            'FILE_PRIVATE',
            'FILE_SHARED_READ',
            'FILE_EXECUTABLE',
            'FILE_READ_ONLY'
        ];

        $reflection = new \ReflectionClass(FilePermissions::class);
        $defined = array_keys($reflection->getConstants());

        foreach ($expected as $name) {
            self::assertContains($name, $defined, "Missing constant: {$name}");
        }
    }

    public function testAllConstantsAreIntegers(): void
    {
        $reflection = new \ReflectionClass(FilePermissions::class);
        foreach ($reflection->getConstants() as $name => $value) {
            self::assertIsInt($value, "Constant {$name} should be int");
        }
    }
}
