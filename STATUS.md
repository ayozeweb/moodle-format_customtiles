# STATUS

## Hito actual
Hitos 1, 2 y 3 cerrados → siguiente: Hito 4 (personalización por sección, presets, categorías).

## Hecho
- Plugin `format_customtiles` 0.1.0 ALPHA (Moodle 5.3, `requires 2026100200`), instala sin errores.
- Clase de formato (`lib.php`): opciones de curso `layout`, `layoutmobile`, `skin`, `basecolor`, `hiddensections`; vista multipágina (sección enfocada = `course/section.php` del core).
- Tokens de diseño `--ct-*` (SCSS → `styles.css`), skins «Claro» y «Oscuro» definidos en PHP (`local/skin.php`), generador de paleta accesible (`local/palette.php`, `local/color.php`).
- Cadenas en/es, Privacy (null provider), README bilingüe, GitHub Actions con moodle-plugin-ci, PHPUnit (skins, contraste AA, opciones).

- **Hito 2**: hero (portada, título, resumen, píldoras, anillo de progreso, botón «Continuar»), rejilla Tiles (2 columnas a 360 px, container queries), estados con icono+texto (bloqueada con motivo, oculta, pendiente, en curso, completada, nueva), progreso por sección y global con caché MUC (`progress`, clave usuario_curso, invalidada por eventos de finalización y por `cacherev`). Sección enfocada = página de sección del core con transición `@view-transition`. Verificado sin scroll horizontal a 360/1280 px y en modo edición.
- **Hito 3**: layouts Itinerario (`path`), Tarjetas (`cards`) y Carrusel (`carousel`, scroll-snap + botones + puntos + flechas de teclado, JS en `amd/src/carousel.js`), selector por curso y layout distinto en móvil (`layoutmobile`: se renderizan ambas variantes y se alterna por media query a 48rem). Cuerpo común en `templates/local/tile_body.mustache`. Verificado sin scroll horizontal a 360/768/1280.

## Decisiones
- **AMD build**: `npm ci` del core está bloqueado por la red del sandbox (403), así que `amd/build/*.min.js` se genera con `/home/user/tools/build-amd.js` (babel AMD + terser), una aproximación a `grunt amd`; conviene regenerarlo con grunt en un entorno con red.
- **«Continuar»**: la última actividad visitada se guarda como preferencia de usuario (hook `before_http_headers`), con Privacy API `user_preference_provider`; si está completada salta a la siguiente pendiente. Sin tabla propia.
- **Contenedores de consulta** (`container-type`) solo en `.ct-herowrap` y `.ct-main`, no en `.ct-root`, para no crear bloque contenedor de elementos `position: fixed` del modo edición.
- Título del hero duplica el H1 del tema (requisito del brief); queda como `h2`.
- **Skins en PHP, no solo en CSS**: así hay una única fuente de verdad, se pueden comprobar los contrastes automáticamente y los skins creados por el admin (hito 4) se inyectan igual como variables CSS en línea sobre `.ct-root`.
- **Sección enfocada = `section.php` del core** (`get_course_display()` devuelve MULTIPAGE): respeta edición reactiva, índice del curso y navegación lineal de 5.3 sin duplicarlos.
- **CSS compilado versionado**: los plugins de Moodle sirven `styles.css` plano; el SCSS vive en `scss/` y se compila con `./build-css.sh`.
- Borde de controles con contraste ≥ 3:1 (WCAG 1.4.11); las tarjetas se separan con sombra, no con borde.

## Entorno de pruebas (sandbox)
- Moodle 5.3 en `/home/user/moodle` (fuera del repo), plugin enlazado con symlink; PostgreSQL 16 local (puerto 5433; se relajó la comprobación de PG 17 solo en ese checkout). Sin Docker.
- PHPUnit: `php vendor/bin/phpunit --testsuite format_customtiles_testsuite`.

## Bloqueos
- Ninguno por ahora. Sin red para descargar `composer.phar`/otros (se usa un wrapper local).

## Siguiente paso
Hito 2: hero con «Continuar», rejilla Tiles responsive, estados y progreso por sección (con MUC).
