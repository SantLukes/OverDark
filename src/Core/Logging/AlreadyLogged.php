<?php

declare(strict_types=1);

namespace OverDark\Core\Logging;

/**
 * Marca exceções que já foram registradas no log por quem tinha mais contexto
 * (normalmente o caso de uso). O kernel não as registra de novo, o que evita
 * alerta duplicado no Slack.
 *
 * Regra: logue uma vez, no ponto com mais contexto de negócio.
 */
interface AlreadyLogged extends \Throwable
{
}
