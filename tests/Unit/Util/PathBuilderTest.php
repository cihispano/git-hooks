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

use CiHispano\Util\PathBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 */
final class PathBuilderTest extends TestCase
{
    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new ReflectionClass(PathBuilder::class);

        $this->assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            $this->assertFalse($constructor->isPublic(), 'Constructor should not be public if it exists');

            $instance = $reflection->newInstanceWithoutConstructor();
            $constructor->setAccessible(true);
            $constructor->invoke($instance);

            $this->assertInstanceOf(PathBuilder::class, $instance);
        }
    }

    public function testJoinCombinesDirectoryAndFileName(): void
    {
        $result = PathBuilder::join('project' . \DIRECTORY_SEPARATOR . 'src', 'index.php');

        $this->assertSame(
            'project' . \DIRECTORY_SEPARATOR . 'src' . \DIRECTORY_SEPARATOR . 'index.php',
            $result,
        );
    }

    public function testJoinNormalizesMixedSeparators(): void
    {
        $result = PathBuilder::join('foo/bar\\baz/', '/qux/test.php');

        $this->assertSame(
            'foo' . \DIRECTORY_SEPARATOR .
            'bar' . \DIRECTORY_SEPARATOR .
            'baz' . \DIRECTORY_SEPARATOR .
            'qux' . \DIRECTORY_SEPARATOR .
            'test.php',
            $result,
        );
    }

    public function testJoinSupportsStreamWrappers(): void
    {
        $this->assertSame('vfs://root/hooks/pre-commit', PathBuilder::join('vfs://root/hooks/', '/pre-commit'));
    }

    public function testNormalizeRemovesTrailingSeparator(): void
    {
        $result = PathBuilder::normalize('foo/bar\\baz/');

        $this->assertSame(
            'foo' . \DIRECTORY_SEPARATOR . 'bar' . \DIRECTORY_SEPARATOR . 'baz',
            $result,
        );
    }

    public function testNormalizeKeepsStreamWrapperFormat(): void
    {
        $this->assertSame('vfs://root/hooks', PathBuilder::normalize('vfs://root/hooks/'));
    }

    public function testCanonicalizeKeepsStreamWrapperFormat(): void
    {
        $this->assertSame('vfs://root/hooks', PathBuilder::canonicalize('vfs://root/hooks/'));
    }

    public function testCanonicalizeResolvesDotSegmentsInAbsolutePath(): void
    {
        $this->assertSame(
            \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . 'www' . \DIRECTORY_SEPARATOR . 'project',
            PathBuilder::canonicalize('/var/www/./project/../project'),
        );
    }

    public function testCanonicalizeResolvesWindowsDrivePrefix(): void
    {
        $this->assertSame(
            'C:' . \DIRECTORY_SEPARATOR . 'src',
            PathBuilder::canonicalize('C:\\project\\..\\src'),
        );
    }

    public function testCanonicalizePreservesLeadingDoubleDotsInRelativePath(): void
    {
        $this->assertSame(
            '..' . \DIRECTORY_SEPARATOR . 'pre-commit',
            PathBuilder::canonicalize('../hooks/../pre-commit'),
        );
    }

    public function testCanonicalizeKeepsEmptyPathEmpty(): void
    {
        $this->assertSame('', PathBuilder::canonicalize(''));
    }

    public function testCanonicalizeResolvesCollapsingPathToCurrentDirectory(): void
    {
        $this->assertSame('.', PathBuilder::canonicalize('hooks/..'));
        $this->assertSame('.', PathBuilder::canonicalize('./'));
    }

    public function testJoinMultipleBuildsPathFromSeveralSegments(): void
    {
        $result = PathBuilder::joinMultiple('foo/bar/', '\\baz', 'hooks', 'pre-commit');

        $this->assertSame(
            'foo' . \DIRECTORY_SEPARATOR .
            'bar' . \DIRECTORY_SEPARATOR .
            'baz' . \DIRECTORY_SEPARATOR .
            'hooks' . \DIRECTORY_SEPARATOR .
            'pre-commit',
            $result,
        );
    }

    public function testJoinMultipleReturnsEmptyStringWithoutSegments(): void
    {
        $this->assertSame('', PathBuilder::joinMultiple());
    }

    #[DataProvider('provideIsAbsoluteDetectsAbsolutePaths')]
    public function testIsAbsoluteDetectsAbsolutePaths(string $path): void
    {
        $this->assertTrue(PathBuilder::isAbsolute($path));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideIsAbsoluteDetectsAbsolutePaths(): iterable
    {
        return [
            'unix path'               => ['/var/www/project'],
            'windows backslashes'     => ['C:\\projects\\repo'],
            'windows forward slashes' => ['D:/projects/repo'],
        ];
    }

    #[DataProvider('provideIsAbsoluteReturnsFalseForRelativePaths')]
    public function testIsAbsoluteReturnsFalseForRelativePaths(string $path): void
    {
        $this->assertFalse(PathBuilder::isAbsolute($path));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideIsAbsoluteReturnsFalseForRelativePaths(): iterable
    {
        return [
            'simple relative path' => ['src/Hooks/pre-commit'],
            'dot relative path'    => ['./src/Hooks'],
            'bare segment'         => ['hooks'],
        ];
    }
}
