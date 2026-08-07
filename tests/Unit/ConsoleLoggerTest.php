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

/**
 * @internal
 */
final class ConsoleLoggerTest extends TestCase
{
    /**
     * @var resource
     */
    private $output;

    protected function setUp(): void
    {
        $stream = \fopen('php://memory', 'r+b');
        $this->assertIsResource($stream);

        $this->output = $stream;
        ConsoleLogger::setOutputStream($stream);
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

        $this->assertStringContainsString('ERROR', $output);
        $this->assertStringContainsString('Test error', $output);
    }

    public function testErrorWithoutIcon(): void
    {
        ConsoleLogger::error('No icon', false);

        $output = $this->clean();

        $this->assertStringContainsString('No icon', $output);
    }

    public function testInfoOutputsMessage(): void
    {
        ConsoleLogger::info('Test info');

        $this->assertStringContainsString('Test info', $this->clean());
    }

    public function testSuccessOutputsMessage(): void
    {
        ConsoleLogger::success(Config::COLOR_SUCCESS);

        $this->assertStringContainsString(Config::COLOR_SUCCESS, $this->clean());
    }

    public function testWarningOutputsMessage(): void
    {
        ConsoleLogger::warning(Config::COLOR_WARNING);

        $this->assertStringContainsString(Config::COLOR_WARNING, $this->clean());
    }

    public function testHeaderOutputsText(): void
    {
        ConsoleLogger::header('My Header', Config::COLOR_INFO);

        $this->assertStringContainsString('My Header', $this->clean());
    }

    public function testBoxOutputsContent(): void
    {
        ConsoleLogger::box('Box content');

        $this->assertStringContainsString('Box content', $this->clean());
    }

    public function testPanelOutputsTitleAndContent(): void
    {
        ConsoleLogger::panel('Panel Title', 'Panel Content');

        $output = $this->clean();

        $this->assertStringContainsString('Panel Title', $output);
        $this->assertStringContainsString('Panel Content', $output);
    }

    public function testListItemOutputsMessage(): void
    {
        ConsoleLogger::listItem('Item');

        $this->assertStringContainsString('Item', $this->clean());
    }

    public function testAskOutputsQuestion(): void
    {
        ConsoleLogger::ask('Are you sure?');

        $this->assertStringContainsString('Are you sure?', $this->clean());
    }

    public function testStepOutputsCorrectFormat(): void
    {
        ConsoleLogger::step(2, 5, 'Processing');

        $output = $this->clean();

        $this->assertStringContainsString('[2/5]', $output);
        $this->assertStringContainsString('Processing', $output);
    }

    public function testSeparatorOutputsCorrectLength(): void
    {
        ConsoleLogger::separator(10);

        $this->assertStringContainsString(\str_repeat('-', 10), $this->clean());
    }

    public function testNewLineOutputsBreak(): void
    {
        ConsoleLogger::newLine();

        $this->assertNotEmpty($this->clean());
    }

    public function testNoColorDisablesAnsiSequences(): void
    {
        \putenv('NO_COLOR=1');

        ConsoleLogger::header('Header', Config::COLOR_INFO);

        $output = $this->clean();

        $this->assertStringContainsString('Header', $output);
        $this->assertStringNotContainsString("\033[", $output);
    }

    private function clean(): string
    {
        \rewind($this->output);

        return \preg_replace('/\e\[[\d;]*m/', '', \stream_get_contents($this->output)) ?? '';
    }
}
