<?php

declare(strict_types=1);

/*
 * Copyright (c) 2026.
 * This file is part of CiHispano Git Hooks library.
 *
 * @copyright CiHispano <administracion@cihispano.org>
 * @license For the full copyright and license information, see the LICENSE file distributed with this source code.
 */

namespace CiHispano\Tests\Exceptions;

use PHPUnit\Framework\Assert;
use RuntimeException;

trait AssertionsException
{
    protected function assertWrappedException(
        RuntimeException $exception,
        string $rootMessage,
        string $previousContains,
    ): void {
        Assert::assertSame($rootMessage, $exception->getMessage());
        Assert::assertNotNull($exception->getPrevious());
        Assert::assertStringContainsString(
            $previousContains,
            $exception->getPrevious()->getMessage(),
        );
    }
}
