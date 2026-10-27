<?php

namespace App\Domain\IntegrationLog;

/** Result of the exchange. */
enum LogStatus: string
{
    case Success = 'success';
    case Failure = 'failure';
    case Duplicate = 'duplicate';
    case InvalidSignature = 'invalid_signature';
}
