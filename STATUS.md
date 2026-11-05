# STATUS

## Hito actual
Hitos 1–6 cerrados. Extras (Bento, Lista, app móvil) sin empezar.

## Hecho
- Plugin `format_customtiles` 0.1.0 ALPHA (Moodle 5.3, `requires 2026100200`), instala sin errores.
- Clase de formato (`lib.php`): opciones de curso `layout`, `layoutmobile`, `skin`, `basecolor`, `hiddensections`; vista multipágina (sección enfocada = `course/section.php` del core).
- Tokens de diseño `--ct-*` (SCSS → `styles.css`), skins «Claro» y «Oscuro» definidos en PHP (`local/skin.php`), generador de paleta accesible (`local/palette.php`, `local/color.php`).
- Cadenas en/es, Privacy (null provider), README bilingüe, GitHub Actions con moodle-plugin-ci, PHPUnit (skins, contraste AA, opciones).

- **Hito 2**: hero (portada, título, resumen, píldoras, anillo de progreso, botón «Continuar»), rejilla Tiles (2 columnas a 360 px, container queries), estados con icono+texto (bloqueada con motivo, oculta, pendiente, en curso, completada, nueva), progreso por sección y global con caché MUC (`progress`, clave usuario_curso, invalidada por eventos de finalización y por `cacherev`). Sección enfocada = página de sección del core con transición `@view-transition`. Verificado sin scroll horizontal a 360/1280 px y en modo edición.
- **Hito 3**: layouts Itinerario (`path`), Tarjetas (`cards`) y Carrusel (`carousel`, scroll-snap + botones + puntos + flechas de teclado, JS en `amd/src/carousel.js`), selector por curso y layout distinto en móvil (`layoutmobile`: se renderizan ambas variantes y se alterna por media query a 48rem). Cuerpo común en `templates/local/tile_body.mustache`. Verificado sin scroll horizontal a 360/768/1280.
- **Hito 4**: 6 presets (Claro, Oscuro, Corporativo, Vibrante, Minimal, Alto contraste AAA) + temas extra del admin (`id|Nombre|preset|#color`, validado) con paleta accesible; fuentes locales OFL (Inter y Atkinson Hyperlegible, `fonts/`, `thirdpartylibs.xml`). Opciones por sección: portada (File API + variantes 480/960 con `srcset`), icono, color/degradado, subtítulo y tiempo estimado, con vista previa en vivo en el formulario de sección. Categorías de actividad (tablas `format_customtiles_cat`/`cmcat`, página de admin, preset SEPE, página de asignación del docente en el menú «Más», insignia con código+icono+nombre accesible, leyenda, API pública `\format_customtiles\api`). Backup/restore de opciones, portadas y categorías (por código). Tests: 22 PHPUnit en verde, phpcs limpio.
- **Hito 5**: mapa del curso (`map.php`, imprimible, un toque desde el hero y la barra de contexto), línea de contexto «Sección · Actividad x de y» (secciones delegadas = UF dentro del módulo) en páginas de sección y de actividad (como acción de cabecera), enlace «Saltar al contenido» al inicio de la página, hoja de impresión (carrusel aplanado, sin fondos ni botones), franja institucional configurable (logotipos subidos por el admin; el plugin no incluye ninguno), botones ≥44 px. Auditoría automática con axe-core (WCAG 2.1 A/AA): 0 violaciones en la portada del curso en tiles, tarjetas, itinerario y carrusel con skins claro, oscuro, vibrante, corporativo (+ color corregido), minimal y alto contraste a 360/768/1280; sin scroll horizontal; LCP ≈ 0,15–0,2 s y CLS ≈ 0 (localhost, sin red). La única violación hallada es del core (botón «Marcar como hecho» en `course/section.php`, `label-content-name-mismatch`).
- **Hito 6**: PHPUnit 27 tests en verde (skins/contraste, opciones, progreso/continuar, categorías, backup/restore, orientación, render de todas las plantillas con su contexto de ejemplo), phpcs (estándar moodle) sin avisos, 30 capturas en `docs/screenshots/` (4 layouts y 6 temas × 360/768/1280, con Playwright contra el Moodle local), README bilingüe con galería.

## Decisiones
- **Mustache lint**: el linter de moodle-plugin-ci necesita el `npm ci` del core, bloqueado en el sandbox (403). En su lugar, un test PHPUnit renderiza cada plantilla con el JSON de su «Example context» y comprueba la cabecera `@template`; el CI de GitHub ejecuta el lint real.
- **Barra de contexto en actividades**: se añade con `$PAGE->add_header_action()`; poner HTML en la parte superior del body quedaría tapado por la barra fija de Boost.
- **Recorte de portada**: no hay recortador interactivo; la imagen se recorta con `object-fit: cover` centrada a la proporción del layout (se avisa en la ayuda del campo).
- **Insignia de categoría en `afterlink`**: la plantilla `cm` del core no ofrece hueco para formatos y sobrescribir la cadena de bloques es frágil.
- **Restauración de categorías**: se hace en `after_restore_course()` (ya con las actividades restauradas) emparejando por código de categoría.
- **Sin WebP/AVIF**: Moodle no los genera desde la File API; se sirven variantes redimensionadas en el formato original.
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

## Siguiente paso (extras)
Layouts Bento y Lista; soporte de la app móvil (`classes/output/mobile.php`). Regenerar `amd/build` con `grunt amd` en un entorno con red.

## Siguiente paso (histórico)
Hito 2: hero con «Continuar», rejilla Tiles responsive, estados y progreso por sección (con MUC).
