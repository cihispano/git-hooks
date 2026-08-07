<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Unit\Support;

use CiHispano\Support\CliIcons;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @internal
 */
final class CliIconsTest extends TestCase
{
    public function testSuccessIconIsCheckmark(): void
    {
        $this->assertSame('✔', CliIcons::SUCCESS);
    }

    public function testErrorIconIsXMark(): void
    {
        $this->assertSame('✖', CliIcons::ERROR);
    }

    public function testWarningIconIsWarningSign(): void
    {
        $this->assertSame('⚠', CliIcons::WARNING);
    }

    public function testInfoIconIsInformationSign(): void
    {
        $this->assertSame('ℹ', CliIcons::INFO);
    }

    public function testStepIconIsArrow(): void
    {
        $this->assertSame('➤', CliIcons::STEP);
    }

    public function testAskIconIsQuestionMark(): void
    {
        $this->assertSame('?', CliIcons::ASK);
    }

    public function testBulletIconIsBulletPoint(): void
    {
        $this->assertSame('•', CliIcons::BULLET);
    }

    #[DataProvider('provideAllIcons')]
    public function testAllIconsAreNonEmptyStrings(string $iconValue): void
    {
        $this->assertNotEmpty($iconValue, 'Icon should not be empty');
    }

    #[DataProvider('provideAllIcons')]
    public function testAllIconsAreSingleCharacterOrUnicode(string $iconValue): void
    {
        $length = \mb_strlen($iconValue, 'UTF-8');
        $this->assertGreaterThanOrEqual(1, $length, 'Icon should have at least 1 character');
        $this->assertLessThanOrEqual(3, $length, 'Icon should not exceed 3 characters (for emoji)');
    }

    /**
     * Data provider for all icon values.
     *
     * @return array<string, list<string>>
     */
    public static function provideAllIcons(): iterable
    {
        return [
            'SUCCESS' => [CliIcons::SUCCESS],
            'ERROR'   => [CliIcons::ERROR],
            'WARNING' => [CliIcons::WARNING],
            'INFO'    => [CliIcons::INFO],
            'STEP'    => [CliIcons::STEP],
            'ASK'     => [CliIcons::ASK],
            'BULLET'  => [CliIcons::BULLET],
        ];
    }

    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new ReflectionClass(CliIcons::class);

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

            $this->assertInstanceOf(CliIcons::class, $instance);
        }
    }

    public function testAllConstantsArePublic(): void
    {
        $reflection = new ReflectionClass(CliIcons::class);
        $constants  = $reflection->getReflectionConstants();

        foreach ($constants as $constant) {
            $this->assertTrue(
                $constant->isPublic(),
                "Constant {$constant->getName()} should be public",
            );
        }
    }

    public function testAllConstantsContainStringValues(): void
    {
        $reflection = new ReflectionClass(CliIcons::class);
        $constants  = $reflection->getReflectionConstants();

        foreach ($constants as $constant) {
            $this->assertIsString($constant->getValue(), "Constant {$constant->getName()} should be a string");
        }
    }

    public function testIconsAreVisuallyDistinct(): void
    {
        $icons = [
            CliIcons::SUCCESS,
            CliIcons::ERROR,
            CliIcons::WARNING,
            CliIcons::INFO,
            CliIcons::STEP,
            CliIcons::ASK,
            CliIcons::BULLET,
        ];

        $uniqueIcons = \array_unique($icons);

        $this->assertCount(
            \count($icons),
            $uniqueIcons,
            'All icons should be unique/distinct from each other',
        );
    }

    /**
     * Integration test: Icons can be used in console output.
     */
    public function testIconsCanBeConcatenatedWithStrings(): void
    {
        $message = CliIcons::SUCCESS . ' Operation completed';
        $this->assertStringContainsString('✔', $message);
        $this->assertStringContainsString('Operation completed', $message);

        $errorMessage = CliIcons::ERROR . ' Something failed';
        $this->assertStringContainsString('✖', $errorMessage);
    }

    /**
     * Integration test: Icons work with string interpolation.
     */
    public function testIconsWorkWithStringInterpolation(): void
    {
        $status  = 'completed';
        $message = \sprintf('%s Status: %s', CliIcons::INFO, $status);

        $this->assertStringContainsString('ℹ', $message);
        $this->assertStringContainsString('Status: completed', $message);
    }
}
