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

final class PathBuilderTest extends TestCase
{
    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(PathBuilder::class);

        self::assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            self::assertFalse($constructor->isPublic(), 'Constructor should not be public if it exists');

            $instance = $reflection->newInstanceWithoutConstructor();
            $constructor->setAccessible(true);
            $constructor->invoke($instance);

            self::assertInstanceOf(PathBuilder::class, $instance);
        }
    }

    public function testJoinCombinesDirectoryAndFileName(): void
    {
        $result = PathBuilder::join('project' . \DIRECTORY_SEPARATOR . 'src', 'index.php');

        self::assertSame(
            'project' . \DIRECTORY_SEPARATOR . 'src' . \DIRECTORY_SEPARATOR . 'index.php',
            $result,
        );
    }

    public function testJoinNormalizesMixedSeparators(): void
    {
        $result = PathBuilder::join('foo/bar\\baz/', '/qux/test.php');

        self::assertSame(
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
        self::assertSame('vfs://root/hooks/pre-commit', PathBuilder::join('vfs://root/hooks/', '/pre-commit'));
    }

    public function testNormalizeRemovesTrailingSeparator(): void
    {
        $result = PathBuilder::normalize('foo/bar\\baz/');

        self::assertSame(
            'foo' . \DIRECTORY_SEPARATOR . 'bar' . \DIRECTORY_SEPARATOR . 'baz',
            $result,
        );
    }

    public function testNormalizeKeepsStreamWrapperFormat(): void
    {
        self::assertSame('vfs://root/hooks', PathBuilder::normalize('vfs://root/hooks/'));
    }

    public function testCanonicalizeKeepsStreamWrapperFormat(): void
    {
        self::assertSame('vfs://root/hooks', PathBuilder::canonicalize('vfs://root/hooks/'));
    }

    public function testCanonicalizeResolvesDotSegmentsInAbsolutePath(): void
    {
        self::assertSame(
            \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . 'www' . \DIRECTORY_SEPARATOR . 'project',
            PathBuilder::canonicalize('/var/www/./project/../project'),
        );
    }

    public function testCanonicalizeResolvesWindowsDrivePrefix(): void
    {
        self::assertSame(
            'C:' . \DIRECTORY_SEPARATOR . 'src',
            PathBuilder::canonicalize('C:\\project\\..\\src'),
        );
    }

    public function testCanonicalizePreservesLeadingDoubleDotsInRelativePath(): void
    {
        self::assertSame(
            '..' . \DIRECTORY_SEPARATOR . 'pre-commit',
            PathBuilder::canonicalize('../hooks/../pre-commit'),
        );
    }

    public function testCanonicalizeKeepsEmptyPathEmpty(): void
    {
        self::assertSame('', PathBuilder::canonicalize(''));
    }

    public function testCanonicalizeResolvesCollapsingPathToCurrentDirectory(): void
    {
        self::assertSame('.', PathBuilder::canonicalize('hooks/..'));
        self::assertSame('.', PathBuilder::canonicalize('./'));
    }

    public function testJoinMultipleBuildsPathFromSeveralSegments(): void
    {
        $result = PathBuilder::joinMultiple('foo/bar/', '\\baz', 'hooks', 'pre-commit');

        self::assertSame(
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
        self::assertSame('', PathBuilder::joinMultiple());
    }

    #[DataProvider('absolutePathProvider')]
    public function testIsAbsoluteDetectsAbsolutePaths(string $path): void
    {
        self::assertTrue(PathBuilder::isAbsolute($path));
    }

    #[DataProvider('relativePathProvider')]
    public function testIsAbsoluteReturnsFalseForRelativePaths(string $path): void
    {
        self::assertFalse(PathBuilder::isAbsolute($path));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function absolutePathProvider(): array
    {
        return [
            'unix path' => ['/var/www/project'],
            'windows backslashes' => ['C:\\projects\\repo'],
            'windows forward slashes' => ['D:/projects/repo'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function relativePathProvider(): array
    {
        return [
            'simple relative path' => ['src/Hooks/pre-commit'],
            'dot relative path' => ['./src/Hooks'],
            'bare segment' => ['hooks'],
        ];
    }
}
