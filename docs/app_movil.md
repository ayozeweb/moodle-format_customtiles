# App móvil oficial de Moodle (fase 6)

> **Estado: implementado y probado en el servidor, sin verificar todavía en la app.** El plan exige no
> declarar soporte sin haberlo visto funcionar. En el entorno de desarrollo no se pudo ejecutar la app
> (el sistema de permisos bloqueó arrancar su servidor de desarrollo), así que falta la prueba de la sección 4.

## 1. Qué hace

Sin este soporte, la app muestra los cursos de un formato desconocido con su formato por defecto. Con él:

- **Portada del curso**: resumen, píldoras (secciones, unidades, actividades, duración, fechas), barra de
  progreso global y botón **«Continuar donde lo dejaste»**, que abre la actividad con la navegación de la app.
- **Rejilla de secciones** (una columna en móvil, dos en tableta, tres en horizontal) con portada o color,
  icono, número, actividades, tiempo, unidades, progreso y **estado con icono y texto**. Las bloqueadas muestran
  el motivo y no se abren.
- **Vista de sección**: «Volver al curso», «Sección 2 de 6», cabecera con progreso y estado, el contenido de la
  sección con el componente de la propia app (`<core-course-section>`: actividades, finalización, descargas y
  unidades/subsecciones funcionan como en cualquier curso) y botones anterior/siguiente que saltan las bloqueadas.
- **Tema**: superficies y texto siguen el tema claro u oscuro de la app; el tema del curso aporta el color de
  acento (con su color de texto comprobado).
- **«Continuar» también aprende de la app**: un observador de `\core\event\course_module_viewed` guarda la última
  actividad vista aunque se abra desde la app o un servicio web, que no cargan la página web de la actividad.

## 2. Cómo está hecho

| Pieza | Función |
|---|---|
| `db/mobile.php` | Declara el formato en `CoreCourseFormatDelegate`, con método y hoja de estilos. Desactiva el índice de la app (la rejilla hace de índice). |
| `classes/output/mobile.php` | `mobile_course_view()`: comprueba el acceso, reúne los datos con las mismas clases que la web (`course_data`, `hero`, `section_style`) y renderiza la plantilla. |
| `mobile/course.mustache` | Plantilla Angular/Ionic. Fuera de `templates/` porque el lint de Mustache la validaría como HTML de navegador. |
| `mobile.css` | Estilos de la app (CSS a mano, variables de Ionic). |

La sección abierta se guarda en `CONTENT_OTHERDATA.section`; todo el texto llega preparado desde PHP, así que
las únicas expresiones de Angular son las previstas: abrir y cerrar secciones, la lista `sections` de la app y
`CoreCourseHelperProvider.navigateToModule()`.

## 3. Seguridad: texto de usuario en una plantilla Angular

La app compila la respuesta como plantilla Angular, donde `{{ … }}` es código. Un docente que pusiera
`{{constructor.constructor('…')()}}` en el nombre de una sección podría ejecutar código en la app de cada alumno.

Durante el desarrollo se comprobó con el compilador oficial de Angular (la misma versión 20.3 que usa la app 5.3)
que **escapar las llaves como entidades HTML (`&#123;`) no protege**: Angular decodifica las entidades antes de
buscar expresiones. El escapado final (`mobile::escape()`) sustituye cada llave por la expresión constante que
documenta Angular (`{{ '{' }}`). Con nombres, subtítulos y resúmenes hostiles, el análisis de la plantilla no
encuentra ninguna expresión que no sea esa constante, y una llave suelta tampoco rompe la plantilla.
`tests/mobile_test.php` fija este comportamiento.

## 4. Verificación pendiente (propietario)

1. En el sitio: **Administración del sitio → Móvil → Configuración móvil → Activar servicios web para
   dispositivos móviles**.
2. Con la app oficial (o la versión web de desarrollo de la app, `npm start` en `moodlehq/moodleapp`), entrar
   como alumno en un curso con este formato.
3. Comprobar, a ser posible en móvil y en tableta y con la app en claro y en oscuro:
   - se ve la portada con progreso y «Continuar», y «Continuar» abre la actividad correcta;
   - la rejilla muestra portadas, colores, estados y progreso; una sección bloqueada muestra el motivo;
   - al tocar una sección se ven sus actividades (y unidades), se pueden completar y descargar, y el progreso
     se actualiza al volver (tirar hacia abajo para refrescar);
   - «Anterior» / «Siguiente» y «Volver al curso» funcionan;
   - tras ver una actividad en la app, «Continuar» en la web apunta a ella.
4. Anotar el resultado en `STATUS.md`. Hasta entonces el README describe el soporte como «pendiente de verificación».

## 5. Límites conocidos

- Solo el layout de rejilla (el plan pide como mínimo Tiles). Los demás layouts del curso se muestran como
  rejilla en la app.
- Sin mapa del curso ni leyenda de categorías en la app.
- El modo «sin conexión» depende de lo que la app guarde de `tool_mobile_get_content`; no se ha probado.
