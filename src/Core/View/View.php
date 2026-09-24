<?php

declare(strict_types=1);

namespace OverDark\Core\View;

use RuntimeException;

/**
 * Motor de templates em PHP puro.
 *
 * Convenção de nomes:
 *  - "Modulo::arquivo"  -> src/Modules/Modulo/Views/arquivo.php
 *  - "pasta/arquivo"    -> resources/views/pasta/arquivo.php (layouts, partials, erros)
 */
final class View
{
    /** @var array<string, mixed> dados disponíveis em todas as views (ex.: usuário logado, token CSRF) */
    private array $shared = [];

    public function __construct(
        private readonly string $modulesPath,
        private readonly string $sharedViewsPath,
    ) {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $name, array $data = []): string
    {
        return (new Template($this, $this->resolve($name), [...$this->shared, ...$data]))->render();
    }

    private function resolve(string $name): string
    {
        if (str_contains($name, '::')) {
            [$module, $template] = explode('::', $name, 2);
            $file = sprintf('%s/%s/Views/%s.php', $this->modulesPath, $module, $template);
        } else {
            $file = sprintf('%s/%s.php', $this->sharedViewsPath, $name);
        }

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('View "%s" não encontrada em %s.', $name, $file));
        }

        return $file;
    }
}
