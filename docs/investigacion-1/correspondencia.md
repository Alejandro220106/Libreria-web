# Correspondencia con la interfaz común: Telegram

Proveedor elegido: **Telegram Bot API** (ver `adr-001-proveedor.md`).
Acción de la categoría Mensajería: enviar a una cuenta de prueba del equipo un mensaje con el número del pedido, sus productos y el total.

Las peticiones de este documento están probadas en `postman/investigacion-1/telegram.postman_collection.json`.

## 1. Operaciones de la interfaz

Telegram no usa un encabezado de autenticación: el token del bot va dentro de la ruta (`/bot<token>/`). Por eso no hay una petición de autenticación aparte.

| Operación | Método | Ruta | Para qué |
|---|---|---|---|
| `probarConexion()` | `GET` | `https://api.telegram.org/bot{token}/getMe` | Consulta de solo lectura. Un 200 con `result.is_bot = true` confirma que el token es válido |
| `ejecutarAccion(Pedido $pedido)` | `POST` | `https://api.telegram.org/bot{token}/sendMessage` | Envía el mensaje del pedido al chat de prueba del equipo |

## 2. Datos del pedido y campos de Telegram

Origen: la respuesta de `GET /wp-json/wc/v3/orders/{id}` (`pedido-prueba.json`, pedido 136).

| Dato del pedido (WooCommerce) | Campo de Telegram | Transformación |
|---|---|---|
| `number` | `text` | Se escribe en la línea `Pedido: #<number>` |
| `line_items[].name` y `line_items[].quantity` | `text` | Cada producto se escribe como `<quantity> x <name>` y se unen con `, ` en la línea `Productos:` |
| `total` | `text` | WooCommerce lo envía como texto (`"10800"`). Se formatea con dos decimales (`10800.00`). No se convierte a la unidad mínima, porque es un mensaje para leer, no un cobro |
| `currency` | `text` | Se agrega después del total: `Total: 10800.00 CRC` |
| (no existe en el pedido) | `chat_id` | WooCommerce no guarda el chat de Telegram del cliente. Se usa el chat de prueba del equipo, configurado en `CONECTOR_TELEGRAM_CHAT_ID` |

`text` admite como máximo 4096 caracteres. Un pedido con muchos productos debe recortarse antes de enviarse.

Ejemplo del cuerpo enviado:

```json
{
  "chat_id": "<chat de prueba>",
  "text": "Gracias por su compra.\nPedido: #136\nProductos: 1 x Cocorí, 1 x El principito - Tapa blanda\nTotal: 10800.00 CRC"
}
```

## 3. Cómo se llena `ResultadoConector`

| Campo | Cuando hay éxito | Cuando falla |
|---|---|---|
| `exito` | `true` (Telegram responde `ok: true`) | `false` (`ok: false` o sin respuesta) |
| `estado` | `"enviado"` (`ejecutarAccion`) o `"conectado"` (`probarConexion`) | `"fallido"` |
| `referenciaExterna` | `result.message_id` (`ejecutarAccion`) o `result.username` del bot (`probarConexion`) | `null` |
| `codigoHttp` | `200` | El código HTTP de la respuesta (igual a `error_code`), o `null` si no hubo respuesta |
| `reintentable` | `false` | Según la tabla de errores de la sección 4 |
| `mensaje` | `"Mensaje enviado"` | `description` de la respuesta de Telegram o, sin respuesta, `"Sin conexión con el proveedor"` |
| `datos` | `{ "message_id": ..., "chat_id": ..., "date": ... }` tomados de `result` | `{ "error_code": ..., "description": ..., "retry_after": ... }`. `retry_after` se copia de `parameters.retry_after` y solo existe en la 429 |

Respuesta de éxito (formato observado):

```json
{ "ok": true, "result": { "message_id": 5, "chat": { "id": 0 }, "date": 0, "text": "..." } }
```

Respuesta de error (formato observado):

```json
{ "ok": false, "error_code": 401, "description": "Unauthorized" }
```

## 4. Errores del proveedor

Regla del enunciado: son reintentables los errores de conexión, las respuestas 5xx y la 429. Las demás respuestas 4xx no lo son.

| Código | Significado | Reintentable | Origen |
|---|---|---|---|
| 400 | Datos inválidos, por ejemplo `chat_id` vacío | No | Observado el 2026-11-09 (carpeta `errores`) |
| 401 | Token del bot inválido | No | Observado el 2026-11-09 (carpeta `errores`) |
| 403 | El usuario bloqueó al bot | No | No observado. La Bot API no documenta este código: solo dice que el bot puede ser bloqueado por el usuario (<https://core.telegram.org/bots/api#update>, consultado el 2026-11-09). Se aplica la regla del enunciado para los 4xx |
| 429 | Límite de tasa, por ejemplo más de un mensaje por segundo al mismo chat. La respuesta trae `parameters.retry_after` con los segundos que hay que esperar | Sí, después de `retry_after` | Documentado: el 429 en <https://core.telegram.org/bots/faq#my-bot-is-hitting-limits-how-do-i-avoid-this> y `retry_after` en <https://core.telegram.org/bots/api#responseparameters> (consultados el 2026-11-09) |
| 5xx | Error interno de Telegram | Sí | Regla del enunciado. La Bot API no documenta códigos 5xx (<https://core.telegram.org/bots/api#making-requests>, consultado el 2026-11-09) |
| Sin respuesta | Error de conexión o timeout | Sí | Regla del enunciado. Lo controla el cliente HTTP, no Telegram |

## 5. Variables de entorno del módulo

```
CONECTOR_PROVEEDOR=
CONECTOR_TELEGRAM_TOKEN=
CONECTOR_TELEGRAM_CHAT_ID=
CONECTOR_TELEGRAM_URL=
CONECTOR_TIMEOUT=
CONECTOR_TRIES=
CONECTOR_BACKOFF_MS=
```

`CONECTOR_TELEGRAM_URL` permite cambiar la URL base (`https://api.telegram.org`) en las pruebas.
