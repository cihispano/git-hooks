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

final class CliIconsTest extends TestCase
{
    public function testSuccessIconIsCheckmark(): void
    {
        self::assertSame('✔', CliIcons::SUCCESS);
    }

    public function testErrorIconIsXMark(): void
    {
        self::assertSame('✖', CliIcons::ERROR);
    }
    public function testWarningIconIsWarningSign(): void
    {
        self::assertSame('⚠', CliIcons::WARNING);
    }
    public function testInfoIconIsInformationSign(): void
    {
        self::assertSame('ℹ', CliIcons::INFO);
    }
    public function testStepIconIsArrow(): void
    {
        self::assertSame('➤', CliIcons::STEP);
    }

    public function testAskIconIsQuestionMark(): void
    {
        self::assertSame('?', CliIcons::ASK);
    }

    public function testBulletIconIsBulletPoint(): void
    {
        self::assertSame('•', CliIcons::BULLET);
    }

    #[DataProvider('provideAllIcons')]
    public function testAllIconsAreNonEmptyStrings(string $iconValue): void
    {
        self::assertNotEmpty($iconValue, 'Icon should not be empty');
        self::assertIsString($iconValue, 'Icon should be a string');
    }

    #[DataProvider('provideAllIcons')]
    public function testAllIconsAreSingleCharacterOrUnicode(string $iconValue): void
    {
        $length = mb_strlen($iconValue, 'UTF-8');
        self::assertGreaterThanOrEqual(1, $length, 'Icon should have at least 1 character');
        self::assertLessThanOrEqual(3, $length, 'Icon should not exceed 3 characters (for emoji)');
    }

    public function testClassCannotBeInstantiated(): void
    {
        $reflection = new \ReflectionClass(CliIcons::class);

        self::assertTrue($reflection->isFinal(), 'Class should be final');

        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            self::assertFalse(
                $constructor->isPublic(),
                'Constructor should not be public if it exists',
            );
        }
    }

    public function testAllConstantsArePublic(): void
    {
        $reflection = new \ReflectionClass(CliIcons::class);
        $constants = $reflection->getReflectionConstants();

        foreach ($constants as $constant) {
            self::assertTrue(
                $constant->isPublic(),
                "Constant {$constant->getName()} should be public",
            );
        }
    }

    public function testAllConstantsAreTypedAsString(): void
    {
        $reflection = new \ReflectionClass(CliIcons::class);
        $constants = $reflection->getReflectionConstants();

        foreach ($constants as $constant) {
            $type = $constant->getType();
            self::assertNotNull($type, "Constant {$constant->getName()} should have a type");
            self::assertSame(
                'string',
                (string) $type,
                "Constant {$constant->getName()} should be typed as string",
            );
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

        $uniqueIcons = array_unique($icons);

        self::assertCount(
            \count($icons),
            $uniqueIcons,
            'All icons should be unique/distinct from each other',
        );
    }

    /**
     * Data provider for all icon values.
     *
     * @return array<string, array<string>>
     */
    public static function provideAllIcons(): array
    {
        return [
            'SUCCESS' => [CliIcons::SUCCESS],
            'ERROR' => [CliIcons::ERROR],
            'WARNING' => [CliIcons::WARNING],
            'INFO' => [CliIcons::INFO],
            'STEP' => [CliIcons::STEP],
            'ASK' => [CliIcons::ASK],
            'BULLET' => [CliIcons::BULLET],
        ];
    }

    /**
     * Integration test: Icons can be used in console output.
     */
    public function testIconsCanBeConcatenatedWithStrings(): void
    {
        $message = CliIcons::SUCCESS . ' Operation completed';
        self::assertStringContainsString('✔', $message);
        self::assertStringContainsString('Operation completed', $message);

        $errorMessage = CliIcons::ERROR . ' Something failed';
        self::assertStringContainsString('✖', $errorMessage);
    }

    /**
     * Integration test: Icons work with string interpolation.
     */
    public function testIconsWorkWithStringInterpolation(): void
    {
        $status = 'completed';
        $message = \sprintf('%s Status: %s', CliIcons::INFO, $status);

        self::assertStringContainsString('ℹ', $message);
        self::assertStringContainsString('Status: completed', $message);
    }
}
