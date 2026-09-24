<?php

declare(strict_types=1);

namespace OverDark\Core\Logging\Handler;

use RuntimeException;

final class CurlSlackTransport implements SlackTransport
{
    public function __construct(private readonly int $timeoutSegundos = 3)
    {
    }

    public function enviar(string $webhookUrl, array $payload): void
    {
        $curl = curl_init($webhookUrl);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSegundos,
            CURLOPT_TIMEOUT => $this->timeoutSegundos,
        ]);

        $resposta = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $erro = curl_error($curl);
        curl_close($curl);

        if ($resposta === false || $status !== 200) {
            throw new RuntimeException(sprintf(
                'Slack respondeu %d: %s',
                $status,
                $erro !== '' ? $erro : mb_substr((string) $resposta, 0, 200),
            ));
        }
    }
}
