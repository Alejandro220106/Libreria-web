<?php

namespace App\Infrastructure\Ai;

use App\Domain\Address\InvalidAddressResponse;
use Opis\JsonSchema\Validator;

/**
 * Checks a model reply against the published DireccionNormalizada schema (C7).
 */
class AddressSchemaValidator
{
    /**
     * @param  mixed  $data  Decoded reply, as an object tree (json_decode without assoc).
     *
     * @throws InvalidAddressResponse
     */
    public function assertValid(mixed $data): void
    {
        $schema = json_decode((string) file_get_contents((string) config('ai.schema_path')));
        $result = (new Validator)->validate($data, $schema);

        if (! $result->isValid()) {
            // Only the keyword that failed: never the model text, which may echo the address.
            $keyword = $result->error()?->keyword() ?? 'schema';

            throw new InvalidAddressResponse("The reply does not match the schema ({$keyword}).");
        }
    }
}
