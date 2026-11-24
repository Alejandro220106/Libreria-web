# Rendimiento del motor

Medición base de los tiempos de respuesta de la API del motor con muchos registros. Corresponde al issue #81 y a los apartados 6.3.5 y 6.7 del enunciado. Es la contraparte del motor de la medición de la tienda con Lighthouse (`docs/calidad-inicial.md`, #24); el Proyecto Integrador repite ambas y compara (#66).

## Meta del equipo

**Cada petición responde en menos de 500 ms**, medido en local. La colección de medición falla cualquier petición que pase de ese límite (variable `max_ms`).

## Cómo se midió

- **Fecha:** 1 de noviembre de 2026.
- **Datos:** el seed normal más `PerformanceSeeder`, en una base SQLite aparte. El seeder tardó unos 5 s.

  | Tabla | Registros |
  |---|---|
  | `products` | 20 014 |
  | `orders` | 10 003 |
  | `integration_logs` | 50 024 |

- **Servidor:** `php artisan serve` con `APP_DEBUG=false`.
- **Herramienta:** Newman con `postman/motor-rendimiento.postman_collection.json`, 20 iteraciones (180 peticiones).
- **Equipo:** AMD Ryzen 5 7535HS, PHP 8.5, Laravel 13.38, Linux.

## Resultados

Tiempos en milisegundos sobre las 20 iteraciones.

| Petición | Promedio | p95 | Máximo |
|---|---|---|---|
| `GET /productos` (primera página) | 10 | 11 | 30 |
| `GET /productos?por_pagina=100` | 12 | 13 | 19 |
| `GET /productos?pagina=1300` | 11 | 12 | 14 |
| `GET /productos?sku=PERF-015000` | 8 | 10 | 10 |
| `GET /pedidos/205000` | 7 | 8 | 11 |
| `GET /pedidos/999999` (404) | 7 | 9 | 11 |
| `GET /bitacora` (primera página) | 9 | 11 | 13 |
| `GET /bitacora?sistema=tienda&estado=fallo` | 15 | 16 | 16 |
| `GET /bitacora?pagina=3000` | 12 | 13 | 13 |

Ninguna petición pasa de la meta: el máximo fue 30 ms, en la primera petición de la corrida (el servidor todavía «en frío»).

## Revisión de consultas

Se revisó el plan de cada consulta con `EXPLAIN QUERY PLAN` de SQLite:

| Consulta | Plan |
|---|---|
| Producto por SKU | Usa el índice único `products_sku_unique`. |
| Pedido por id de la tienda | Usa el índice único `orders_woo_id_unique`. |
| Bitácora sin filtros | Recorre el índice `integration_logs_logged_at_index` ya en orden. |
| Bitácora por sistema y estado | Usa `integration_logs_status_index` y ordena el resultado en una tabla temporal. |

Las listas paginan en la base de datos y los endpoints medidos no cargan relaciones, así que no hay consultas N+1.

