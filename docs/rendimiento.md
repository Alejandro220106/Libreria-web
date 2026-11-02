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

## Cómo repetirlo

Usa una base aparte para no perder los datos de la base local:

```bash
cd motor
touch database/rendimiento.sqlite
DB_DATABASE=$PWD/database/rendimiento.sqlite php artisan migrate:fresh --seed
DB_DATABASE=$PWD/database/rendimiento.sqlite php artisan db:seed --class=PerformanceSeeder
DB_DATABASE=$PWD/database/rendimiento.sqlite APP_DEBUG=false php artisan serve
```

En otra terminal, desde la raíz del repositorio:

```bash
newman run postman/motor-rendimiento.postman_collection.json -n 20
```

`PerformanceSeeder` no forma parte de `php artisan migrate --seed`, así que el seed normal y lo que espera la colección del docente no cambian.
