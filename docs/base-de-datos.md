# Base de datos del motor

El motor usa **PostgreSQL 18** en todos los ambientes: local, pruebas y demostración.

## Bases

| Base | Uso |
|---|---|
| `libreria_motor` | Datos del motor en desarrollo y en la demostración |
| `libreria_motor_testing` | Solo para `php artisan test`; las pruebas la vacían en cada corrida |

## Roles

| Rol | Uso | Permisos |
|---|---|---|
| `libreria_owner` | Dueño del esquema; solo corre las migraciones (conexión `pgsql_admin`) | Crea, altera y borra tablas |
| `libreria_app` | Atiende las peticiones del motor (conexión `pgsql`) | Solo `SELECT`, `INSERT`, `UPDATE` y `DELETE`; no es dueño de ninguna tabla |

Si un error o una inyección llega a la base por la API, el rol de la aplicación no puede crear, alterar ni borrar tablas (mínimo privilegio).

La primera migración (`0000_00_00_000000_grant_privileges_to_the_application_role`) le da a `libreria_app` permisos sobre las tablas existentes y, con `ALTER DEFAULT PRIVILEGES`, sobre todas las que el dueño cree después, sin pasos manuales.

## Preparar una máquina nueva

1. Instalar PostgreSQL 18 y habilitar las extensiones `pdo_pgsql` y `pgsql` en el `php.ini`.
2. Crear los roles y las bases, desde la carpeta `motor/`, pasando las contraseñas como variables (nunca se escriben en el repositorio):

   ```
   psql -U postgres -v owner_password='...' -v app_password='...' -f database/roles.sql
   ```

3. En `motor/.env`, poner la contraseña de `libreria_app` en `DB_PASSWORD` y la de `libreria_owner` en `DB_ADMIN_PASSWORD`.
4. Crear las tablas como dueño: `php artisan migrate --database=pgsql_admin`.
5. Comprobar: `php artisan test` (las pruebas migran como dueño y consultan como aplicación).

Las migraciones **siempre** se corren con `--database=pgsql_admin`; sin esa opción fallan, porque `libreria_app` no puede crear tablas.

## Convenciones del esquema

- Tablas en inglés, `snake_case` y en plural (`orders`, `integration_logs`).
- Llave primaria `id`; llaves foráneas `<tabla_en_singular>_id`.
- Las migraciones nuevas guardan las fechas en UTC con `timestampTz` y se muestran en la hora de Costa Rica.
- Índices y llaves foráneas con los nombres que genera Laravel (`<tabla>_<columna>_index`, `<tabla>_<columna>_foreign`).
- Todo cambio de esquema se hace con una migración nueva; nunca se edita una migración ya mergeada.