**A vigilar:** la bitácora filtrada es la consulta más lenta porque, después de filtrar por estado, ordena las filas por fecha. Hoy son unas 7 500 filas y tarda 15 ms. Ese costo crece con la tabla, aunque la limpieza diaria de la bitácora (#78) la mantiene acotada. Si en el Proyecto Integrador supera la meta, la mejora es un índice compuesto `(system, status, logged_at)`. No se agrega ahora porque no hace falta y cada índice hace más lentas las escrituras.

## Segunda medición: PostgreSQL y estrés (23 de noviembre de 2026)

La primera medición usó SQLite en Linux. Desde #136 el motor usa PostgreSQL 18, así que se repitió con la estructura `src/` (#167) y se agregó una prueba de concurrencia.

- **Datos:** los mismos volúmenes (20 014 productos, 10 003 pedidos, 50 024 entradas), cargados en `libreria_motor_testing`, la base desechable de Pest. El seeder tardó 16 s.
- **Servidor:** `php artisan serve --port=8001` con `APP_DEBUG=false`, sin cachear configuración ni rutas.
- **Equipo:** Windows 11, PostgreSQL 18.

### Secuencial (Newman, 20 iteraciones, 180 peticiones)

| Petición | Promedio | p95 | Máximo |
|---|---|---|---|
| `GET /productos` (primera página) | 145 | 200 | 225 |
| `GET /productos?por_pagina=100` | 163 | 225 | 232 |
| `GET /productos?pagina=1300` | 150 | 196 | 252 |
| `GET /productos?sku=PERF-015000` | 143 | 203 | 217 |
| `GET /pedidos/205000` | 137 | 176 | 191 |
| `GET /pedidos/999999` (404) | 137 | 187 | 229 |
| `GET /bitacora` (primera página) | 144 | 183 | 190 |
| `GET /bitacora?sistema=tienda&estado=fallo` | 145 | 184 | 196 |
| `GET /bitacora?pagina=3000` | 219 | 328 | 385 |

Todas bajo la meta de 500 ms, 0 fallos. Son unas 14 veces más lentas que con SQLite en Linux, pero casi todo es costo fijo y no de las consultas:

| Dónde se va el tiempo | Medido |
|---|---|
| `GET /up` (no toca la base) | ~60–120 ms: arranque de Laravel en el servidor de desarrollo de Windows |
| Abrir la conexión a PostgreSQL | ~120 ms por petición: PHP abre una conexión nueva en cada request y `sslmode=prefer` negocia SSL |
| `count(*)` de la bitácora (50 000 filas) | 6 ms |
| Página 3000 de la bitácora (`OFFSET 44 985`) | 45 ms |
| Bitácora filtrada por sistema y estado | 5 ms (conteo) + 1 ms (página) |

### Concurrencia (estrés)

200 peticiones repartidas entre `productos?por_pagina=100`, `bitacora?pagina=3000`, la bitácora filtrada y un pedido.

| Clientes a la vez | Errores | Peticiones/s | Promedio | p95 | Máximo |
|---|---|---|---|---|---|
| 1 | 0 | 5,4 | 174 ms | 257 ms | 615 ms |
| 5 | 0 | 5,2 | 940 ms | 1 388 ms | 1 579 ms |
| 10 | 0 | 3,9 | 2 426 ms | 3 015 ms | 3 188 ms |

El motor no pierde ninguna petición, pero las peticiones por segundo no suben con más clientes: `php artisan serve` atiende una a la vez y el resto espera en cola. Es un límite del servidor de desarrollo, no del código. Para el despliegue del Proyecto Integrador (#64): un servidor con varios procesos PHP (por ejemplo PHP-FPM detrás de Nginx o Apache), `php artisan config:cache` y `route:cache`, y `DB_SSLMODE=disable` cuando la base está en la misma máquina.

### A vigilar

- **Paginación profunda:** `OFFSET` recorre todas las filas anteriores; la página 3000 de la bitácora es la petición más lenta. La limpieza diaria (#78) la mantiene acotada.
- **Estadísticas:** después de una carga masiva conviene `VACUUM ANALYZE`, para que PostgreSQL elija bien los planes.

## Cómo repetirlo

Usa una base aparte para no perder los datos de la base local. `libreria_motor_testing` sirve porque Pest la limpia en cada corrida (el rol del motor no puede crear bases nuevas). En PowerShell, desde `motor/`:

```powershell
$env:DB_DATABASE = 'libreria_motor_testing'
php artisan migrate:fresh --seed
php artisan db:seed --class=PerformanceSeeder
$env:APP_DEBUG = 'false'; php artisan serve --port=8001
```

En otra terminal, desde la raíz del repositorio:

```powershell
newman run postman/motor-rendimiento.postman_collection.json -n 20 --env-var base_url=http://127.0.0.1:8001/api/v1
```

`PerformanceSeeder` no forma parte de `php artisan migrate --seed`, así que el seed normal y lo que espera la colección del docente no cambian. Al terminar, `php artisan test` deja `libreria_motor_testing` limpia otra vez.
