<?php

namespace App\Domain\IntegrationLog;

/** Who started the exchange: another system called us, or we called another system. */
enum LogDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';

    /** Value used by the API contract (schema EntradaBitacora). */
    public function contract(): string
    {
        return match ($this) {
            self::Incoming => 'entrante',
            self::Outgoing => 'saliente',
        };
    }

    public static function fromContract(string $value): self
    {
        return collect(self::cases())->firstOrFail(fn (self $case) => $case->contract() === $value);
    }
}
