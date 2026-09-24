<?php

declare(strict_types=1);

namespace OverDark\Core\Session;

/**
 * Sessão nativa do PHP, iniciada sob demanda com cookie seguro.
 */
final class NativeSession implements Session
{
    private const FLASH = '_flash';

    public function __construct(
        private readonly string $name = 'overdark_session',
        private readonly bool $secure = false,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();

        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function invalidate(): void
    {
        $this->start();
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[self::FLASH][$key] = $value;
    }

    public function pullFlash(string $key, mixed $default = null): mixed
    {
        $this->start();
        $value = $_SESSION[self::FLASH][$key] ?? $default;
        unset($_SESSION[self::FLASH][$key]);

        return $value;
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name($this->name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
