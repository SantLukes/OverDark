<?php

declare(strict_types=1);

namespace OverDark\Core\Session;

/**
 * Sessão em memória — para testes e CLI.
 */
final class ArraySession implements Session
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @var array<string, mixed> */
    private array $flash = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function regenerate(): void
    {
    }

    public function invalidate(): void
    {
        $this->data = [];
        $this->flash = [];
    }

    public function flash(string $key, mixed $value): void
    {
        $this->flash[$key] = $value;
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $this->flash[$key] ?? $default;
        unset($this->flash[$key]);

        return $value;
    }
}
