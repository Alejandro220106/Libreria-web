<?php

namespace App\Application\Ports;

/**
 * Port to turn a free-text shipping address into structured fields.
 * Business code only knows this interface; the model adapter (hosted or
 * local Ollama) is chosen from .env and lives in Infrastructure.
 */
interface AddressNormalizer
{
    /**
     * Asks the model for structured output. The caller must still validate
     * the result against the published schema before saving it.
     *
     * @param  string  $address  Free-text shipping address, for example
     *                           "100 metros norte de la iglesia de Ciudad Quesada, casa verde".
     * @param  string|null  $correlationId  Order id, so the log entry can be traced.
     * @return array{provincia: string, canton: string, distrito: string, otras_senas: string, confianza: float}
     *
     * @throws \App\Domain\Address\InvalidAddressResponse When the reply does not match the schema.
     */
    public function normalize(string $address, ?string $correlationId = null): array;
}
