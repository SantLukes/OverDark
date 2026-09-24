<?php

declare(strict_types=1);

namespace OverDark\Core\Logging;

use Psr\Log\LogLevel;

/**
 * Ordem de severidade dos níveis PSR-3 (quanto maior, mais grave).
 */
final class Level
{
    private const ORDEM = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    public static function severidade(string $level): int
    {
        return self::ORDEM[strtolower($level)] ?? self::ORDEM[LogLevel::INFO];
    }

    public static function atinge(string $level, string $minimo): bool
    {
        return self::severidade($level) >= self::severidade($minimo);
    }

    public static function valido(string $level): bool
    {
        return isset(self::ORDEM[strtolower($level)]);
    }
}
