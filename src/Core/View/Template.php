<?php

declare(strict_types=1);

namespace OverDark\Core\View;

use DateTimeInterface;
use OverDark\Core\Security\Csrf;
use OverDark\Shared\Domain\Money;
use OverDark\Shared\Formatting\Formatter;
use Throwable;

/**
 * Contexto de um arquivo de template: dentro da view, `$this` é esta classe.
 *
 * Helpers disponíveis nas views: layout(), insert(), e(), asset(), json(),
 * csrf(), money(), signedMoney(), percent(), date().
 */
final class Template
{
    private ?string $layout = null;

    /** @var array<string, mixed> */
    private array $layoutData = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly View $view,
        private readonly string $file,
        private readonly array $data,
    ) {
    }

    public function render(): string
    {
        $content = $this->capture();

        if ($this->layout === null) {
            return $content;
        }

        return $this->view->render($this->layout, [...$this->layoutData, 'content' => $content]);
    }

    /**
     * Declara o layout que envolve esta view. Chamado no topo do template.
     *
     * @param array<string, mixed> $data
     */
    public function layout(string $name, array $data = []): void
    {
        $this->layout = $name;
        $this->layoutData = $data;
    }

    /**
     * Renderiza um partial no ponto atual do template.
     *
     * @param array<string, mixed> $data
     */
    public function insert(string $name, array $data = []): void
    {
        echo $this->view->render($name, $data);
    }

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }

    /**
     * JSON seguro para uso dentro de atributos HTML (ex.: data-chart).
     */
    public function json(mixed $value): string
    {
        return $this->e(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Campo oculto com o token anti-CSRF. Obrigatório em todo <form method="post">.
     */
    public function csrf(): string
    {
        $token = $this->data['csrfToken'] ?? '';

        return sprintf('<input type="hidden" name="%s" value="%s">', Csrf::FIELD, $this->e($token));
    }

    public function money(Money $money): string
    {
        return $this->e(Formatter::money($money));
    }

    public function signedMoney(Money $money): string
    {
        return $this->e(Formatter::signedMoney($money));
    }

    public function percent(float $value, bool $signed = false, int $decimals = 2): string
    {
        return $this->e(Formatter::percent($value, $signed, $decimals));
    }

    public function date(DateTimeInterface $date): string
    {
        return $this->e(Formatter::date($date));
    }

    private function capture(): string
    {
        // Cópia: extract() recebe o array por referência e $data é readonly.
        extract([...$this->data], EXTR_SKIP);

        $level = ob_get_level();
        ob_start();

        try {
            require $this->file;
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return (string) ob_get_clean();
    }
}
