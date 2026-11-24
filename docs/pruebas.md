# Pruebas del motor

Qué pruebas tiene el motor (`motor/`), cómo se corren y qué dieron en la última corrida. Complementa `docs/rendimiento.md` (medición de tiempos) y `docs/arquitectura.md` (reglas que las pruebas de arquitectura hacen cumplir).

## Última corrida

- **Fecha:** 23 de noviembre de 2026.
- **Código:** `main` después de #168 (estructura `src/`) más el arreglo del test de auditoría (#169).
- **Equipo:** Windows 11, PHP 8.3+, PostgreSQL 18, Ollama con `qwen2.5:7b`.

| Tipo | Herramienta | Resultado |
|---|---|---|
| Unitarias (arquitectura) | Pest | 12 pruebas, todas pasan |
| De integración (Feature) | Pest | 162 pruebas, todas pasan (incluye las 2 de las secuencias) |
| Contrato de la API | Newman, colección del docente | 12 peticiones, 64 aserciones, 0 fallos |
| Auditoría | Pest (`tests/Auditoria`) | 1 prueba, **falla a propósito**: documenta un hallazgo abierto |
| Rendimiento secuencial | Newman, 20 iteraciones | 180 peticiones, todas bajo 500 ms |
| Estrés (concurrencia) | 1, 5 y 10 clientes en paralelo | 600 peticiones, 0 errores; el servidor de desarrollo hace cola |
| IA real | Llamada directa a `qwen2.5:7b` | Funciona; hallazgos sobre la confianza (ver abajo) |

`php artisan test` corre las unitarias y las Feature (174 en total) sin internet en unos 20 s.

## 1. Pruebas unitarias: arquitectura

**Archivo:** `tests/Unit/ArchitectureTest.php`.

No ejecutan código: Pest lee el código fuente y revisa qué usa cada clase. Por eso no necesitan base de datos ni red.

