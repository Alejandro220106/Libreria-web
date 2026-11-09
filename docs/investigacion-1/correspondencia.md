# Correspondencia con la interfaz común: Mailtrap

Proveedor elegido: **Mailtrap Email Sandbox** (ver `adr-001-proveedor.md`).
Acción de la categoría Mensajería: enviar un mensaje con el número del pedido, sus productos y el total.

Las peticiones de este documento están probadas en `postman/investigacion-1/mailtrap.postman_collection.json`.

## 1. Operaciones de la interfaz

Las dos operaciones usan autenticación Bearer: encabezado `Authorization: Bearer <token>`.

| Operación | Método | Ruta | Para qué |
|---|---|---|---|
| `probarConexion()` | `GET` | `https://mailtrap.io/api/accounts/{account_id}/inboxes/{inbox_id}` | Consulta de solo lectura. Un 200 confirma que el token es válido y tiene acceso a la bandeja |
| `ejecutarAccion(Pedido $pedido)` | `POST` | `https://sandbox.api.mailtrap.io/api/send/{inbox_id}` | Envía la confirmación del pedido. El correo queda en la bandeja de pruebas y no llega a una persona real |

`account_id` se obtiene una vez con `GET https://mailtrap.io/api/accounts` (carpeta `autenticacion`) y se guarda como variable de entorno.

## 2. Datos del pedido y campos de Mailtrap

Origen: la respuesta de `GET /wp-json/wc/v3/orders/{id}` (`pedido-prueba.json`).

| Dato del pedido (WooCommerce) | Campo de Mailtrap | Transformación |
|---|---|---|
| `number` | `subject` y `text` | Se inserta en el asunto `Pedido #<number> recibido` y en la línea `Pedido: #<number>` |
| `line_items[].name` y `line_items[].quantity` | `text` | Cada producto se escribe como `<quantity> x <name>` y se unen con `, ` en la línea `Productos:` |
| `total` | `text` | Se usa tal cual. WooCommerce lo envía como texto con dos decimales (`"25500.00"`) y no se convierte a la unidad mínima, porque es un mensaje para leer, no un cobro |
| `currency` | `text` | Se agrega después del total: `Total: 25500.00 CRC` |
| `billing.email` | `to[0].email` | Sin transformación. En el sandbox el correo nunca se entrega |
| Nombre de la tienda (configuración) | `from.name` | Viene de `CONECTOR_MAILTRAP_FROM_NAME`, no del pedido |
| Correo remitente (configuración) | `from.email` | Viene de `CONECTOR_MAILTRAP_FROM_EMAIL`, no del pedido |

Ejemplo del cuerpo enviado:

```json
{
  "from": { "email": "tienda@libreria.test", "name": "Libreria Web" },
  "to": [{ "email": "cliente@ejemplo.test" }],
  "subject": "Pedido #1001 recibido",
  "text": "Gracias por su compra.\nPedido: #1001\nProductos: 1 x Cien anos de soledad, 2 x El principito\nTotal: 25500.00 CRC"
}
```

## 3. Cómo se llena `ResultadoConector`

| Campo | Cuando hay éxito | Cuando falla |
|---|---|---|
| `exito` | `true` | `false` |
| `estado` | `"enviado"` (`ejecutarAccion`) o `"conectado"` (`probarConexion`) | `"fallido"` |
| `referenciaExterna` | `message_ids[0]` de la respuesta de Mailtrap. En `probarConexion` es el `id` de la bandeja | `null` |
| `codigoHttp` | `200` | El código que respondió Mailtrap, o `null` si no hubo respuesta (error de conexión o timeout) |
| `reintentable` | `false` | Según la tabla de errores de la sección 4 |
| `mensaje` | `"Correo enviado al sandbox"` | `errors[0]` de la respuesta de Mailtrap o, sin respuesta, `"Sin conexión con el proveedor"` |
| `datos` | `{ "message_ids": [...] }` | `{ "errors": [...] }`, o vacío si no hubo respuesta |

Respuesta de éxito observada:

```json
{ "success": true, "message_ids": ["..."] }
```

## 4. Errores del proveedor

Regla del enunciado: son reintentables los errores de conexión, las respuestas 5xx y la 429. Las demás respuestas 4xx no lo son.

| Código | Significado | Reintentable | Origen |
|---|---|---|---|
| 400 | Datos inválidos. Con `to` vacío respondió `{"success":false,"errors":["'to', 'cc' or 'bcc' address is required"]}` | No | Observado el 2026-11-09 (carpeta `errores`) |
| 401 | Token inválido o ausente. Respuesta con la propiedad `errors` | No | Observado el 2026-11-09 (carpeta `errores`) |
| 429 | Límite de tasa del plan gratuito: un segundo envío pocos segundos después de otro | Sí | Observado el 2026-11-08 y el 2026-11-09 |
| 5xx | Error interno del proveedor | Sí | Documentado: <https://api-docs.mailtrap.io> (consultado el AAAA-MM-DD) |
| Sin respuesta | Error de conexión o timeout | Sí | Regla del enunciado. Lo controla el cliente HTTP, no Mailtrap |

## 5. Variables de entorno del módulo

```
CONECTOR_PROVEEDOR=
CONECTOR_MAILTRAP_TOKEN=
CONECTOR_MAILTRAP_ACCOUNT_ID=
CONECTOR_MAILTRAP_INBOX_ID=
CONECTOR_MAILTRAP_FROM_EMAIL=
CONECTOR_MAILTRAP_FROM_NAME=
CONECTOR_MAILTRAP_TO_EMAIL=
CONECTOR_TIMEOUT=
CONECTOR_TRIES=
CONECTOR_BACKOFF_MS=
```

`CONECTOR_MAILTRAP_TO_EMAIL` solo se usa en las pruebas, cuando el pedido no trae `billing.email`.
