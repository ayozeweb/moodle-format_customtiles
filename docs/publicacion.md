# Mantenimiento y publicación

Cómo se versiona, se publica y se mantiene al día `format_customtiles` con las versiones nuevas de Moodle.

## 1. Versiones

- **Versionado semántico** en `$plugin->release` (`MAYOR.MENOR.PARCHE`):
  - **PARCHE** (0.7.1): correcciones sin cambios de base de datos ni de comportamiento visible.
  - **MENOR** (0.8.0): funcionalidades nuevas compatibles hacia atrás (opciones nuevas con valor por defecto).
  - **MAYOR** (1.0.0): cambios incompatibles (opciones que desaparecen, cambio de la versión mínima de Moodle).
- `$plugin->version` es la fecha `AAAAMMDDXX` y **siempre crece**. Cada versión nueva tiene su paso en
  `db/upgrade.php` (aunque no migre nada) y `tests/format_customtiles_test.php` comprueba que la actualización
  llega a ella.
- `$plugin->maturity`: `MATURITY_ALPHA` mientras falten piezas del plan; `MATURITY_BETA` cuando lo haya probado
  un centro real; `MATURITY_STABLE` a partir de 1.0.0.
- Una versión se publica con su entrada en `CHANGES.md` y una etiqueta de git `vX.Y.Z`.

## 2. Ramas

| Rama | Contenido |
|---|---|
| `main` | Última versión estable para la versión de Moodle soportada (hoy 5.3). Siempre instalable y con la CI en verde. |
| Ramas de trabajo | Una por cambio; entran en `main` por pull request con la CI en verde. |
| `MOODLE_503_STABLE`, `MOODLE_504_STABLE`… | Solo cuando haga falta mantener a la vez dos versiones de Moodle incompatibles: la rama de la versión antigua recibe correcciones; `main` sigue a la nueva. |

Mientras una misma versión del plugin funcione en varias versiones de Moodle, basta con ampliar
`$plugin->supported` y no hace falta crear ramas.

## 3. Lista de comprobación de una versión

1. **CI en verde** en `main` (las dos ejecuciones: «Moodle Plugin CI» y la última «Core watch»).
2. **Versión**: subir `$plugin->version` y `$plugin->release` en `version.php`, añadir el paso en `db/upgrade.php`
   y actualizar `test_upgrade_from_first_release`.
3. **`CHANGES.md`**: entrada nueva arriba con la fecha.
4. **Capturas**: si cambió algo visible, regenerarlas (`docs/screenshots/`, a 360, 768 y 1280 px).
5. **Zip**: `./release.sh` comprueba que coinciden versión, changelog y paso de actualización, y genera
   `dist/format_customtiles_<release>_<version>.zip` con `git archive` (sin tests, docs, scss ni herramientas,
   según `.gitattributes`).
6. **Instalación limpia** del zip en un Moodle 5.3 vacío (`Administración → Extensiones → Instalar extensiones`
   o `admin/cli/install_database.php`).
7. **Actualización** desde la versión anterior: instalar el zip anterior (`./release.sh vX.Y.Z` lo genera desde su
   etiqueta), crear un curso con portadas, categorías y unidades, sustituir por el zip nuevo, actualizar, ejecutar
   el cron (`admin/cli/adhoc_task.php --execute`) y abrir el curso como alumno.
8. **Etiqueta y publicación**:

   ```bash
   git tag -a v0.8.0 -m "format_customtiles 0.8.0"
   ```

   ```bash
   git push origin v0.8.0
   ```

   Crear la «release» en GitHub con el texto de `CHANGES.md` y el zip adjunto; subir el mismo zip al directorio
   de plugins de Moodle (sección 5).

La 0.7.0 se comprobó así el 18-11-2026: instalación limpia del zip, actualización desde el zip de la 0.6.0 con un
curso con portada, categorías SEPE, unidad y alumno (la tarea creó las copias WebP y se conservaron las
asignaciones), página del curso sin errores de JavaScript a 360 px y desinstalación sin restos.

