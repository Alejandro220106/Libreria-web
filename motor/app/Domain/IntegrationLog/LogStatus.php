<?php

namespace App\Domain\IntegrationLog;

/** Result of the exchange. */
enum LogStatus: string
{
    case Success = 'success';
    case Failure = 'failure';
    case Duplicate = 'duplicate';
    case InvalidSignature = 'invalid_signature';

    /** Value used by the API contract (schema EstadoBitacora). */
    public function contract(): string
    {
        return match ($this) {
            self::Success => 'exito',
            self::Failure => 'fallo',
            self::Duplicate => 'duplicado',
            self::InvalidSignature => 'firma_invalida',
        };
    }

    public static function fromContract(string $value): self
    {
        return collect(self::cases())->firstOrFail(fn (self $case) => $case->contract() === $value);
    }
}
