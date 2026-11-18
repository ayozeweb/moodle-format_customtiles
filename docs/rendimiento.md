# Rendimiento, calidad y compatibilidad (fase 5)

Mediciones y comprobaciones de la versión 0.7.0. Se repiten con las herramientas descritas al final.

## Presupuesto de rendimiento

Objetivo de CLAUDE.md: LCP < 2,5 s y CLS ≈ 0 en un móvil de gama media.

### Curso de prueba

`cli/generate_test_course.php` (herramienta de desarrollo, fuera del zip publicado) crea un curso grande:

```bash
php public/course/format/customtiles/cli/generate_test_course.php --sections=60 --activities=10 --covers=20 --units=2 --student=alumno
```

60 secciones, 600 actividades con finalización y categoría, 2 unidades y 20 portadas JPEG de 1–2 MB (ruido, para que
no se compriman como un degradado). Solo funciona con la depuración de desarrollador activada.

### Laboratorio

Playwright + Chrome DevTools Protocol: 360×780, `deviceScaleFactor` 2, CPU ralentizada ×4, red «4G lenta»
(150 ms de latencia, 1,6 Mbps de bajada, 750 kbps de subida). Moodle local con PHP integrado (sin OPcache afinado,
sin HTTP/2): el TTFB es peor que en un servidor real.

| Página | LCP | Elemento LCP | CLS | TBT | HTML (comprimido) | Portadas descargadas |
|---|---|---|---|---|---|---|
| Portada del curso, sin caché | 0,59 s | título del curso | 0 | 420 ms | 235 KB (≈ 20 KB) | 5 (158 KB, WebP) |
| Portada del curso, segunda visita | 0,44 s | título del curso | 0 | 305 ms | 235 KB | 0 (caché) |
| Página de sección | 0,66 s | portada de la sección (WebP) | 0 | 357 ms | 246 KB | 1 |

El tiempo de bloqueo (TBT) es casi todo del JavaScript del core (cajón del índice, edición reactiva, YUI); el del
formato son cinco módulos pequeños que se cargan después de pintar.

### Qué se hizo

- **Portadas**: se recodifican al subirlas (máximo 1920 px) con variantes de 480 y 960 px, `srcset`, tamaños fijos
  (`width`/`height` + `aspect-ratio`, sin saltos de maquetación) y, si GD sabe escribir WebP, una copia WebP de cada
  variante (calidad 75) servida con `<picture>` y el JPEG/PNG como respaldo. Ahorro medido: 27 % a 480 px y 19 % a
  960 px. AVIF no está disponible en el GD del entorno de prueba. Las portadas anteriores a 0.7.0 se convierten con
  una tarea ad hoc que se encola al actualizar.
- **Prioridad**: la primera fila de la rejilla (4 elementos) se carga sin `loading="lazy"`; la portada de la cabecera
  de sección, que es el LCP de esa página, lleva además `fetchpriority="high"`. El resto es perezoso.
- **Consultas**: `tests/performance_test.php` cuenta las lecturas de base de datos al pintar la portada y una sección
  con 5 y con 40 secciones y falla si crecen. Hoy: 4 lecturas la portada y 3 la sección, sea cual sea el tamaño del
  curso (progreso en caché MUC, opciones de sección en la caché de `modinfo`, categorías en caché de petición).
- **CSS**: 1.860 líneas sin comprimir, unos 7,5 KB comprimido y en la caché del tema. Separar los estilos por layout
  ahorraría 2–3 KB por página a cambio de otra petición: no compensa.

### Decisiones

- **Sin Lighthouse en la CI.** Necesita un Moodle con datos, un servidor web y un navegador en la misma ejecución, y
  sus cifras en un runner compartido varían más que los cambios que queremos detectar. Lo que sí es estable va en la
  CI (el número de consultas); las cifras de laboratorio se repiten a mano antes de cada versión con
  `perf.js` (abajo).

## Calidad

- **CI** (GitHub Actions + moodle-plugin-ci): PHP 8.3 con PostgreSQL 17 ejecuta todas las comprobaciones (lint,
  validación, Mustache, phpcs, grunt, CSS compilado, tests de JavaScript, phpdoc, PHPUnit y Behat). PHP 8.4 con
  MariaDB 11.4 y con MySQL 8.4 ejecutan PHPUnit (instalación, actualización, consultas y copias de seguridad son lo
  que depende de la versión de PHP y de la base de datos).
- **Sin trabajo contra la rama de desarrollo de Moodle**: `version.php` declara `supported = [503, 503]`, así que Moodle
  rechaza instalarlo en otra versión y ese trabajo fallaría siempre. Se añadirá cuando exista la rama de 5.4 y se
  amplíe el rango (fase 7).
- **Tests de JavaScript**: el arnés Jest del core solo cubre módulos ESM en `esm/`, no los AMD. Las funciones de color
  de la vista previa están en `amd/src/local/colour.js` y se prueban con `node:test` (`npm run test:js`), contra la
  misma tabla (`tests/fixtures/colour_parity.json`) con la que PHPUnit prueba `\format_customtiles\local\color`: el
  contraste que ve el docente es el que aplica el servidor. El comportamiento de los módulos con DOM (carrusel,
  editor) se prueba con Behat (escenarios con JavaScript en la CI).

## Compatibilidad

| Aspecto | Resultado |
|---|---|
| Reflujo a 320 px (WCAG 1.4.10) | Sin scroll horizontal ni elementos fuera de la pantalla en los 6 layouts, la página de sección y el mapa. |
| Zoom al 200 % (WCAG 1.4.4) | Igual, con una ventana de 1280×900 al 200 % (640×450 CSS). |
| Teclado | «Saltar al contenido» es la primera parada; orden lógico (cabecera, acciones del curso, secciones, leyenda); foco visible en todas las paradas (el anillo se pinta en la tarjeta entera, no solo en el enlace). Carrusel con flechas del teclado. |
| RTL | Sin paquete de idioma RTL instalado se simuló con `dir="rtl"`. Los layouts usan propiedades lógicas y se reflejan bien; las flechas y chevrons se invierten; el carrusel avanza en el sentido correcto con botones, teclado y gesto. |
| Modo oscuro de Boost (experimental en 5.3) | Si el sitio lo activa y el usuario lo elige, el tema de curso «Claro» (el predeterminado) pasa a «Oscuro». Los temas elegidos a propósito (corporativo, alto contraste…) se mantienen, porque son pares de colores completos con contraste AA comprobado. El modo «automático» depende del dispositivo y el servidor no lo conoce, así que cuenta como claro. axe sin violaciones propias. |
| Tema Classic | No existe en Moodle 5.3. |
| Temas hijos de Boost | Los tokens toman la tipografía de Bootstrap (`--bs-body-font-family`); los colores del curso salen del tema de curso y no dependen de los del sitio. |
| Lector de pantalla | Pendiente: NVDA o VoiceOver no se pueden ejecutar en el entorno de la sesión. Se pide al propietario un recorrido de portada, sección, actividad y editor. |

## Cómo repetir las mediciones

Scripts de Playwright usados en el desarrollo (no se publican con el plugin):

- `perf.js <courseid> <sectionid>`: LCP, CLS, TBT, peso y portadas, con la red y la CPU limitadas.
- `reflow.js`: reflujo a 320 px y zoom al 200 % en todos los layouts, RTL simulado y recorrido con teclado.
- `darkmode.js`: modo oscuro de Boost + axe.
