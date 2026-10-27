<?php

namespace App\Domain\IntegrationLog;

/** The other side of the exchange. */
enum LogSystem: string
{
    case Store = 'store';
    case Connector = 'connector';
    case Ai = 'ai';
    case Legacy = 'legacy';
}
