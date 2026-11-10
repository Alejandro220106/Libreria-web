# Base de datos del motor

El motor usa **PostgreSQL 18** en todos los ambientes: local, pruebas y demostración.

## Bases

| Base | Uso |
|---|---|
| `libreria_motor` | Datos del motor en desarrollo y en la demostración |
| `libreria_motor_testing` | Solo para `php artisan test`; las pruebas la vacían en cada corrida |

## Preparar una máquina nueva

1. Instalar PostgreSQL 18 y habilitar las extensiones `pdo_pgsql` y `pgsql` en el `php.ini`.
2. Crear el rol y las bases, desde la carpeta `motor/`, pasando la contraseña como variable (nunca se escribe en el repositorio):

   ```
   psql -U postgres -v owner_password='...' -f database/roles.sql
   ```

3. En `motor/.env`, poner esa contraseña en `DB_PASSWORD`.
4. Crear las tablas: `php artisan migrate`.
5. Comprobar: `php artisan test`.

## Convenciones del esquema

- Tablas en inglés, `snake_case` y en plural (`orders`, `integration_logs`).
- Llave primaria `id`; llaves foráneas `<tabla_en_singular>_id`.
- Las migraciones nuevas guardan las fechas en UTC con `timestampTz` y se muestran en la hora de Costa Rica.
- Índices y llaves foráneas con los nombres que genera Laravel (`<tabla>_<columna>_index`, `<tabla>_<columna>_foreign`).
- Todo cambio de esquema se hace con una migración nueva; nunca se edita una migración ya mergeada.
