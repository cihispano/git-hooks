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

/**
 * ConsoleLogger.
 *
 * Provides styled console output using native ANSI escape sequences.
 * Colors are automatically disabled when the output is not a TTY or when
 * the NO_COLOR environment variable is set.
 */
final class ConsoleLogger
{
    private const RESET = "\033[0m";
    private const BOLD  = '1';

    /**
     * ANSI foreground color codes.
     *
     * @var array<string, string>
     */
    private const FOREGROUND = [
        'black'   => '30',
        'red'     => '31',
        'green'   => '32',
        'yellow'  => '33',
        'blue'    => '34',
        'magenta' => '35',
        'cyan'    => '36',
        'white'   => '37',
    ];

    /**
     * ANSI background color codes.
     *
     * @var array<string, string>
     */
    private const BACKGROUND = [
        'black'   => '40',
        'red'     => '41',
        'green'   => '42',
        'yellow'  => '43',
        'blue'    => '44',
        'magenta' => '45',
        'cyan'    => '46',
        'white'   => '47',
    ];

    /**
     * Output stream override, mainly for tests.
     *
     * @var resource|null
     */
    private static mixed $stream = null;

    /**
     * Redirect console output to the given stream.
     *
     * Useful in tests: pass a stream resource (e.g. "php://memory") to capture output.
     * Pass null to restore standard output.
     *
     * @param resource|null $stream
     */
    public static function setOutputStream(mixed $stream): void
    {
        self::$stream = $stream;
    }

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
        $icon = self::renderIcon(CliIcons::ERROR, 'red', $includeIcon);

        self::write(
            ' ' . $icon . self::color('ERROR:', 'red', true) . ' ' . self::color($message, 'red'),
        );
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
        $icon = self::renderIcon(CliIcons::INFO, 'cyan', $includeIcon);

        self::write(' ' . $icon . self::color($message, 'cyan'));
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
        $icon = self::renderIcon(CliIcons::SUCCESS, 'green', $includeIcon);

        self::write(' ' . $icon . self::color($message, 'green'));
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
        $icon = self::renderIcon(CliIcons::WARNING, 'yellow', $includeIcon);

        self::write(' ' . $icon . self::color($message, 'yellow'));
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
        $code = self::BACKGROUND[$bgColor] ?? self::BACKGROUND['cyan'];

        self::write(' ' . self::paint(" {$header} ", $code, true) . ' ');
    }

    /**
     * Display content inside a bordered box.
     *
     * @param string $content The content to display inside the box
     */
    public static function box(string $content): void
    {
        $line = \str_repeat('-', \strlen($content) + 4);

        self::write(' ' . $line);
        self::write(' | ' . $content . ' |');
        self::write(' ' . $line);
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

        self::write(' ' . self::color($line, $color));
    }

    /**
     * Display a blank line in the console.
     */
    public static function newLine(): void
    {
        self::write('');
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
        self::write(
            ' ' . self::color("[{$step}/{$total}]", 'blue', true)
            . ' ' . self::color($message, 'cyan'),
        );
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
        self::write('  ' . self::color($icon . ' ' . $message, $color));
    }

    /**
     * Display a question prompt.
     *
     * @param string $question The question to ask the user
     */
    public static function ask(string $question): void
    {
        $icon = self::renderIcon(CliIcons::ASK, 'yellow', true);

        self::write(' ' . $icon . self::color(' ' . $question, 'white'));
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
        $border = \str_repeat('─', 24);

        self::write(' ' . self::color($border, $color));
        self::write(' ' . self::color($title, $color, true));
        self::write(' ' . $content);
        self::write(' ' . self::color($border, $color));
    }

    /**
     * Render an icon with the specified color.
     *
     * @param string $icon    The icon constant from CliIcons
     * @param string $color   The color name (red, cyan, green, yellow, etc.)
     * @param bool   $include Whether to include the icon
     *
     * @return string The rendered icon or empty string
     */
    private static function renderIcon(
        string $icon,
        string $color,
        bool $include,
    ): string {
        if (! $include) {
            return '';
        }

        return self::color($icon . ' ', $color);
    }

    /**
     * Apply a background color (and optional bold) to a text segment.
     *
     * @param string $text The text to paint
     * @param string $code The ANSI background color code
     * @param bool   $bold Whether to apply bold
     *
     * @return string The painted text (plain when colors are disabled)
     */
    private static function paint(
        string $text,
        string $code,
        bool $bold = false,
    ): string {
        if (! self::supportsColor()) {
            return $text;
        }

        $modifiers = [$code];

        if ($bold) {
            $modifiers[] = self::BOLD;
        }

        return "\033[" . \implode(';', $modifiers) . "m{$text}" . self::RESET;
    }

    /**
     * Apply ANSI color to a text segment.
     *
     * @param string $text The text to colorize
     * @param string $name The color name (see FOREGROUND map)
     * @param bool   $bold Whether to apply bold
     *
     * @return string The colorized text (plain when colors are disabled)
     */
    private static function color(
        string $text,
        string $name,
        bool $bold = false,
    ): string {
        if (! self::supportsColor()) {
            return $text;
        }

        $code      = self::FOREGROUND[$name] ?? self::FOREGROUND['white'];
        $modifiers = [$code];

        if ($bold) {
            $modifiers[] = self::BOLD;
        }

        return "\033[" . \implode(';', $modifiers) . "m{$text}" . self::RESET;
    }

    /**
     * Write a line to standard output.
     */
    private static function write(string $line): void
    {
        if (null !== self::$stream) {
            \fwrite(self::$stream, $line . PHP_EOL);

            return;
        }

        echo $line . PHP_EOL;
    }

    /**
     * Whether the current stream supports ANSI colors.
     *
     * Colors are disabled when NO_COLOR is set (regardless of value),
     * when the TERM is "dumb", or when STDOUT is not a TTY.
     */
    private static function supportsColor(): bool
    {
        if (false !== \getenv('NO_COLOR')) {
            return false;
        }

        if ('dumb' === \getenv('TERM')) {
            return false;
        }

        return ! (! \defined('STDOUT') || ! \stream_isatty(STDOUT));
    }
}
