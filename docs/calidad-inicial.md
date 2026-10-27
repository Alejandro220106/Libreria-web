# Calidad inicial de la tienda

Medición base de la tienda con Lighthouse. Corresponde al issue #24 y al apartado 5.5.3 del enunciado. El Proyecto Integrador repite esta medición y compara los resultados (#66).

## Cómo se midió

- **Fecha:** 26 de octubre de 2026.
- **Herramienta:** Lighthouse, desde las herramientas de desarrollador del navegador (Brave, basado en Chromium).
- **Modo:** móvil (*Mobile*), navegación, en una ventana privada para evitar extensiones.
- **Entorno:** sitio local en Local (`https://librera-avance2.local`), con la tienda en modo «En vivo».

## Resultados

| Página | Dirección | Rendimiento | Accesibilidad | Buenas prácticas | SEO |
| --- | --- | --- | --- | --- | --- |
| Inicio | `/` | 98 | 96 | 100 | 92 |
| Producto | `/producto/breve-historia-del-tiempo/` | 97 | 97 | 100 | 92 |

Los puntajes de rendimiento son altos en parte porque el sitio corre en la misma computadora que el navegador, sin la latencia de una red real. En un hosting público pueden bajar.

## Tres mejoras identificadas

1. **Definir tiempos de caché para los archivos estáticos.** Lighthouse estima un ahorro de 285 KiB en el inicio y 92 KiB en el producto. El servidor no indica al navegador cuánto tiempo guardar imágenes, estilos y scripts, así que se descargan en cada visita. Se resuelve con cabeceras de caché en el servidor o con un plugin de caché.
2. **Reducir los recursos que bloquean el renderizado.** Ahorro estimado de 450 ms en ambas páginas. Hay hojas de estilo y scripts que el navegador debe descargar antes de mostrar la página. Se puede diferir lo que no es necesario para la primera vista.
3. **Optimizar las imágenes.** Ahorro estimado de 211 KiB en el inicio. Las imágenes del catálogo pesan más de lo necesario: conviene comprimirlas, servirlas en un formato más liviano como WebP y al tamaño en que se muestran.