## 4. Nueva versión de Moodle

El flujo **Core watch** (`.github/workflows/core-watch.yml`) se ejecuta cada lunes contra `MOODLE_503_STABLE` y
contra `main` de Moodle (la próxima versión). En `main` quita `$plugin->supported` solo en su copia, para poder
instalar; sus fallos no marcan la ejecución como fallida, pero son la lista de tareas de la versión siguiente.
Un fallo en `MOODLE_503_STABLE` sí es un error real y se corrige como cualquier otro.

Cuando sale una versión nueva de Moodle:

1. Leer los `UPGRADING.md` de `public/course/format`, `public/theme/boost` y `public/lib` (y `public/course` si
   cambia la página de sección o las tarjetas de actividad). Anotar en `STATUS.md` lo que afecte.
2. Revisar el último «Core watch» contra `main` y corregir lo que falle.
3. Ejecutar todos los tests del plugin, phpcs, Mustache lint y grunt con las reglas de la versión nueva.
4. Revisar a mano: modo edición (estructura y diseño), índice del curso, página de sección, actividad, mapa,
   modo oscuro, y las plantillas del core que el formato sobrescribe o extiende.
5. Regenerar las capturas y compararlas con las anteriores.
6. Ampliar `$plugin->supported` (o crear la rama de la versión anterior, sección 2) y añadir la versión nueva a
   la matriz de `ci.yml`.
7. Publicar una versión MENOR.

## 5. Directorio de plugins de Moodle

### Antes de enviarlo

| Requisito | Estado |
|---|---|
| Licencia GPL v3 y cabecera en cada archivo | Hecho (`LICENSE`, cabeceras comprobadas por phpcs). |
| Nombre de componente (`format_customtiles`) y cadenas en `lang/en` | Hecho. |
| Sin recursos externos (CDN) ni librerías sin declarar | Hecho; fuentes en `thirdpartylibs.xml`. |
| Privacy API | Hecho (`user_preference_provider`). |
| Copias de seguridad y restauración | Hecho, con tests. |
| phpcs, phpdoc, Mustache lint, ESLint/stylelint sin avisos | Hecho en la CI. |
| `$plugin->supported` y versión mínima | Hecho (5.3). |
| Repositorio público, seguimiento de incidencias y documentación | Repositorio en GitHub; incidencias con plantillas; guías en `docs/`. |
| Capturas | `docs/screenshots/` (subir 3–6 representativas al directorio). |
| Madurez | Hoy `ALPHA`. **Recomendado pasar a `BETA`** tras probarlo en un centro real antes de enviarlo. |

### Ficha (texto propuesto)

**Nombre:** Custom tiles

**Resumen (en):** A visual, mobile-first course format: tiles, cards, path, carousel, bento and list layouts with
accessible visual themes, a "continue where you left off" header, progress and states per section and unit,
activity categories and a course map. Built for WCAG 2.1 AA and the usability requirements of Spanish public
employment e-learning (SEPE/SCE).

**Resumen (es):** Formato de curso visual y pensado para el móvil: diseños en mosaico, tarjetas, itinerario,
carrusel, bento y lista, con temas visuales accesibles, cabecera con «Continuar donde lo dejaste», progreso y
estados por sección y unidad, categorías de actividad y mapa del curso. Cumple WCAG 2.1 AA y los requisitos de
usabilidad de la teleformación para el empleo (SEPE/SCE).

**Política de soporte:** se mantiene la última versión de Moodle LTS y la siguiente. Las correcciones de seguridad
se publican solo para la última versión del plugin (ver `SECURITY.md`). Las dudas y errores, en las incidencias
de GitHub.

### Después de publicarlo

- **Traducciones**: una vez aprobado, el plugin aparece en AMOS (lang.moodle.org) y las traducciones se hacen allí.
  `lang/es` se mantiene en el repositorio como referencia completa.
- Cada versión nueva se sube al directorio con el zip de `release.sh` y su entrada de `CHANGES.md`.