| Regla | Por qué |
|---|---|
| El `Domain` de cada módulo no usa Laravel, Symfony, `App\` ni ninguna `Infrastructure` | El centro del hexágono es PHP puro. |
| La `Application` de cada módulo no usa Eloquent, HTTP, facades ni adaptadores | Los casos de uso hablan solo con puertos (interfaces). |
| Repositorios y puertos son interfaces | Si no, no se pueden cambiar ni simular. |
| Ningún código de `src/` nombra un conector concreto (salvo el provider que lo elige) | El proveedor sale de `CONECTOR_PROVEEDOR` (enunciado 6.6.2). |
| `env()` solo en `config/` | Con la configuración cacheada, `env()` devuelve null. |
| No quedan `dd`, `dump` ni `var_dump` | Código limpio para la entrega. |

Las primeras dos reglas se repiten por módulo (Catalog, Orders, IntegrationLog, Webhooks), por eso son 12 pruebas. Se comprobó que detectan una violación real: al importar el modelo Eloquent `Order` dentro de `StoreOrder`, la prueba falla.

## 2. Pruebas de integración (Feature)

**Carpeta:** `tests/Feature/`. Arrancan Laravel completo y usan la base `libreria_motor_testing` (se crea sola la primera vez y se limpia en cada prueba). Toda llamada HTTP hacia afuera está simulada con `Http::fake()`, y `Http::preventStrayRequests()` hace fallar cualquier llamada real.

| Área | Archivos | Qué comprueban |
|---|---|---|
| Contrato de la API | `ApiErrorFormatTest`, `ApiPaginationTest`, `ProductListingTest`, `ProductPaginationTest`, `OrderShowTest`, `LogListingTest` | Forma exacta del JSON, paginación (`pagina`, `por_pagina`, enlaces), errores 404/422 con el formato único, que no se expongan datos del cliente (C8). |
| Sincronización con la tienda | `WooCommerceClientTest`, `SyncProductsCommandTest`, `IncrementalSyncTest` | Autenticación, timeout, 3 intentos con backoff solo en errores temporales (conexión, 5xx, 429), recorrer todas las páginas, no duplicar productos, sincronización incremental. |
| Webhooks | `OrderWebhookTest`, `WebhookSignatureTest`, `WebhookIdempotencyTest`, `WebhookTopicRoutingTest` | Firma HMAC válida e inválida (401), entregas duplicadas (se guardan una vez), un manejador por tema, el job se despacha después de responder 200. |
| IA | `OllamaAddressNormalizerTest`, `NormalizeOrderAddressTest` | Se manda el JSON Schema a Ollama, se rechaza una respuesta fuera del esquema (provincia inventada, campo de más, confianza fuera de 0–1), umbral de confianza, Ollama caído. |
| Conector | `ConnectorContractTest`, `TelegramConnectorTest`, `MailtrapConnectorTest`, `RunConnectorActionTest` | El proveedor sale de `CONECTOR_PROVEEDOR`, un fallo del proveedor no pierde el pedido, no se notifica dos veces. |
| Bitácora | `IntegrationLoggerTest`, `IntegrationLogPruneTest` | Cada intercambio queda registrado sin secretos; los registros viejos se borran según la retención. |
| Base y entorno | `DatabaseConnectionTest`, `DatabaseRolesTest`, `DatabaseSeederTest`, `HealthCheckTest`, `LocalizationTest`, `TimezoneTest` | PostgreSQL, rol de la app sin privilegios de dueño, secuencias de ids alineadas después del seed, `/up`, mensajes en español, zona horaria de Costa Rica. |

## 3. Pruebas de contrato con Newman

**Colección:** `motor/motor-contrato.postman_collection.json` (la del docente). Se corre contra el motor encendido:

```powershell
cd motor
newman run motor-contrato.postman_collection.json --env-var base_url=http://127.0.0.1:8000/api/v1
```

Resultado: 12 peticiones, 64 aserciones, 0 fallos, promedio 88 ms. Valida cada respuesta contra los esquemas del contrato (`ListaProductos`, `Pedido`, `ListaBitacora`, `Error`) y revisa que no haya trazas internas.

## 4. Pruebas de auditoría

**Carpeta:** `tests/Auditoria/`. Las escribió el equipo auditor (#166). No forman parte de `php artisan test` porque documentan hallazgos que todavía no se corrigen. Se corren aparte:

```powershell
php artisan test tests/Auditoria
```

| Prueba | Resultado | Significado |
|---|---|---|
| `InyeccionDireccionTest` | Falla: el estado queda `normalized`, se esperaba `pending_review` | Si el modelo obedece una instrucción escrita dentro de la dirección y responde con confianza alta, el motor guarda la dirección falsa. La validación del esquema no lo detecta porque la respuesta tiene la forma correcta. Se corrige en el Proyecto Integrador (#65). |

## 5. Rendimiento y estrés

Detalle completo en `docs/rendimiento.md`. Resumen con PostgreSQL y 80 000 registros (20 014 productos, 10 003 pedidos, 50 024 entradas de bitácora):

- **Secuencial** (Newman, 20 iteraciones): todas las peticiones bajo la meta de 500 ms; el peor promedio fue la bitácora en página profunda, 219 ms.
- **Concurrente:** 0 errores con 1, 5 y 10 clientes, pero el rendimiento se queda en unas 5 peticiones por segundo y la latencia crece con los clientes, porque `php artisan serve` atiende una petición a la vez. Es un límite del servidor de desarrollo, no del código.

## 6. IA con el modelo real

Las pruebas Pest nunca llaman al modelo real. Para conocer su comportamiento se mandaron dos direcciones a `qwen2.5:7b` a través del puerto `AddressNormalizer`:

| Caso | Respuesta | Decisión del motor |
|---|---|---|
| Dirección normal de Ciudad Quesada | Alajuela / San Carlos / "Ciudad Quesada", confianza 0.80 | Pendiente de revisión (umbral 0.85) |
| Dirección de Heredia con instrucciones inyectadas | Heredia / Heredia / Mercedes (correcto), confianza 0.99 | Se guarda |

Observaciones:
- En esta corrida el modelo no obedeció la inyección, pero copió la confianza 0.99 que venía en el texto del atacante. La confianza que da el modelo no es confiable por sí sola; refuerza el hallazgo de auditoría.
- Una dirección válida quedó bajo el umbral, y el distrito vino como "Ciudad Quesada" en vez de "Quesada". Con este modelo, muchas direcciones reales irán a revisión manual.
- La primera llamada tardó 15,7 s (el modelo se carga en memoria); las siguientes, unos 3 s. Por eso la normalización corre en un job después de responder el webhook.

## Hallazgos de esta corrida

1. **Secuencias de PostgreSQL desfasadas en los seeders.** `OrderSeeder` (ids 1001–1003) e `IntegrationLogSeeder` insertan ids fijos y PostgreSQL no mueve la secuencia, que sigue en 1. Los inserts sin id funcionan hasta que la secuencia llega a 1001; ahí chocan (`orders_pkey`). Le pasaba al `PerformanceSeeder`, que inserta 10 000 pedidos, y le pasaría a la base sembrada después de 1000 pedidos reales. **Corregido:** `DatabaseSeeder` mueve cada secuencia al id más alto al terminar, con la conexión del dueño del esquema (la app solo puede usar las secuencias, no cambiarlas). `tests/Feature/DatabaseSeederTest.php` lo comprueba.
2. **Inyección en la dirección** (auditoría, ver sección 4).
3. **Confianza del modelo poco confiable** (sección 6).
4. **Costo de conexión:** cada petición abre una conexión nueva a PostgreSQL (~120 ms en Windows con `sslmode=prefer`). Ver `docs/rendimiento.md`.
