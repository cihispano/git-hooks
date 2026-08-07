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
use ReflectionClass;

/**
 * @internal
 */
final class FilePermissionsTest extends TestCase
{
    public function testDirNoAccessValue(): void
    {
        $this->assertSame(0o000, FilePermissions::DIR_NO_ACCESS);
    }

    public function testDirReadOnlyValue(): void
    {
        $this->assertSame(0o555, FilePermissions::DIR_READ_ONLY);
    }

    public function testDirPrivateValue(): void
    {
        $this->assertSame(0o700, FilePermissions::DIR_PRIVATE);
    }

    public function testDirDefaultValue(): void
    {
        $this->assertSame(0o755, FilePermissions::DIR_DEFAULT);
    }

    public function testDirSharedWritableValue(): void
    {
        $this->assertSame(0o775, FilePermissions::DIR_SHARED_WRITABLE);
    }

    public function testDirWorldWritableValue(): void
    {
        $this->assertSame(0o777, FilePermissions::DIR_WORLD_WRITABLE);
    }

    public function testFilePrivateValue(): void
    {
        $this->assertSame(0o600, FilePermissions::FILE_PRIVATE);
    }

    public function testFileSharedReadValue(): void
    {
        $this->assertSame(0o644, FilePermissions::FILE_SHARED_READ);
    }

    public function testFileExecutableValue(): void
    {
        $this->assertSame(0o755, FilePermissions::FILE_EXECUTABLE);
    }

    public function testFileReadOnlyValue(): void
    {
        $this->assertSame(0o444, FilePermissions::FILE_READ_ONLY);
    }

    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new ReflectionClass(FilePermissions::class);

        $this->assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            $this->assertFalse(
                $constructor->isPublic(),
                'Constructor should not be public if it exists',
            );

            $instance = $reflection->newInstanceWithoutConstructor();
            $constructor->setAccessible(true);
            $constructor->invoke($instance);

            $this->assertInstanceOf(FilePermissions::class, $instance);
        }
    }

    public function testAllConstantsArePublic(): void
    {
        $reflection = new ReflectionClass(FilePermissions::class);
        $constants  = $reflection->getReflectionConstants();

        foreach ($constants as $constant) {
            $this->assertTrue(
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
            'FILE_READ_ONLY',
        ];

        $reflection = new ReflectionClass(FilePermissions::class);
        $defined    = \array_keys($reflection->getConstants());

        foreach ($expected as $name) {
            $this->assertContains($name, $defined, "Missing constant: {$name}");
        }
    }

    public function testAllConstantsAreIntegers(): void
    {
        $reflection = new ReflectionClass(FilePermissions::class);

        foreach ($reflection->getConstants() as $name => $value) {
            $this->assertIsInt($value, "Constant {$name} should be int");
        }
    }
}
