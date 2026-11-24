# Arquitectura del motor

Estructura acordada para el motor de integración (`motor/`, Laravel 13 sobre PHP 8.3). Corresponde a los issues #75 y #167 y a los apartados 6.2.1, 6.6.1 y 6.6.2 del enunciado. Sigue el ejemplo hexagonal del docente (SodaYa-Hexagonal): el código de negocio vive en `motor/src/`, organizado por módulo y por capa. Se añade por escalabilidad: así se agregan conectores, temas de webhook y sistemas externos sin reescribir lo que ya funciona.

## Idea central: puertos y adaptadores

El motor habla con sistemas externos (la tienda, el conector, el modelo de IA, la bitácora). Si el código de negocio los llamara directamente, cambiar de proveedor o probarlo sin internet sería muy difícil.

Por eso cada sistema externo se usa a través de una **interfaz** (el *puerto*) y su implementación concreta (el *adaptador*) se elige en un service provider. Las reglas de negocio solo conocen la interfaz.

Ejemplo del proyecto: el caso de uso "ejecutar la acción del conector sobre un pedido" pide un `ConnectorInterface`. Qué proveedor responde (Mailtrap hoy, otro mañana) lo decide `CONECTOR_PROVEEDOR` en el `.env`. En las pruebas se sustituye por un doble, sin tocar el caso de uso.

## Módulos

`composer.json` registra el namespace `Src\` en la carpeta `src/`. Cada módulo es una parte del negocio:

| Módulo | Qué resuelve | Endpoint |
|---|---|---|
| `src/Catalog/` | Copiar los productos de la tienda y listarlos. | `GET /api/v1/productos` |
| `src/Orders/` | Guardar pedidos, normalizar la dirección con IA y ejecutar el conector. | `GET /api/v1/pedidos/{id}` |
| `src/Webhooks/` | Recibir los webhooks de WooCommerce, verificar la firma y evitar duplicados. | `POST /api/v1/webhooks/woocommerce` |
| `src/IntegrationLog/` | La bitácora de integraciones y su consulta. | `GET /api/v1/bitacora` |
| `src/Shared/` | Lo común de la API: formato de error, paginación y request base de listados. | — |

En `motor/app/` queda solo lo propio de Laravel: `Providers/AppServiceProvider.php` (configuración general), `Console/Commands/` (el comando de sincronización) y `Models/User.php`. También `Conectores/`, que no se mueve porque el enunciado fija esa ruta.

## Capas dentro de cada módulo

```
src/Orders/
├── Domain/            Entities (Order), Contracts (OrderRepository), Exceptions
├── Application/       UseCases (StoreOrder, NormalizeOrderAddress, RunConnectorAction), Contracts (AddressNormalizer)
└── Infrastructure/    Http (controlador, resource, routes.php), Persistence (modelo Eloquent y repositorio),
                       Ai (adaptador de Ollama), Jobs, Webhooks (manejadores por tema), OrdersServiceProvider
