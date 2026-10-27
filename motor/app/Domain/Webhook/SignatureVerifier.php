<?php

namespace App\Domain\Webhook;

/**
 * Checks the HMAC-SHA256 signature WooCommerce puts in X-WC-Webhook-Signature.
 * It receives everything as parameters: it reads no config and no request.
 */
final class SignatureVerifier
{
    public function isValid(string $rawBody, ?string $signature, ?string $secret): bool
    {
        // Without a secret or a signature nothing can be trusted. An empty secret
        // would still produce a "valid" HMAC that anyone could compute.
        if ($signature === null || $signature === '' || $secret === null || $secret === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        // hash_equals compares in constant time, so response time leaks nothing.
        return hash_equals($expected, $signature);
    }
}
