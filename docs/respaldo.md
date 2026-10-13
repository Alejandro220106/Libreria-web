# Respaldo de Libreria-web

Procedimiento de respaldo y restauración de la tienda (WordPress + WooCommerce en Local, localhost). Corresponde al issue #22.

## Herramienta

Plugin **UpdraftPlus** (versión gratuita).

## Qué se respalda

- Archivos de `wp-content`: plugins, temas y uploads.
- La base de datos.

## Frecuencia

- Respaldo manual **antes de cada cambio grande**.
- Un respaldo **semanal**.

## Dónde se guardan

- Fuera del repositorio.
- Fuera del equipo donde corre la tienda.
- En una carpeta compartida de **Google Drive** del equipo.

## Regla

**Nunca subir respaldos (`.zip`, `.sql`, `.gz`) al repositorio.**

## Cómo respaldar

1. En el administrador de WordPress, ir a **Ajustes → Copias de UpdraftPlus**.
2. Pulsar **Copia de seguridad ahora**.
3. Marcar la base de datos y los archivos (plugins, temas, uploads).
4. Pulsar **Copia de seguridad ahora** para confirmar y esperar a que termine.
5. Descargar los archivos generados y subirlos a la carpeta compartida de Google Drive del equipo.
6. Borrar las copias descargadas que queden dentro del repositorio, si las hubiera.

## Cómo restaurar

1. Ir a **Ajustes → Copias de UpdraftPlus → pestaña Copias existentes**.
2. Si la copia no aparece, pulsar **Subir archivos de copia** y cargar los archivos descargados desde Google Drive.
3. Pulsar **Restaurar** en la copia deseada.
4. Elegir los componentes a restaurar (base de datos y archivos).
5. Confirmar y esperar a que termine el proceso.
6. Revisar que la tienda funcione (productos, carrito, inicio de sesión).

## Video de restauración

Pendiente
