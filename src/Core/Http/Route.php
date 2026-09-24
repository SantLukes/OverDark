<?php

declare(strict_types=1);

namespace OverDark\Core\Http;

final class Route
{
    /**
     * @param array{class-string, string} $handler
     * @param list<class-string<Middleware>> $middleware
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $handler,
        public readonly array $middleware = [],
    ) {
    }
}
