# STATUS

## Hito actual
Hitos 1–6 cerrados. Extras: layouts Bento y Lista compacta hechos (axe limpio); app móvil sin empezar.

## Hecho
- Plugin `format_customtiles` 0.1.0 ALPHA (Moodle 5.3, `requires 2026100200`), instala sin errores.
- Clase de formato (`lib.php`): opciones de curso `layout`, `layoutmobile`, `skin`, `basecolor`, `hiddensections`; vista multipágina (sección enfocada = `course/section.php` del core).
- Tokens de diseño `--fct-*` (SCSS → `styles.css`), skins «Claro» y «Oscuro» definidos en PHP (`local/skin.php`), generador de paleta accesible (`local/palette.php`, `local/color.php`).
- Cadenas en/es, Privacy (null provider), README bilingüe, GitHub Actions con moodle-plugin-ci, PHPUnit (skins, contraste AA, opciones).

- **Hito 2**: hero (portada, título, resumen, píldoras, anillo de progreso, botón «Continuar»), rejilla Tiles (2 columnas a 360 px, container queries), estados con icono+texto (bloqueada con motivo, oculta, pendiente, en curso, completada, nueva), progreso por sección y global con caché MUC (`progress`, clave usuario_curso, invalidada por eventos de finalización y por `cacherev`). Sección enfocada = página de sección del core con transición `@view-transition`. Verificado sin scroll horizontal a 360/1280 px y en modo edición.
- **Hito 3**: layouts Itinerario (`path`), Tarjetas (`cards`) y Carrusel (`carousel`, scroll-snap + botones + puntos + flechas de teclado, JS en `amd/src/carousel.js`), selector por curso y layout distinto en móvil (`layoutmobile`: se renderizan ambas variantes y se alterna por media query a 48rem). Cuerpo común en `templates/local/tile_body.mustache`. Verificado sin scroll horizontal a 360/768/1280.
- **Hito 4**: 6 presets (Claro, Oscuro, Corporativo, Vibrante, Minimal, Alto contraste AAA) + temas extra del admin (`id|Nombre|preset|#color`, validado) con paleta accesible; fuentes locales OFL (Inter y Atkinson Hyperlegible, `fonts/`, `thirdpartylibs.xml`). Opciones por sección: portada (File API + variantes 480/960 con `srcset`), icono, color/degradado, subtítulo y tiempo estimado, con vista previa en vivo en el formulario de sección. Categorías de actividad (tablas `format_customtiles_cat`/`cmcat`, página de admin, preset SEPE, página de asignación del docente en el menú «Más», insignia con código+icono+nombre accesible, leyenda, API pública `\format_customtiles\api`). Backup/restore de opciones, portadas y categorías (por código). Tests: 22 PHPUnit en verde, phpcs limpio.
- **Hito 5**: mapa del curso (`map.php`, imprimible, un toque desde el hero y la barra de contexto), línea de contexto «Sección · Actividad x de y» (secciones delegadas = UF dentro del módulo) en páginas de sección y de actividad (como acción de cabecera), enlace «Saltar al contenido» al inicio de la página, hoja de impresión (carrusel aplanado, sin fondos ni botones), franja institucional configurable (logotipos subidos por el admin; el plugin no incluye ninguno), botones ≥44 px. Auditoría automática con axe-core (WCAG 2.1 A/AA): 0 violaciones en la portada del curso en tiles, tarjetas, itinerario y carrusel con skins claro, oscuro, vibrante, corporativo (+ color corregido), minimal y alto contraste a 360/768/1280; sin scroll horizontal; LCP ≈ 0,15–0,2 s y CLS ≈ 0 (localhost, sin red). La única violación hallada es del core (botón «Marcar como hecho» en `course/section.php`, `label-content-name-mismatch`).
- **Hito 6**: PHPUnit 27 tests en verde (skins/contraste, opciones, progreso/continuar, categorías, backup/restore, orientación, render de todas las plantillas con su contexto de ejemplo), phpcs (estándar moodle) sin avisos, 30 capturas en `docs/screenshots/` (4 layouts y 6 temas × 360/768/1280, con Playwright contra el Moodle local), README bilingüe con galería.

- **Revisión de buenas prácticas (puntos 4–9)**: Behat (4 features: página del curso, opciones de sección, categorías, carrusel); borrado de categorías con página de confirmación y POST, y carga del preset solo por POST; HTML de `assign.php`/`categories.php` movido a plantillas Mustache con renderer; cabeceras de licencia en todos los SCSS y en `styles.css`; copyright con el usuario público `ayozeweb` (sustituir por el nombre legal); cadenas huérfanas eliminadas.

