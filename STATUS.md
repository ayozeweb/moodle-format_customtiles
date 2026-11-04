# STATUS

## Hito actual
**Hito 1 – Base y sistema visual** (cerrado) → siguiente: Hito 2 (Hero + Tiles + progreso).

## Hecho
- Plugin `format_customtiles` 0.1.0 ALPHA (Moodle 5.3, `requires 2026100200`), instala sin errores.
- Clase de formato (`lib.php`): opciones de curso `layout`, `layoutmobile`, `skin`, `basecolor`, `hiddensections`; vista multipágina (sección enfocada = `course/section.php` del core).
- Tokens de diseño `--ct-*` (SCSS → `styles.css`), skins «Claro» y «Oscuro» definidos en PHP (`local/skin.php`), generador de paleta accesible (`local/palette.php`, `local/color.php`).
- Cadenas en/es, Privacy (null provider), README bilingüe, GitHub Actions con moodle-plugin-ci, PHPUnit (skins, contraste AA, opciones).

## Decisiones
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
