# Webhooks de la tienda y túnel local

Corresponde al issue #44 (apartados 6.5.1 y 6.2.5 del enunciado). El motor no se despliega en el Avance 2, así que la tienda desplegada le envía los webhooks a la máquina de quien lo está probando mediante un túnel.

## Herramienta elegida: Cloudflare Tunnel (`cloudflared`)

- Gratis y sin cuenta con el "túnel rápido".
- Entrega una URL HTTPS pública que reenvía al puerto local del motor.
- La URL **cambia cada vez** que se abre el túnel. Hay que actualizar la URL de entrega de los dos webhooks antes de cada prueba o demo.

Instalación en Windows:

```bash
winget install --id Cloudflare.cloudflared -e
```

## Paso a paso

1. En `motor/.env`, definir el secreto (el mismo que se pondrá en la tienda). Nunca va en el repositorio:

   ```
   WOOCOMMERCE_WEBHOOK_SECRET=<un valor largo y aleatorio>
   ```

2. Levantar el motor (el puerto 8000 es el de `php artisan serve`):

   ```bash
   cd motor
   php artisan serve --port=8000
   ```

3. En otra terminal, abrir el túnel y copiar la URL `https://<nombre>.trycloudflare.com` que aparece:

   ```bash
   cloudflared tunnel --url http://127.0.0.1:8000
   ```

4. Comprobar el túnel antes de tocar la tienda: `https://<nombre>.trycloudflare.com/up` debe responder 200.

5. En la tienda, ir a **WooCommerce > Ajustes > Avanzado > Webhooks** y crear dos webhooks con estos datos:

   | Nombre | Tema | URL de entrega | Secreto | Estado |
   |---|---|---|---|---|
   | Pedido creado | Order created | `https://<nombre>.trycloudflare.com/api/v1/webhooks/woocommerce` | el de `.env` | Activo |
   | Pedido actualizado | Order updated | la misma URL | el de `.env` | Activo |

6. Probar: WooCommerce envía un *ping* al guardar el webhook. El motor lo responde con 200 (issue #42) y queda en la bitácora.

## Antes de cada demo

- Abrir el túnel de nuevo y copiar la URL nueva.
- Editar los dos webhooks y cambiar solo la URL de entrega.
- Confirmar que el estado de ambos sea **Activo**. WooCommerce desactiva un webhook que falla muchas veces seguidas.

## Qué no hacer

- No subir `.env` ni pegar el secreto en issues, capturas o commits.
- No dejar el túnel abierto cuando no se está probando: cualquiera con la URL llega al endpoint (la firma HMAC rechaza lo que no venga de la tienda, pero conviene cerrarlo igual).
