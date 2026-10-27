<?php

namespace App\Domain\IntegrationLog;

/** Who started the exchange: another system called us, or we called another system. */
enum LogDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}
