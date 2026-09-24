<?php

declare(strict_types=1);

namespace OverDark\Core\Clock;

use DateTimeImmutable;

/**
 * Relógio parado — para testes.
 */
final class FixedClock implements Clock
{
    public function __construct(private readonly DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
