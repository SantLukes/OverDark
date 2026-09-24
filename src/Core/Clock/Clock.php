<?php

declare(strict_types=1);

namespace OverDark\Core\Clock;

use DateTimeImmutable;

/**
 * Fonte de "agora". Injetada para que regras dependentes de data sejam testáveis.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
