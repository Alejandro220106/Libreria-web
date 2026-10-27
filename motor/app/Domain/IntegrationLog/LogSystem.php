<?php

namespace App\Domain\IntegrationLog;

/** The other side of the exchange. */
enum LogSystem: string
{
    case Store = 'store';
    case Connector = 'connector';
    case Ai = 'ai';
    case Legacy = 'legacy';

    /** Value used by the API contract (schema Sistema). */
    public function contract(): string
    {
        return match ($this) {
            self::Store => 'tienda',
            self::Connector => 'conector',
            self::Ai => 'ia',
            self::Legacy => 'heredado',
        };
    }

    public static function fromContract(string $value): self
    {
        return collect(self::cases())->firstOrFail(fn (self $case) => $case->contract() === $value);
    }
}
