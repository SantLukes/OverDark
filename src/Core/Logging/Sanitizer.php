<?php

declare(strict_types=1);

namespace OverDark\Core\Logging;

use DateTimeInterface;
use JsonSerializable;
use Stringable;
use Throwable;

/**
 * Garante que nenhum segredo chegue ao log e que todo valor seja serializável.
 */
final class Sanitizer
{
    private const OCULTO = '[REDACTED]';

    /** Chaves cujo valor nunca é registrado. */
    private const CHAVES_SENSIVEIS = '/senha|password|passwd|secret|token|webhook|authorization|cookie|session/i';

    /** Quantos frames da stack trace entram no log (filtrar o irrelevante). */
    private const FRAMES = 5;

    /**
     * @param array<array-key, mixed> $dados
     * @return array<array-key, mixed>
     */
    public static function limpar(array $dados): array
    {
        $limpo = [];

        foreach ($dados as $chave => $valor) {
            if (is_string($chave) && preg_match(self::CHAVES_SENSIVEIS, $chave)) {
                $limpo[$chave] = self::OCULTO;
                continue;
            }

            $limpo[$chave] = self::valor($valor);
        }

        return $limpo;
    }

    /**
     * "lucas.santana@gmail.com" → "l***@gmail.com": identifica sem expor.
     */
    public static function mascararEmail(string $email): string
    {
        $email = trim($email);
        $arroba = strrpos($email, '@');

        if ($arroba === false || $arroba === 0) {
            return $email === '' ? '' : mb_substr($email, 0, 1) . '***';
        }

        return mb_substr($email, 0, 1) . '***' . substr($email, $arroba);
    }

    /**
     * @return array{classe: string, mensagem: string, arquivo: string, trace: list<string>}
     */
    public static function excecao(Throwable $e): array
    {
        $raiz = dirname(__DIR__, 3) . '/';
        $frames = [];

        foreach (array_slice($e->getTrace(), 0, self::FRAMES) as $frame) {
            $local = isset($frame['file']) ? str_replace($raiz, '', $frame['file']) . ':' . ($frame['line'] ?? '?') : '[interno]';
            $frames[] = $local . ' ' . ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'] . '()';
        }

        return [
            'classe' => $e::class,
            'mensagem' => $e->getMessage(),
            'arquivo' => str_replace($raiz, '', $e->getFile()) . ':' . $e->getLine(),
            'trace' => $frames,
        ];
    }

    private static function valor(mixed $valor): mixed
    {
        return match (true) {
            $valor === null, is_scalar($valor) => $valor,
            is_array($valor) => self::limpar($valor),
            $valor instanceof Throwable => self::excecao($valor),
            $valor instanceof DateTimeInterface => $valor->format(DateTimeInterface::ATOM),
            $valor instanceof JsonSerializable => $valor->jsonSerialize(),
            $valor instanceof Stringable => (string) $valor,
            $valor instanceof \BackedEnum => $valor->value,
            is_object($valor) => '[' . $valor::class . ']',
            default => '[' . get_debug_type($valor) . ']',
        };
    }
}
