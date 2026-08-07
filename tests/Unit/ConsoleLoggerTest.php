<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Unit;

use CiHispano\Config;
use CiHispano\ConsoleLogger;
use PHPUnit\Framework\TestCase;

final class ConsoleLoggerTest extends TestCase
{
    /**
     * @var resource
     */
    private $output;

    protected function setUp(): void
    {
        $this->output = \fopen('php://memory', 'r+');
        ConsoleLogger::setOutputStream($this->output);
        \putenv('NO_COLOR');
    }

    protected function tearDown(): void
    {
        ConsoleLogger::setOutputStream(null);
        \fclose($this->output);
    }

    public function testErrorOutputsMessage(): void
    {
        ConsoleLogger::error('Test error');

        $output = $this->clean();

        self::assertStringContainsString('ERROR', $output);
        self::assertStringContainsString('Test error', $output);
    }

    public function testErrorWithoutIcon(): void
    {
        ConsoleLogger::error('No icon', false);

        $output = $this->clean();

        self::assertStringContainsString('No icon', $output);
    }

    public function testInfoOutputsMessage(): void
    {
        ConsoleLogger::info('Test info');

        self::assertStringContainsString('Test info', $this->clean());
    }

    public function testSuccessOutputsMessage(): void
    {
        ConsoleLogger::success(Config::COLOR_SUCCESS);

        self::assertStringContainsString(Config::COLOR_SUCCESS, $this->clean());
    }

    public function testWarningOutputsMessage(): void
    {
        ConsoleLogger::warning(Config::COLOR_WARNING);

        self::assertStringContainsString(Config::COLOR_WARNING, $this->clean());
    }

    public function testHeaderOutputsText(): void
    {
        ConsoleLogger::header('My Header', Config::COLOR_INFO);

        self::assertStringContainsString('My Header', $this->clean());
    }

    public function testBoxOutputsContent(): void
    {
        ConsoleLogger::box('Box content');

        self::assertStringContainsString('Box content', $this->clean());
    }

    public function testPanelOutputsTitleAndContent(): void
    {
        ConsoleLogger::panel('Panel Title', 'Panel Content');

        $output = $this->clean();

        self::assertStringContainsString('Panel Title', $output);
        self::assertStringContainsString('Panel Content', $output);
    }

    public function testListItemOutputsMessage(): void
    {
        ConsoleLogger::listItem('Item');

        self::assertStringContainsString('Item', $this->clean());
    }

    public function testAskOutputsQuestion(): void
    {
        ConsoleLogger::ask('Are you sure?');

        self::assertStringContainsString('Are you sure?', $this->clean());
    }

    public function testStepOutputsCorrectFormat(): void
    {
        ConsoleLogger::step(2, 5, 'Processing');

        $output = $this->clean();

        self::assertStringContainsString('[2/5]', $output);
        self::assertStringContainsString('Processing', $output);
    }

    public function testSeparatorOutputsCorrectLength(): void
    {
        ConsoleLogger::separator(10);

        self::assertStringContainsString(\str_repeat('-', 10), $this->clean());
    }

    public function testNewLineOutputsBreak(): void
    {
        ConsoleLogger::newLine();

        self::assertNotEmpty($this->clean());
    }

    public function testNoColorDisablesAnsiSequences(): void
    {
        \putenv('NO_COLOR=1');

        ConsoleLogger::header('Header', Config::COLOR_INFO);

        $output = $this->clean();

        self::assertStringContainsString('Header', $output);
        self::assertStringNotContainsString("\033[", $output);
    }

    private function clean(): string
    {
        \rewind($this->output);

        return \preg_replace('/\e\[[\d;]*m/', '', \stream_get_contents($this->output)) ?? '';
    }
}
