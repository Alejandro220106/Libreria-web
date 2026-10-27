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
     * @return array{provincia: string, canton: string, distrito: string, otras_senas: string, confianza: float}
     */
    public function normalize(string $address): array;
}
