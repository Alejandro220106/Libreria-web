# Título

ADR-001: Proveedor del conector de mensajería

## Fecha

2026-11-09

## Estado

Aceptado

## Contexto

El motor debe ejecutar una acción sobre cada pedido nuevo. El equipo eligió la categoría Mensajería: enviar a una cuenta de prueba del equipo un mensaje con el número del pedido, sus productos y el total. El conector implementa la interfaz común (`probarConexion()` y `ejecutarAccion()`), y el proveedor se elige con `CONECTOR_PROVEEDOR`. Ambos se probaron en Postman, en sus ambientes de prueba, con el pedido de prueba 136 (`pedido-prueba.json`).

## Opciones consideradas

1. **Telegram Bot API**: un bot con un token fijo; el mensaje se envía con `POST /bot{token}/sendMessage`.
2. **WhatsApp Business Cloud API**: un número de prueba de Meta con un token de acceso; el mensaje se envía con `POST /{phone-number-id}/messages`.

## Decisión

Se elige **Telegram Bot API**, según los resultados de `comparativa.md`:

- **Tiempo de respuesta de la acción**: Telegram promedió 382,6 ms (mín. 298, máx. 975) y WhatsApp 672,9 ms (mín. 503, máx. 1215), en diez ejecuciones con Newman cada uno (`tiempos.md`).
- **Tipo de autenticación**: el token del bot de Telegram no vence. El token de prueba de WhatsApp vence a las pocas horas y hay que generarlo de nuevo: dos mediciones devolvieron 401 en las diez peticiones (código 190).
- **Costo en producción por unidad**: en pruebas ambos son gratuitos, pero en producción Telegram sigue sin costo y WhatsApp cobra por mensaje según la tarifa de Meta.
- **Límites del ambiente de pruebas**: WhatsApp solo permite enviar a números de destino verificados en el número de prueba; Telegram no exige ese registro previo.

## Consecuencias

**Positivas**

- Menos configuración: un solo token y un `chat_id`.
- Las peticiones probadas en Postman pasan directo al motor (`correspondencia.md`).

**Negativas**

- El mensaje llega a un chat del equipo y no al cliente, porque WooCommerce no guarda el chat de Telegram del cliente. Para avisar al cliente haría falta pedirle ese dato en la tienda.
- El motor del Avance 2 tiene hoy un conector de Mailtrap; hay que programar el conector de Telegram y seleccionarlo con `CONECTOR_PROVEEDOR=telegram` antes del 17 de noviembre.

**Riesgo y mitigación**

- **Riesgo**: Telegram limita la tasa de envío y responde 429 si se supera.
- **Mitigación**: el 429 y los errores 5xx se tratan como reintentables, con los reintentos y la espera configurados en `CONECTOR_TRIES` y `CONECTOR_BACKOFF_MS`; cada intento queda en la bitácora de integraciones.
