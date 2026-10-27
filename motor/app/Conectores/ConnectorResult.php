<?php

namespace App\Conectores;

/** Outcome of a connector action; it maps to the connector_* columns of the order. */
final readonly class ConnectorResult
{
    private function __construct(
        public bool $success,
        public ?string $reference,
        public ?string $detail,
    ) {}

    public static function success(?string $reference = null): self
    {
        return new self(true, $reference, null);
    }

    public static function failure(string $detail): self
    {
        return new self(false, null, $detail);
    }
}
