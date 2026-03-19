<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano;

use CiHispano\Support\CliIcons;

use function Termwind\render;

/**
 * ConsoleLogger.
 *
 * Provides styled console output using Termwind for CLI applications
 */
final class ConsoleLogger
{
    /**
     * Display an error message in the console.
     *
     * @param string $message     The error message to display
     * @param bool   $includeIcon Whether to include an error icon before the message
     */
    public static function error(
        string $message,
        bool $includeIcon = true,
    ): void {
        $message = self::escape($message);

        $icon = self::renderIcon(CliIcons::ERROR, 'red', $includeIcon);

        render(<<<HTML
            <div class="mx-1">
                {$icon}
                <span class="text-red font-bold mr-1">ERROR:</span>
                <span class="text-red">{$message}</span>
            </div>
        HTML);
    }

    /**
     * Display an informational message in the console.
     *
     * @param string $message     The info message to display
     * @param bool   $includeIcon Whether to include an info icon before the message
     */
    public static function info(
        string $message,
        bool $includeIcon = false,
    ): void {
        $message = self::escape($message);

        $icon = self::renderIcon(CliIcons::INFO, 'cyan', $includeIcon);

        render(<<<HTML
            <div class="mx-1">
                {$icon}
                <span class="text-cyan">{$message}</span>
            </div>
        HTML);
    }

    /**
     * Display a success message in the console.
     *
     * @param string $message     The success message to display
     * @param bool   $includeIcon Whether to include a success icon before the message
     */
    public static function success(
        string $message,
        bool $includeIcon = false,
    ): void {
        $message = self::escape($message);

        $icon = self::renderIcon(CliIcons::SUCCESS, 'green', $includeIcon);

        render(<<<HTML
            <div class="mx-1">
                {$icon}
                <span class="text-green">{$message}</span>
            </div>
        HTML);
    }

    /**
     * Display a warning message in the console.
     *
     * @param string $message     The warning message to display
     * @param bool   $includeIcon Whether to include a warning icon before the message
     */
    public static function warning(
        string $message,
        bool $includeIcon = false,
    ): void {
        $message = self::escape($message);

        $icon = self::renderIcon(CliIcons::WARNING, 'yellow', $includeIcon);

        render(<<<HTML
            <div class="mx-1">
                {$icon}
                <span class="text-yellow">{$message}</span>
            </div>
        HTML);
    }

    /**
     * Display a styled header with background color.
     *
     * @param string $header  The header text to display
     * @param string $bgColor The background color (cyan, red, green, yellow, blue, etc.)
     */
    public static function header(
        string $header,
        string $bgColor,
    ): void {
        $header = self::escape($header);

        render(<<<HTML
            <div class="mx-1 my-0 w-50 flex justify-center">
                <div class="my-0 px-2 bg-{$bgColor} text-black font-bold w-full text-center">
                    {$header}
                </div>
            </div>
        HTML);
    }

    /**
     * Display content inside a bordered box.
     *
     * @param string $content The content to display inside the box
     */
    public static function box(string $content): void
    {
        $content = self::escape($content);

        $line = \str_repeat('-', \strlen($content) + 4);

        render(<<<HTML
            <div class="mx-1 my-1 px-2 py-1">
                {$line}
                | {$content} |
                {$line}
            </div>
        HTML);
    }

    /**
     * Display a horizontal separator line.
     *
     * @param int    $length The length of the separator in characters (default: 50)
     * @param string $color  The color of the separator (default: cyan)
     */
    public static function separator(
        int $length = 50,
        string $color = 'cyan',
    ): void {
        $line = \str_repeat('-', $length);

        render(<<<HTML
            <div class="mx-1 my-0">
                <span class="text-{$color}">{$line}</span>
            </div>
        HTML);
    }

    /**
     * Display a blank line in the console.
     */
    public static function newLine(): void
    {
        render('<br />');
    }

    /**
     * Display a step indicator for multi-step processes.
     *
     * @param int    $step    The current step number
     * @param int    $total   The total number of steps
     * @param string $message The message describing the current step
     */
    public static function step(
        int $step,
        int $total,
        string $message,
    ): void {
        $message = self::escape($message);

        render(<<<HTML
            <div class="mx-1">
                <span class="text-blue font-bold">[{$step}/{$total}]</span>
                <span class="text-cyan pl-1">{$message}</span>
            </div>
        HTML);
    }

    /**
     * Display a list item with an icon.
     *
     * @param string $message The list item message
     * @param string $icon    The icon to display before the message (default: bullet point)
     * @param string $color   The text color (default: white)
     */
    public static function listItem(
        string $message,
        string $icon = CliIcons::BULLET,
        string $color = 'white',
    ): void {
        $message = self::escape($message);

        render(<<<HTML
            <div class="mx-2">
                <span class="text-{$color}">{$icon}</span>
                <span class="text-{$color}"> {$message}</span>
            </div>
        HTML);
    }

    /**
     * Display a question prompt.
     *
     * @param string $question The question to ask the user
     */
    public static function ask(string $question): void
    {
        $icon = self::renderIcon(CliIcons::ASK, 'yellow', true);

        $question = self::escape($question);

        render(<<<HTML
            <div class="mx-1 my-1">
                <span class="text-yellow font-bold">{$icon}</span>
                <span class="text-white"> {$question}</span>
            </div>
        HTML);
    }

    /**
     * Display a panel with a title and content.
     *
     * @param string $title   The panel title
     * @param string $content The panel content
     * @param string $color   The border and title color (default: cyan)
     */
    public static function panel(
        string $title,
        string $content,
        string $color = 'cyan',
    ): void {
        $title = self::escape($title);

        $content = self::escape($content);

        render(<<<HTML
            <div class="mx-1 my-1">
                <div class="text-{$color}">────────────────────────</div>
                <div class="font-bold text-{$color}">{$title}</div>
                <div class="mt-1 text-white">{$content}</div>
                <div class="text-{$color}">────────────────────────</div>
            </div>
        HTML);
    }

    /**
     * Escape HTML special characters to prevent formatting issues.
     *
     * @param string $text The text to escape
     *
     * @return string The escaped text safe for HTML output
     */
    private static function escape(string $text): string
    {
        return \htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Render an icon with the specified color.
     *
     * @param string $icon    The icon constant from CliIcons
     * @param string $color   The color class for the icon (red, cyan, green, yellow, etc.)
     * @param bool   $include Whether to include the icon
     *
     * @return string The rendered icon HTML or empty string
     */
    private static function renderIcon(
        string $icon,
        string $color,
        bool $include,
    ): string {
        return $include
            ? '<span class="text-' . $color . ' mr-2">' . $icon . '</span>'
            : '';
    }
}
