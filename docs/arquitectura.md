# Arquitectura del motor

Estructura acordada para el motor de integración (`motor/`, Laravel 13 sobre PHP 8.3). Corresponde al issue #75 y a los apartados 6.2.1, 6.6.1 y 6.6.2 del enunciado. Se añade por escalabilidad: así se agregan conectores, temas de webhook y sistemas externos sin reescribir lo que ya funciona.

## Idea central: puertos y adaptadores

El motor habla con sistemas externos (la tienda, el conector, el modelo de IA, la bitácora). Si el código de negocio los llamara directamente, cambiar de proveedor o probarlo sin internet sería muy difícil.

Por eso cada sistema externo se usa a través de una **interfaz** (el *puerto*) y su implementación concreta (el *adaptador*) se elige en un service provider. Las reglas de negocio solo conocen la interfaz.

Ejemplo del proyecto: el caso de uso "ejecutar la acción del conector sobre un pedido" pide un `ConnectorInterface`. Qué proveedor responde (Mailtrap hoy, otro mañana) lo decide `CONECTOR_PROVEEDOR` en el `.env`. En las pruebas se sustituye por un doble, sin tocar el caso de uso.

## Capas

| Capa | Carpeta en `motor/app/` | Qué contiene | Qué NO hace |
|---|---|---|---|
| Dominio | `Domain/` | Reglas de negocio puras: validar la confianza de una dirección, decidir si un reintento aplica, estados de la bitácora. | No usa HTTP, base de datos ni `now()`. |
| Aplicación | `Application/` | Casos de uso que coordinan el dominio y los puertos: procesar un pedido, sincronizar productos, normalizar una dirección. Definen las interfaces (puertos). | No conoce Eloquent ni el cliente HTTP concretos. |
| Infraestructura | `Infrastructure/` | Adaptadores: cliente de WooCommerce, adaptador del modelo de IA, bitácora en base de datos. | No contiene reglas de negocio. |
| Conectores | `Conectores/` | Implementaciones de la interfaz común del docente (una por proveedor). Ruta fija por el enunciado. | No se nombra desde controladores ni jobs. |
| HTTP | `Http/` | Controladores delgados, API Resources, requests de validación. | No contiene reglas de negocio. |

Los modelos Eloquent y los providers que ya trae Laravel (`Models/`, `Providers/`) se mantienen. La carpeta `Http/` ya existe.

### Reglas de dependencia

El flujo va de fuera hacia dentro: `Http` y `Infrastructure` dependen de `Application`, y `Application` depende de `Domain`. Nunca al revés. Esto se puede comprobar con pruebas de arquitectura de Pest (issue #80).

## Rutas que no se mueven

Estas rutas las fija el enunciado y no cambian aunque reorganicemos lo demás:

- `motor/app/Conectores/`
- `motor/openapi.yaml`

## Puertos del motor

| Puerto (interfaz) | Para qué sirve | Adaptador inicial |
|---|---|---|
| Cliente de la tienda | Consumir la API REST v3 de WooCommerce con reintentos. | `Infrastructure/` con el cliente HTTP de Laravel. |
| Conector | Ejecutar la acción de la categoría sobre cada pedido nuevo. Es la interfaz común del docente. | `Conectores/` (según `CONECTOR_PROVEEDOR`). |
| Modelo de IA | Normalizar la dirección con salida estructurada. | `Infrastructure/` (proveedor declarado en el README). |
| Bitácora de integraciones | Registrar cada llamada saliente y cada webhook. | `Infrastructure/` en base de datos. |

El sistema heredado del Proyecto Integrador (SOAP) será un puerto más, con su propio adaptador.

Los puertos se enlazan en el service container, en un service provider. Es el mismo mecanismo que exige el enunciado para `CONECTOR_PROVEEDOR`.

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

1. Añadir un manejador por tema (una clase por tema) que reciba el payload ya verificado.
2. Registrar el tema en el controlador del webhook, que solo despacha al manejador.
3. Despachar el trabajo en un job que reciba ids y sea idempotente.
4. Registrar la entrega en la bitácora y probar firma válida, firma inválida y entrega duplicada.

## Cómo verificarlo

- Revisar que cada carpeta de la tabla de capas exista y que ninguna regla de negocio llame a Eloquent, HTTP ni `now()`.
- `php artisan test` debe pasar sin internet.
