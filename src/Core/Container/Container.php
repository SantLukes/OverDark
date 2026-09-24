<?php

declare(strict_types=1);

namespace OverDark\Core\Container;

use Closure;
use RuntimeException;

/**
 * Container de injeção de dependências mínimo.
 *
 * Cada serviço é registrado com uma factory e instanciado uma única vez
 * (singleton por requisição). As ligações ficam em config/services.php.
 */
final class Container
{
    /** @var array<string, Closure(self): object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * @param Closure(self): object $factory
     */
    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->instances[$id]);
    }

    /**
     * @template T of object
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : object)
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new RuntimeException(sprintf('Serviço "%s" não registrado no container.', $id));
        }

        return $this->instances[$id] = ($this->factories[$id])($this);
    }
}
