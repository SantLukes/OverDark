<?php

declare(strict_types=1);

namespace OverDark\Core\Http;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $attributes dados anexados por middlewares (ex.: usuário autenticado)
     * @param array<string, string> $headers nomes em minúsculas (ex.: "x-correlation-id")
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $body = [],
        public readonly array $attributes = [],
        public readonly array $headers = [],
        public readonly string $ip = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Compatibilidade com os links antigos no formato index.php?url=/rota.
        if (isset($_GET['url']) && is_string($_GET['url'])) {
            $path = $_GET['url'];
        }

        $headers = [];
        foreach ($_SERVER as $chave => $valor) {
            if (is_string($chave) && str_starts_with($chave, 'HTTP_') && is_string($valor)) {
                $headers[strtolower(str_replace('_', '-', substr($chave, 5)))] = $valor;
            }
        }

        return new self($method, self::normalizePath($path), $_GET, $_POST, [], $headers, (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    public static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/index.php' ? '/' : $path;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /** Campo do formulário como string aparada ('' se ausente ou não-string). */
    public function string(string $key): string
    {
        $value = $this->body[$key] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function withAttribute(string $key, mixed $value): self
    {
        return new self($this->method, $this->path, $this->query, $this->body, [...$this->attributes, $key => $value], $this->headers, $this->ip);
    }
}
