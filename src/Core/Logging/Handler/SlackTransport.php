<?php

declare(strict_types=1);

namespace OverDark\Core\Logging\Handler;

interface SlackTransport
{
    /**
     * @param array<string, mixed> $payload corpo JSON do Incoming Webhook
     */
    public function enviar(string $webhookUrl, array $payload): void;
}
