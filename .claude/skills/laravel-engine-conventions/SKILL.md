---
name: laravel-engine-conventions
description: Rules for the Laravel 13 integration engine in motor/ (API contract, WooCommerce client, webhooks, connector, integration log, AI address case, Pest tests). Use when writing or reviewing anything under motor/.
---

# Laravel engine conventions

The numbered conditions are in `contexto.md` section 6 and `Proyecto 1.pdf` section 6. The instructor's files (connector interface, OpenAPI contract, address JSON schema, Newman collection) are never modified.

## Project
- Laravel 13 on PHP 8.3 in `motor/`. It must start with only: `composer install`, copy `.env.example` to `.env`, `php artisan key:generate`, `php artisan migrate --seed`, `php artisan serve`.
- `.env.example` lists every variable, with empty secrets. `php artisan test` passes offline with no real keys. The engine is not deployed in Avance 2; webhooks arrive through a tunnel.

## Structure
- Ports and adapters: domain rules receive the current time and their data as parameters; the store, the connector, the AI model and the integration log sit behind interfaces bound in the service container.
- Fixed paths: connectors in `motor/app/Conectores/`, the API description in `motor/openapi.yaml`.
- `env()` only inside `config/`. Anything that may change (timeouts, retries, page size, confidence threshold, log retention) is configuration.
- Routes are grouped under `/api/v1` so authentication and rate limits can be added later.

## API contract
- Endpoints: `GET /api/v1/productos`, `GET /api/v1/pedidos/{id}`, `GET /api/v1/bitacora`, in the exact shape, pagination and filters of the contract. One error format for 404, 422 and 500. No stack traces with `APP_DEBUG=false`.
- `GET /api/v1/productos` answers from the engine's database, never from the store.
- Use API Resources and eager loading; index the columns used to filter and sort.

## WooCommerce client
- Laravel HTTP client, keys from `.env`. 10 s timeout, up to 3 attempts with increasing backoff. Retry on connection errors, 5xx and 429; do not retry other 4xx.
- `php artisan tienda:sincronizar-productos` walks every page using `X-WP-TotalPages` and creates or updates the local copy, including the custom field. Every call is logged.

## Webhooks (`POST /api/v1/webhooks/woocommerce`)
- Signature: Base64 of HMAC-SHA256 over the raw body with the webhook secret, compared to `X-WC-Webhook-Signature` with `hash_equals`. Mismatch → 401, nothing processed, logged as «firma inválida».
- Idempotency: store `X-WC-Webhook-Delivery-ID`; a repeated delivery → 200, not processed, logged as «duplicado».
- The test ping (body with only `webhook_id`) → 200 and no order.
- Valid delivery: save or update the order and answer 200 at once; the connector and the address normalization run in a job dispatched after the response. A job failure never changes the response. Jobs receive ids and can be repeated safely.

## Connector
- Implements the instructor's interface without changing its signature. The provider comes from `CONECTOR_PROVEEDOR` and is resolved in the container; no controller or job names it. Sandbox only. A provider failure leaves the order saved and the failure in the log.

## Integration log
- One record per outgoing call (store, connector, AI model) and per webhook: direction, system, operation, status (success, failure, duplicate, invalid signature), HTTP code, attempts, duration in ms, correlation id, error message, date. Never keys, secrets, signatures or full bodies.

## AI address case
- Ask the model for structured output with `provincia`, `canton`, `distrito`, `otras_senas` and `confianza` (0 to 1). Validate against the published schema; only a valid answer is saved. Invalid answer, `confianza` below 0.85 or a provider failure → «pendiente de revisión», logged, the engine keeps going.

## Tests (Pest)
- Every test uses `Http::fake()` and `Http::preventStrayRequests()`. The real model is never called.
- The seven required tests: valid signature saves the order · invalid signature gives 401 and saves nothing · duplicate delivery is not processed twice · store returns 500 → three attempts and the failure is logged · connector failure keeps the order and logs the failure · an answer outside the schema leaves the address pending review · `GET /api/v1/productos` returns the contract pagination.