- **Plan de mejora, Fase 0 (0.2.0)**: prefijo CSS renombrado a `fct-` (clases, variables `--fct-*`, atributos `data-fct-*`; los nombres de opciones de sección `ct_*` no cambian); caché de petición para las asignaciones de categoría; `db/uninstall.php`; test de actualización y de desinstalación; `.gitattributes`, `.editorconfig`, `CHANGES.md`, `CONTRIBUTING.md`, `$plugin->supported`; paso de CI que comprueba que `styles.css` coincide con `scss/`; capturas regeneradas.

- **Plan de mejora, Fase 1 (0.3.0)**: vista de sección con cabecera propia, tarjetas de actividad con el tema del curso, navegación anterior/siguiente y gesto de deslizar, mapa del curso como panel (`<dialog>` + fragmento), barra de contexto en una línea con «volver» (sustituye a la miga de pan en móvil), transición compartida tile → cabecera, estados vacíos, opción para repetir el título, «nueva» = añadido desde la visita anterior. Medido a 360×800: contenido 83 % en portada, 92 % en sección y 84 % en actividad (contando barra superior, navegación secundaria y pie fijo del core). axe sin violaciones propias en portada, sección, actividad y mapa con los temas claro, oscuro, alto contraste y vibrante.

- **CI real en verde por primera vez** (ejecución 16, commit `7e4f006`): lint PHP, validación, Mustache, phpcs, grunt, CSS compilado, phpdoc, PHPUnit (36) y Behat (16 escenarios, incluidos los de JS). Las 15 ejecuciones anteriores fallaban.

- **0.3.1**: aviso de plazo por actividad («Vence hoy / mañana / en N días», hasta 7 días, y «Fuera de plazo» si hay seguimiento de finalización y sigue pendiente), leído de las fechas que cada módulo declara al core (`core\activity_dates`); opción de curso para mantener la miga de pan en móviles; copyright a nombre de Ayoze M de Paz Paz. Comprobado visualmente en claro y oscuro; al probarlo, axe detectó contraste insuficiente del texto de fechas del core en temas oscuros, corregido.

- **Plan de mejora, Fase 4 (0.4.0)**: modelo de amenazas (`docs/modelo_amenazas.md`), capacidades propias en páginas y API, portadas validadas y siempre recodificadas, limpieza al borrar actividades y cursos, test de backup manipulado, política de divulgación. 44 tests PHPUnit.

- **0.5.0 — jerarquía módulo → unidad**: las subsecciones del core (`mod_subsection`) son unidades de la sección que las contiene. La sección agrega progreso, actividades y tiempo de sus unidades; cada unidad tiene estado, progreso, página propia, numeración «5.2» y «Unidad x de y». Se muestran en tarjetas, itinerario, carrusel, cabecera de sección y mapa. 50 tests PHPUnit, 15 escenarios Behat sin JS.

- **Plan de mejora, Fase 2 (0.6.0) — editor visual**: conmutador Estructura/Diseño en modo edición; vista de diseño con un tile por sección y unidad y botón «Editar aspecto»; formulario modal (`core_form\dynamic_form`) con portada, punto focal, icono, colores con selector nativo y contraste en vivo, subtítulo, tiempo y «aplicar también a»; colores automáticos para todo el curso; guardar refresca solo ese tile (fragmento) y devuelve el foco. 59 tests PHPUnit; escenario Behat con JS para la CI. axe sin violaciones propias en la vista de diseño (360 y 1280 px).

