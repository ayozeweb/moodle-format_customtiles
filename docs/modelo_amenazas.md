# Modelo de amenazas de `format_customtiles`

Versión del plugin: 0.4.0. Alcance: el código de este repositorio. No cubre el core de Moodle, el tema ni otros plugins.

Cada control enlaza a la prueba que lo demuestra. «Manual» significa que se comprobó a mano contra una instancia local en la fecha indicada y no hay test automático.

## Actores

| Actor | Qué puede hacer legítimamente |
|---|---|
| Anónimo | Nada dentro de un curso. Ver los logotipos institucionales (públicos por diseño). |
| Alumno | Ver el curso, sus secciones visibles, el mapa y su propio progreso. |
| Docente con edición | Lo anterior, más personalizar secciones (portada, color, icono…) y asignar categorías a actividades de **su** curso. |
| Gestor / administrador | Lo anterior, más definir categorías de actividad, temas visuales adicionales y logotipos. |
| Autor de un backup | Aportar un fichero `.mbz` que otra persona restaura. Controla todo su contenido. |

## Activos

- Estructura y contenido del curso que el usuario no debe ver (secciones ocultas o restringidas, y sus portadas).
- Datos de progreso y de visita de cada usuario (preferencias `format_customtiles_last_*` y `format_customtiles_seen_*`).
- Configuración del sitio (categorías, temas, logotipos).
- La sesión del resto de usuarios (frente a XSS y CSRF).
- Recursos del servidor (memoria y CPU al procesar imágenes).

## Superficies y controles

| # | Superficie | Amenaza | Control | Prueba |
|---|---|---|---|---|
| 1 | `assign.php` | Un alumno asigna categorías | `require_login` + capacidad `format/customtiles:assigncategories`; POST con `sesskey` | `security_test::test_assign_needs_capability_and_own_course`; manual 2026-11-05 |
| 2 | `api::set_activity_category()` | Otro plugin o un docente actúa sobre una actividad de otro curso | Comprobación de capacidad en el curso y de que la actividad pertenece a él; categoría existente | Mismo test |
| 3 | `categories.php` y API de categorías | Un docente crea o borra categorías; CSRF de borrado | Capacidad `format/customtiles:managecategories` en página y API; borrado con confirmación y POST; preset solo por POST | `security_test::test_manage_categories_needs_capability`; Behat `categories.feature`; manual 2026-11-09 |
| 4 | Salida HTML (plantillas) | XSS almacenado desde nombres, subtítulos, colores, iconos, categorías | Escapado de Mustache; `{{{ }}}` solo con contenido saneado por el core; colores por `color::normalise()`; iconos contra lista cerrada; texto con `PARAM_TEXT` | `api_test::test_save_category_sanitises`, `templates_test`, `security_test::test_hostile_backup_is_neutralised` |
| 5 | Restauración de backups | Un `.mbz` manipulado inyecta marcado, colores o nombres de fichero con rutas | Los valores restaurados se validan de nuevo al pintar (layout, tema, colores, icono); las categorías se emparejan por código | `security_test::test_hostile_backup_is_neutralised` |
| 6 | Subida de portadas | Fichero activo (SVG, HTML, PHP disfrazado), metadatos (EXIF/GPS), bomba de descompresión | Solo PNG, JPEG, GIF y WebP; máximo 5 MB y 40 megapíxeles; **siempre se recodifica** y el original no se conserva | `security_test::test_cover_is_reencoded`, `test_cover_rejects_unsafe_files` |
| 7 | `pluginfile` (portadas) | Leer portadas de secciones que el usuario no ve; path traversal; interpretación como contenido activo | Login en el curso, sección `uservisible`, búsqueda en BD por contexto (no por ruta), `nosniff` y CSP restrictiva | `api_test::test_pluginfile_hidden_section`; manual 2026-11-05 |
| 8 | Fragmento del mapa (`core_get_fragment`) | Ver la estructura de un curso sin acceso | El servicio del core valida contexto y acceso; el mapa solo lista lo `uservisible` | `orientation_test::test_map_fragment`, `test_course_map` |
| 9 | Preferencias de usuario | Retención tras borrar el curso; exportación incompleta | Borrado al eliminar el curso y al desinstalar; exportación por la Privacy API | `security_test::test_cleanup_on_delete`, `format_customtiles_test::test_uninstall_cleanup`, `course_data_test::test_privacy_export` |
| 10 | Ajuste «temas visuales adicionales» | Inyección de CSS desde el ajuste de administración | Formato `id|Nombre|preset|#color` validado; el color pasa por el generador de paleta | `skin_test::test_custom_skins` |
| 11 | JavaScript | XSS vía DOM | `textContent` y `setAttribute`; el HTML del mapa llega del servidor ya renderizado | Revisión de código |

## Riesgos aceptados o pendientes

| Riesgo | Estado |
|---|---|
| Los logotipos institucionales se sirven sin login. | Aceptado por diseño; pendiente de confirmar con el propietario (decisión 6 del plan). |
| Los tokens de color y otros valores van en atributos `style`. En un sitio con CSP estricta (`style-src` sin `unsafe-inline`) no se aplicarían. No es una vía de inyección: los valores están saneados. | Pendiente (D7 del plan). |
| Portadas guardadas antes de la 0.4.0 conservan el fichero original hasta que se elija una imagen nueva. | Aceptado; se documenta en `db/upgrade.php`. |
| La línea de contexto en actividades y el aviso de plazo leen fechas y estructura con los permisos del usuario actual; no se ha hecho fuzzing. | Aceptado. |
| No se ha hecho un pentest externo ni análisis con herramientas automáticas de seguridad. | Pendiente antes de publicar una versión estable. |
