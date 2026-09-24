<?php

declare(strict_types=1);

namespace OverDark\Core\Session;

interface Session
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    /** Gera um novo ID de sessão mantendo os dados (evita session fixation no login). */
    public function regenerate(): void;

    /** Apaga todos os dados e gera um novo ID (logout). */
    public function invalidate(): void;

    /** Guarda um valor que sobrevive apenas até ser lido (padrão Post/Redirect/Get). */
    public function flash(string $key, mixed $value): void;

    /** Lê e remove um valor de flash. */
    public function pullFlash(string $key, mixed $default = null): mixed;
}
