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
- Llave primaria `id` autoincremental (el `id()` de Laravel). El video de referencia del profe usa `uuid DEFAULT uuidv7()`, pero el equipo decidió no cambiar las tablas existentes a una semana del congelamiento del motor: el riesgo de romper relaciones y datos de prueba no se justifica, y el enunciado del curso no exige UUID como llave primaria. PostgreSQL 18 sí tiene `uuidv7()` disponible (lo confirma la prueba `generates version 7 uuids` en `DatabaseConnectionTest.php`), así que una tabla nueva puede usarlo si el equipo lo necesita.
- Llaves foráneas `<tabla_en_singular>_id`.
- **Zona horaria:** el motor trabaja en `America/Costa_Rica` (variable `APP_TIMEZONE`). La misma zona está en `config/app.php` y en las dos conexiones (`pgsql` y `pgsql_admin`), para que `now()` en PHP y en SQL den la misma hora. Lo confirma `tests/Feature/TimezoneTest.php`.
- **Fechas:** las columnas actuales son `timestamp` sin zona, así que guardan la hora de Costa Rica. Toda fecha que llega en UTC (los campos `*_gmt` de WooCommerce o un filtro `desde`/`hasta` con otro desfase) se convierte a la zona de la app **antes** de guardarla o compararla; si no, quedaría corrida 6 horas. La API responde con el desfase explícito (`2026-11-10T14:02:55-06:00`), así que el cliente siempre sabe qué instante es.
- Las tablas nuevas usan `timestampTz`, que guarda el instante en UTC y lo devuelve en la zona de la sesión.
- **Dinero:** columnas `decimal(12, 2)`, nunca `float` ni `integer`. Es la convención del propio motor (ver "Design principles" en `CLAUDE.md`: *decimal for money*), que se aplica incluso donde el ejemplo del profe usa colones enteros sin decimales. `decimal` evita el error de redondeo binario de `float` y, a diferencia de un entero, deja espacio para centavos si el negocio los necesita más adelante sin otra migración. `products.price` ya sigue esta regla.
- Índices y llaves foráneas con los nombres que genera Laravel (`<tabla>_<columna>_index`, `<tabla>_<columna>_foreign`).
- Todo cambio de esquema se hace con una migración nueva; nunca se edita una migración ya mergeada.

## Funciones, procedimientos, triggers, políticas y vistas

Cuando una tabla necesite un objeto de este tipo, se crea en una migración, nunca a mano en la base:

- Siempre con `DB::statement()` o `DB::unprepared()` dentro de `up()`, y con el `down()` que lo elimina (`DROP FUNCTION`, `DROP TRIGGER`, etc.). Sin `down()` no se puede revertir la migración.
- Prefijo según el tipo de objeto: `fn_` funciones, `sp_` procedimientos, `trg_` triggers, `v_` vistas, `mv_` vistas materializadas. Por ejemplo, un trigger que actualice `updated_at` se llamaría `trg_products_touch_updated_at`.
- Las reglas de negocio (cálculos, validaciones del dominio) se escriben en PHP, en la capa de dominio del motor — no se duplican en PL/pgSQL. Un objeto de base de datos solo cubre lo que la base necesita resolver por sí misma (por ejemplo, una restricción que el ORM no puede expresar).
- El rol que crea estos objetos es `libreria_owner` (la misma conexión `pgsql_admin` de las migraciones). `libreria_app` los puede usar (ejecutar una función, ser afectado por un trigger) pero no crearlos ni modificarlos, por el mismo principio de mínimo privilegio que ya rige las tablas.