```

| Capa | Qué contiene | Qué NO hace |
|---|---|---|
| `Domain/` | Entidades, enums y servicios puros (por ejemplo, `SignatureVerifier`), y las interfaces de los repositorios. | No usa Laravel, HTTP, base de datos ni nada de `App\`. |
| `Application/` | Casos de uso que coordinan el dominio y los puertos. Define los puertos hacia sistemas externos (`StoreClient`, `AddressNormalizer`, `IntegrationLogger`). | No conoce Eloquent, el cliente HTTP ni las facades de Laravel. |
| `Infrastructure/` | Adaptadores de entrada (controladores, comandos, jobs, manejadores de webhook) y de salida (Eloquent, WooCommerce, Ollama), más el service provider del módulo. | No contiene reglas de negocio. |

### Reglas de dependencia

El flujo va de fuera hacia dentro: `Infrastructure` depende de `Application` y `Application` depende de `Domain`. Nunca al revés. `tests/Unit/ArchitectureTest.php` lo comprueba con Pest en cada `php artisan test`: si un caso de uso importa un modelo Eloquent, la prueba falla.

### Repositorios

Los casos de uso no llaman a Eloquent: piden un repositorio (`ProductRepository`, `SyncStateRepository`, `OrderRepository`) definido en `Domain/Contracts` e implementado en `Infrastructure/Persistence/Repositories`. Por ejemplo, `NormalizeOrderAddress` dice `markAddressPendingReview($id)` en vez de escribir columnas. El repositorio devuelve la entidad `Order` del dominio, no el modelo Eloquent.

Los controladores sí consultan Eloquent directamente para los listados: son adaptadores de entrada y su consulta (filtros, paginación) es de infraestructura.

## Rutas que no se mueven

Estas rutas las fija el enunciado y no cambian aunque reorganicemos lo demás:

- `motor/app/Conectores/`
- `motor/openapi.yaml`

## Puertos del motor

| Puerto (interfaz) | Para qué sirve | Adaptador | Se enlaza en |
|---|---|---|---|
| `Catalog\Application\Contracts\StoreClient` | Consumir la API REST v3 de WooCommerce con reintentos. | `Catalog/Infrastructure/WooCommerce/WooCommerceClient` | `CatalogServiceProvider` |
| `Catalog\Domain\Contracts\ProductRepository` y `SyncStateRepository` | Guardar el catálogo y la fecha de la última sincronización. | `Catalog/Infrastructure/Persistence/Repositories/` | `CatalogServiceProvider` |
| `Orders\Domain\Contracts\OrderRepository` | Leer y guardar los pedidos. | `Orders/Infrastructure/Persistence/Repositories/` | `OrdersServiceProvider` |
| `Orders\Application\Contracts\AddressNormalizer` | Normalizar la dirección con salida estructurada. | `Orders/Infrastructure/Ai/OllamaAddressNormalizer` (según `AI_PROVIDER`) | `OrdersServiceProvider` |
| `App\Conectores\ConnectorInterface` | Ejecutar la acción de la categoría sobre cada pedido nuevo. Es la interfaz común del docente. | `app/Conectores/` (según `CONECTOR_PROVEEDOR`) | `OrdersServiceProvider` |
| `IntegrationLog\Application\Contracts\IntegrationLogger` | Registrar cada llamada saliente y cada webhook. | `IntegrationLog/Infrastructure/Persistence/DatabaseIntegrationLogger` | `IntegrationLogServiceProvider` |

El sistema heredado del Proyecto Integrador (SOAP) será un puerto más, con su propio adaptador.

Cada módulo enlaza sus puertos en su propio service provider, registrado en `bootstrap/providers.php`. Es el mismo mecanismo que exige el enunciado para `CONECTOR_PROVEEDOR`.

## Reglas de código

- **Controladores delgados.** Validan, llaman a un caso de uso y devuelven un Resource. Los webhooks responden 200 primero y el trabajo pesado va a un job después de la respuesta.
- **El tiempo y los datos entran por parámetro.** Una regla que necesita la hora recibe la hora; no llama a `now()`. Así la prueba controla el reloj.
- **Los jobs reciben ids**, no objetos completos, y son idempotentes: repetirlos no duplica efectos.
- **`env()` solo dentro de `config/`.** Tiempos de espera, reintentos, tamaño de página, umbral de confianza (0,85) y retención de la bitácora son configuración.
- **Rutas agrupadas en `/api/v1`**, para añadir autenticación y límites de peticiones en el Proyecto Integrador sin cambiar endpoints.

## Cómo agregar un conector nuevo

1. Crear la clase en `motor/app/Conectores/` que implemente la interfaz común del docente, sin cambiar su firma.
2. Registrar el valor del proveedor en `config/` y enlazarlo en el service provider según `CONECTOR_PROVEEDOR`.
3. Añadir sus variables a `.env.example` con los valores vacíos.
4. Escribir pruebas con `Http::fake()` y `Http::preventStrayRequests()`.
5. Verificar que ningún controlador ni job menciona al proveedor por su nombre.

## Cómo agregar un tema de webhook nuevo

1. Añadir un manejador por tema que implemente `Webhooks\Application\Contracts\WebhookHandler` y reciba el payload ya verificado. Va en el módulo dueño del tema (por ejemplo, `Orders/Infrastructure/Webhooks/`).
2. Registrar el tema en `config/webhooks.php`; el controlador solo despacha al manejador.
3. Despachar el trabajo en un job que reciba ids y sea idempotente.
4. Registrar la entrega en la bitácora y probar firma válida, firma inválida y entrega duplicada.

## Cómo agregar un módulo

1. Crear `src/<Modulo>/Domain`, `Application` e `Infrastructure`.
2. Poner las interfaces de repositorio en `Domain/Contracts` y sus implementaciones Eloquent en `Infrastructure/Persistence`.
3. Crear `Infrastructure/<Modulo>ServiceProvider.php` con los bindings y registrarlo en `bootstrap/providers.php`.
4. Si expone endpoints, crear `Infrastructure/Http/routes.php` y cargarlo desde `routes/api.php` dentro del grupo `v1`.
5. Agregar el módulo a la lista de `tests/Unit/ArchitectureTest.php`.
6. Un modelo Eloquent fuera de `App\Models` necesita `#[UseFactory(...)]` para encontrar su factory.

## Cómo verificarlo

- `php artisan test` debe pasar sin internet. Incluye las pruebas de arquitectura, que fallan si una capa interna depende de una externa.

## Retención de la bitácora

Los registros de `integration_logs` se borran solos cuando superan `INTEGRATION_LOG_RETENTION_DAYS` días (por defecto **30**, definido en `config/integration_log.php`). El Scheduler de Laravel ejecuta `model:prune` una vez al día. `GET /api/v1/bitacora` y el panel del Proyecto Integrador solo ven lo que queda dentro de ese periodo.