## Decisiones
- **Punto focal en vez de recortador**: se guarda «x,y» en % y se aplica con `object-position`; no destruye el original, sirve para todos los layouts y se puede fijar con ratón, flechas o números.
- **La vista de diseño no reemplaza a la de estructura**: mover, añadir y borrar siguen en la lista del core (estado reactivo y arrastrar y soltar intactos). La elección se guarda como preferencia de usuario.
- **En la vista de diseño no se exporta la lista del core**: exportarla registraba JavaScript de desplegables que no se pintan (errores en consola).
- **Formularios dinámicos**: el JavaScript añadido al definirlos no llega al navegador, así que la vista previa del modal se inicializa desde `designer.js` con un observador.
- **Pendiente de la Fase 2**: selector de layout y tema con miniaturas en los ajustes del curso, plantillas de curso y buscador de iconos. En el modal, axe señala dos cosas del core: la lista vacía del selector múltiple y los botones pequeños del gestor de archivos.
- **Error que había**: el progreso de una sección ignoraba lo que hubiera dentro de sus subsecciones (en el curso de prueba, 3 de 10 actividades), y «Continuar» mandaba esas actividades al final del curso. Corregido al agregar en el orden de la página.
- **Tiempo estimado**: si la sección tiene tiempo propio, manda; si no, es la suma de sus unidades.
- **Nombre neutro «unidad»** en la interfaz (no «UF»), porque el formato no es solo para SEPE; el nombre real lo pone el docente en cada subsección.
- **Un solo nivel de anidamiento**, que es lo que permite el core.
- **Estilo de las unidades anidadas**: el core las pinta con reglas muy específicas y variables de su sistema de diseño (`--mds-*`); se igualan esas variables al tema y se usa un selector equivalente. Riesgo si 5.4 cambia ese marcado: volverían a verse como cajas claras, sin romper nada.
- **Portadas**: se guarda una copia principal saneada (máx. 1920 px) además de las variantes 480/960, porque el formulario de sección necesita un archivo que mostrar al volver a editar; si la imagen no cambia no se reprocesa.
- **Restauración**: el core escribe las opciones sin pasar por la validación del plugin; por eso todo se valida de nuevo al pintar. El test lo comprobó: un layout inválido llega a la base de datos, pero la página usa el valor por defecto.
- **Pendiente de la Fase 4**: compatibilidad con CSP estricta (estilos en línea, D7), decisión sobre los logotipos públicos y tests Behat de permisos por rol (las denegaciones lanzan excepción en Behat; están cubiertas por PHPUnit y por la prueba manual).
- **Miga de pan en móvil y seguimiento**: prueba controlada a 360 px con la miga oculta y visible; en ambos casos Moodle registra los mismos eventos (`course_module_viewed`, `section_viewed`, `course_viewed`), porque el registro es del servidor al cargar la página. Ocultarla no afecta a logs, tiempos ni finalización. Se mantiene oculta por defecto (84 % de contenido en la página de actividad frente a 77 %) y se añade la opción «Mantener la miga de pan en móviles» por si una inspección la exige a la vista.
- **Aviso de plazo solo en la página de sección**, no en los tiles de la portada: leer las fechas de todas las actividades del curso costaría consultas por actividad en cada carga de la portada. Pendiente si se quiere: resumen «N vencen pronto» por sección, con caché.
- **«Fuera de plazo»** solo se muestra cuando se sabe que la actividad sigue pendiente (finalización activada); sin seguimiento no se puede distinguir de una actividad ya entregada.
- **Comprobar siempre con las herramientas de la CI**: `moodle-plugin-ci grunt` y `mustache` están instalados en local (`/home/user/tools/ci`) y se ejecutan antes de subir. Los fallos de la CI venían de comprobaciones que aquí se sustituían por aproximaciones (lint de Mustache, avisos de ESLint ocultos al recortar la salida).
- **Miga de pan oculta en móvil** (< 768 px) dentro de cursos con este formato y fuera del modo edición: la barra de contexto da la misma orientación y el enlace de vuelta en una sola línea. En la página de actividad era la diferencia entre 77 % y 84 % de contenido.
- **Tarjetas de actividad solo con CSS** sobre el marcado del core (variables de Bootstrap y áreas de la rejilla `activity-grid`), sin copiar plantillas. Riesgo: si 5.4 cambia esas áreas, la insignia de categoría vuelve a su fila; no se rompe nada.
- **Fragmento del mapa sin `require_login` propio**: `core_get_fragment` ya valida contexto y acceso; llamarlo de nuevo lanza un error de codificación.
- **«Siguiente actividad»**: no se añade botón propio; el pie fijo de 5.3 ya lo ofrece.
- **Violación axe del core** (`label-content-name-mismatch` en el botón de finalización) presente en sección y actividad: se documenta, no se tapa.
- **CI**: el escenario Behat del carrusel era inestable (dependía de que terminase el desplazamiento suave); corregido en el JS. El paso «CSS compilado» fallaba por un `find | head` bajo `pipefail`.
- **CI real**: hasta el commit `104af1b` las 13 ejecuciones en GitHub fallaban, y solo por el paso Mustache Lint (atributo `pathLength` en el anillo SVG). El resto pasaba, incluido Behat con JS (12/12). Corregido quitando el atributo. Lección: mirar la CI real, no solo las comprobaciones locales. El lint oficial se reproduce ya en local con `moodle-plugin-ci` y su `vnu.jar`.
- **Behat**: 10 escenarios sin JS pasan en local (BrowserKit). Los 3 `@javascript` (carrusel y marcar actividad como hecha) validados solo con `--dry-run`: el sandbox no tiene un chromedriver que coincida con Chrome 153 (descarga bloqueada). El carrusel con botones y teclado sí se verificó con Playwright. El paso `behat` de la CI los ejecutará.
- **Copyright**: no se publica el correo del autor ni se supone un nombre real; usar el nombre legal cuando se decida.
- **Mustache lint**: el linter de moodle-plugin-ci necesita el `npm ci` del core, bloqueado en el sandbox (403). En su lugar, un test PHPUnit renderiza cada plantilla con el JSON de su «Example context» y comprueba la cabecera `@template`; el CI de GitHub ejecuta el lint real.
- **Barra de contexto en actividades**: se añade con `$PAGE->add_header_action()`; poner HTML en la parte superior del body quedaría tapado por la barra fija de Boost.
- **Recorte de portada**: no hay recortador interactivo; la imagen se recorta con `object-fit: cover` centrada a la proporción del layout (se avisa en la ayuda del campo).
- **Insignia de categoría en `afterlink`**: la plantilla `cm` del core no ofrece hueco para formatos y sobrescribir la cadena de bloques es frágil.
- **Restauración de categorías**: se hace en `after_restore_course()` (ya con las actividades restauradas) emparejando por código de categoría.
- **Sin WebP/AVIF**: Moodle no los genera desde la File API; se sirven variantes redimensionadas en el formato original.
- **AMD build**: `amd/build/*.min.js` se genera ya con el `grunt amd` real del core (ESLint + rollup/babel con las versiones fijadas del shrinkwrap). En el sandbox hubo que instalar las dependencias de Node del core sin `shifter` e `istanbul` (tarballs de GitHub bloqueados); no afectan a `amd`.
- **CSS**: `styles.css` se formatea con el stylelint de Moodle (`build-css.sh`, 4 espacios) y pasa sin errores. Se desactivan solo `csstree/validator` y `property-no-unknown` en el SCSS, porque el validador del core no conoce container queries ni `@view-transition`.
- **Plurales**: cadenas separadas singular/plural (`oneactivity`/`nactivities`, `onesection`/`nsections`) mediante `local\plural`.
- **CI**: añadido el paso `moodle-plugin-ci grunt` y corregida la variable `extensions` inexistente.
- **«Continuar»**: la última actividad visitada se guarda como preferencia de usuario (hook `before_http_headers`), con Privacy API `user_preference_provider`; si está completada salta a la siguiente pendiente. Sin tabla propia.
- **Contenedores de consulta** (`container-type`) solo en `.fct-herowrap` y `.fct-main`, no en `.fct-root`, para no crear bloque contenedor de elementos `position: fixed` del modo edición.
- Título del hero duplica el H1 del tema (requisito del brief); queda como `h2`.
- **Skins en PHP, no solo en CSS**: así hay una única fuente de verdad, se pueden comprobar los contrastes automáticamente y los skins creados por el admin (hito 4) se inyectan igual como variables CSS en línea sobre `.fct-root`.
- **Sección enfocada = `section.php` del core** (`get_course_display()` devuelve MULTIPAGE): respeta edición reactiva, índice del curso y navegación lineal de 5.3 sin duplicarlos.
- **CSS compilado versionado**: los plugins de Moodle sirven `styles.css` plano; el SCSS vive en `scss/` y se compila con `./build-css.sh`.
- Borde de controles con contraste ≥ 3:1 (WCAG 1.4.11); las tarjetas se separan con sombra, no con borde.

## Entorno de pruebas (sandbox)
- Moodle 5.3 en `/home/user/moodle` (fuera del repo), plugin enlazado con symlink; PostgreSQL 16 local (puerto 5433; se relajó la comprobación de PG 17 solo en ese checkout). Sin Docker.
- PHPUnit: `php vendor/bin/phpunit --testsuite format_customtiles_testsuite`.

## Bloqueos
- Ninguno por ahora. Sin red para descargar `composer.phar`/otros (se usa un wrapper local).

## Siguiente paso (extras)
Soporte de la app móvil (`classes/output/mobile.php`). Regenerar `amd/build` con `grunt amd` en un entorno con red.

## Siguiente paso (histórico)
Hito 2: hero con «Continuar», rejilla Tiles responsive, estados y progreso por sección (con MUC).
