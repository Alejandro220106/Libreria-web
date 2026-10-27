# Archivos base del docente

Archivos que publicó el docente para todos los equipos. Se copian **sin cambios**: si alguno tiene un error, se avisa al docente y la corrección se publica para todos a la vez (enunciado, apartado 6.1). Corresponde al issue #32.

| Archivo | Ubicación en el repositorio | Publicado | Para qué sirve |
| --- | --- | --- | --- |
| Contrato OpenAPI obligatorio | `motor/openapi.yaml` | 20 de octubre de 2026 | Define las rutas, la paginación y el formato de errores del motor. Los endpoints adicionales del equipo se documentan en este mismo archivo |
| Esquema de la dirección normalizada | `motor/direccion-normalizada.schema.json` | 20 de octubre de 2026 | Valida la respuesta del modelo de IA antes de guardar la dirección de un pedido |
| Colección de Newman | `motor/motor-contrato.postman_collection.json` | 20 de octubre de 2026 | Comprueba que el motor cumple el contrato, con los datos que carga `php artisan migrate --seed` |
| Interfaz común del conector | `motor/app/Conectores/` | 6 de octubre de 2026 | Interfaz PHP que implementa el conector. **Pendiente de agregar** |

Los tres primeros vienen del archivo `practica-postman.zip` del 20 de octubre y se comprobó que son idénticos al original.