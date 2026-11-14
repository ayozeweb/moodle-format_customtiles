# Plan de mejora de `format_customtiles`

Documento de trabajo para aplicar en sesiones posteriores de Claude Code. Está escrito para que una sesión nueva, sin el contexto de la conversación que lo generó, pueda ejecutarlo.

- **Estado de partida:** rama `claude/brave-sagan-2yxee3`, commit `57fff7a`, versión `2026110405` (0.1.0, ALPHA), Moodle 5.3.
- **Objetivo del producto:** que un curso de Moodle se vea y se use como una aplicación moderna, sobre todo en móvil y tablet, cumpliendo de serie los requisitos de usabilidad y accesibilidad que verifican los servicios públicos de empleo (SEPE/SCE).
- **Naturaleza del proyecto:** plugin independiente, no un fork de `format_tiles`. No contiene código de `format_tiles` y no debe contenerlo (ver sección 4).

---

## 0. Cómo usar este plan

1. Lee primero `CLAUDE.md` (reglas del proyecto), `STATUS.md` (decisiones y bloqueos) y `SECURITY.md`.
2. Aplica **una fase por sesión**. Cada fase termina con: PHPUnit y Behat en verde, phpcs y `grunt` sin avisos, `STATUS.md` actualizado, commit y push.
3. No empieces una fase si la anterior dejó algo en rojo.
4. Las tareas marcadas con **[DECISIÓN]** necesitan respuesta del propietario antes de implementarse. Están reunidas en la sección 6.
5. Las afirmaciones sobre normativa del SEPE de este documento **no están contrastadas con el texto oficial**. La fase 3 empieza precisamente por contrastarlas. No des por bueno ningún requisito normativo sin citar el artículo o apartado.

### Modelo recomendado por fase

| Fase | Trabajo | Modelo | Por qué |
|---|---|---|---|
| 0 | Higiene y release | Sonnet | Tareas mecánicas y bien especificadas |
| 1 | Vista de sección y actividad en móvil | Opus | Diseño de interacción e integración delicada con el core 5.3 |
| 2 | Editor visual del docente | Opus | Estado reactivo del core, UX de edición, muchas decisiones |
| 3a | Matriz de conformidad SEPE | Opus | Lectura de normativa y trazabilidad requisito → prueba |
| 3b | Implementación de lo que salga de 3a | Sonnet | Una vez fijada la matriz, es trabajo acotado |
| 4a | Modelo de amenazas y revisión | Opus | Razonamiento adversarial |
| 4b | Capacidades, validaciones y tests | Sonnet | Implementación guiada por 4a |
| 5 | Calidad, rendimiento y compatibilidad | Sonnet | Tests, CI y mediciones |
| 6 | App móvil | Opus | API distinta (plantillas Ionic), poca documentación |
| 7 | Mantenimiento y publicación | Sonnet | Documentación y automatización |

Regla práctica: **Opus para decidir, Sonnet para ejecutar**. Si una sesión con Sonnet se atasca en una decisión de diseño, anota la duda en `STATUS.md` y pásala a una sesión con Opus en lugar de improvisar.

---

## 1. Diagnóstico de partida

### 1.1 Qué funciona y está comprobado

- Seis layouts (tiles, tarjetas, itinerario, carrusel, bento, lista), con opción distinta en móvil.
- Seis temas visuales más temas del administrador, con contraste AA comprobado por test.
- Cabecera con progreso y «Continuar», progreso por sección con caché, estados con icono y texto.
- Portada, icono, color, subtítulo y tiempo por sección.
- Categorías de actividad con preset SEPE, insignia, leyenda y API pública.
- Mapa del curso, línea de contexto, enlace de salto, hoja de impresión, franja institucional.
- Backup y restauración, Privacy API.
- 30 tests PHPUnit, 10 escenarios Behat sin JS, phpcs y stylelint limpios, `amd/build` generado con el grunt real.
- axe-core sin violaciones en la portada del curso a 360, 768 y 1280 px.

### 1.2 Carencias conocidas (deuda real, no hipotética)

| # | Carencia | Impacto |
|---|---|---|
| D1 | **La vista de sección y la de actividad apenas están tocadas.** Usan las tarjetas del core tal cual. Es donde el alumno pasa casi todo el tiempo. | Alto |
| D2 | El docente personaliza desde el formulario clásico de sección. No hay edición en el propio tile ni recortador de imagen. | Alto |
| D3 | Los 3 escenarios Behat con JS nunca se han ejecutado (solo `--dry-run`). No hay tests Jest. | Medio |
| D4 | La jerarquía módulo → unidad formativa solo existe como texto en la línea de contexto. No hay progreso por módulo que agregue sus UF. | Alto para SEPE |
| D5 | No hay informe de seguimiento para el tutor. Se delega en `local_customreport`, cuyo contrato no está definido. | Alto para SEPE |
| D6 | Sin capacidades propias. `api::set_activity_category()` no comprueba permisos. | Medio |
| D7 | Los tokens de color van en un atributo `style` en línea. Un sitio con CSP estricta (`style-src` sin `unsafe-inline`) los bloquearía. | Medio |
| D8 | El redimensionado de portadas usa GD sin límite de dimensiones ni reencodado que elimine metadatos. | Medio |
| D9 | El rendimiento solo se ha medido en localhost, con un curso de 7 secciones y sin red real. | Medio |
| D10 | Sin soporte para la app móvil oficial. | Medio |
| D11 | Sin `.gitattributes`, `CHANGES.md`, `CONTRIBUTING.md`, `.editorconfig`, `$plugin->supported` ni test de actualización. Las capturas (5,8 MB) entrarían en el zip. | Bajo |
| D12 | Caché estática en `classes/output/courseformat/content/cm.php`. | Bajo |
| D13 | El título del hero duplica el H1 del tema. | Bajo |
| D14 | La línea de contexto en actividades va como acción de cabecera, no a todo el ancho. | Bajo |
| D15 | Sin probar: RTL, tema Classic, temas hijos, modo oscuro experimental de 5.3, MariaDB/MySQL, PHP 8.4, PostgreSQL 17 (se probó con 16). | Medio |
| D16 | Copyright con el usuario `ayozeweb` como marcador. | Bajo |
| D17 | Prefijo CSS `ct-`, corto y con riesgo de colisión. | Bajo |
| D18 | El estado «nueva» es una heurística (actividad añadida en los últimos 7 días), no «no visto por este usuario». | Bajo |
| D19 | Sin WebP/AVIF. | Bajo |

---

## 2. Principios que no se negocian

1. **Móvil primero de verdad.** Cada pantalla se diseña a 360 px y se comprueba sin scroll horizontal y con objetivos táctiles ≥ 44 px antes de mirar el escritorio.
2. **Sobre el core, no contra él.** Se extiende `core_courseformat` (clases de salida, estado reactivo, plantillas). No se duplica la navegación lineal ni el pie fijo de 5.3: se integran.
3. **Accesible por construcción.** WCAG 2.1 AA, el color nunca es el único portador de información, `prefers-reduced-motion` respetado, teclado completo.
4. **Sin dependencias externas en tiempo de ejecución.** Nada de CDN. Todo recurso dentro del plugin, con licencia en `thirdpartylibs.xml`.
5. **Cada mejora visual lleva su prueba.** Si no se puede comprobar de forma automática (axe, Behat, PHPUnit, medición), se documenta cómo se comprobó a mano.
6. **Nada de afirmaciones normativas sin fuente.** Lo que se diga que «cumple el Anexo IV» debe apuntar al apartado y a la prueba que lo demuestra.

---

## 3. Fases

Tamaño: S = una sesión corta, M = una sesión larga, L = varias sesiones.

### Fase 0 — Higiene y base de release (S, Sonnet)

Objetivo: dejar el repositorio en condiciones de publicar una 0.2.0 sin cambiar funcionalidad.

- [ ] `.gitattributes` con `export-ignore` para `docs/`, `tests/`, `scss/`, `.github/`, `STATUS.md`, `SECURITY.md`, `CLAUDE.md`, `plan_mejora_fable.md`, `package.json`, `build-css.sh` (D11). Comprobar con `git archive` que el zip resultante instala.
- [ ] `CHANGES.md` (formato «Keep a Changelog») reconstruido desde el historial de git, `CONTRIBUTING.md`, `.editorconfig`.
- [ ] `$plugin->supported = [503, 503]` en `version.php`.
- [ ] Paso de CI que recompila el CSS y falla si `styles.css` difiere (`npm run build:css && git diff --exit-code styles.css`).
- [ ] Sustituir la caché estática de `cm.php` por una caché de petición (`cache::make_from_params(cache_store::MODE_REQUEST, ...)`) y quitar `reset_cache()` (D12).
- [ ] Test de actualización: instalar el esquema de 0.1.0 y pasar `db/upgrade.php`.
- [ ] Limpieza al desinstalar: borrar el área de archivos `logos` del contexto de sistema (`db/uninstall.php`).
- [ ] **[DECISIÓN]** Copyright definitivo (D16) y prefijo CSS (D17). Si se renombra el prefijo, hacerlo aquí y de una vez (SCSS, plantillas, JS, Behat), con las capturas regeneradas.

Criterio de aceptación: CI completa en verde en GitHub (no solo en local), incluido Behat con JS. Esta fase es la primera vez que la CI real se ejecuta: **arreglar lo que falle allí es parte de la fase** (D3).

---

### Fase 1 — La vista de sección y de actividad en móvil (L, Opus)

Es la mejora de mayor impacto visual (D1). Hoy la portada del curso está cuidada y, al entrar en una sección, el alumno vuelve al Moodle de siempre.

Antes de escribir código: leer los `UPGRADING.md` de 5.3 de `public/course/format`, `public/theme/boost` y `public/lib` (tarjetas de actividad, navegación lineal, pie fijo, API de componentes React).

**1.1 Cabecera de sección**
- [ ] Sustituir la cabecera estándar de la página de sección por una propia: portada o color de la sección, título, subtítulo, tiempo estimado, barra de progreso y estado. Reutilizar `section_style::export()` y `course_data`.
- [ ] Añadir `view-transition-name` compartido entre el tile de la portada y esta cabecera, para que la transición sea «el tile se abre», no un fundido de página. Sin animación con `prefers-reduced-motion`.

**1.2 Lista de actividades**
- [ ] Rediseñar la tarjeta de actividad dentro de `.ct-root` solo con CSS sobre el marcado del core cuando sea posible. Si hace falta cambiar marcado, hacerlo sobrescribiendo las clases de salida (`content\cm`, `content\section\cmitem`), nunca copiando plantillas enteras del core.
- [ ] Objetivo táctil: toda la tarjeta es pulsable, el botón de finalización mide ≥ 44 px, icono del módulo a la izquierda, nombre a dos líneas como máximo, insignia de categoría en la misma fila que el nombre (resolver aquí la limitación actual de `afterlink`).
- [ ] Estado visible por actividad: hecha, pendiente, bloqueada con motivo, con fecha próxima.
- [ ] Etiquetas (`mod_label`) y subsecciones con un tratamiento visual propio, como separadores de contenido y no como tarjetas.

**1.3 Navegación entre secciones**
- [ ] Integrar con la navegación lineal y el pie fijo de 5.3. Añadir solo lo que falte: gesto de deslizar entre secciones con alternativa por botones y teclado, respetando `env(safe-area-inset-*)`.
- [ ] Mapa del curso como hoja inferior (`<dialog>` nativo) en móvil y panel lateral en escritorio, a un toque desde cualquier página del curso. Mantener `map.php` como alternativa sin JS e imprimible.

**1.4 Página de actividad**
- [ ] Mover la línea de contexto a una franja a todo el ancho bajo la cabecera de la actividad (D14). Investigar primero si `core\output\activity_header` o un hook de 5.3 lo permite sin tocar el tema.
- [ ] Botón «Siguiente actividad» coherente con el «Continuar» del hero, sin duplicar el del pie fijo del core.

**1.5 Detalles de calidad percibida**
- [ ] Opción de curso para ocultar el título del hero cuando el tema ya muestra el H1 (D13), activada por defecto.
- [ ] Estados vacíos diseñados: curso sin secciones, sección sin actividades, curso sin seguimiento de finalización.
- [ ] Sustituir la heurística de «nueva» por «contenido añadido desde tu última visita al curso» usando `user_lastaccess` (D18).

Criterios de aceptación:
- Capturas a 360, 768 y 1280 px de portada, sección y actividad en los seis temas, en `docs/screenshots/`.
- axe-core sin violaciones **propias** en las tres páginas. Hoy hay una violación del core en `course/section.php` (`label-content-name-mismatch` en «Marcar como hecho»): documentarla, no taparla.
- El contenido ocupa ≥ 80 % del alto útil en la primera pantalla de una sección a 360×800. Medirlo con Playwright y guardar la medición.
- Behat: abrir una sección desde un tile, completar una actividad, volver y ver el progreso actualizado.

Riesgo principal: acoplarse a marcado interno del core que cambie en 5.4. Mitigación: selectores sobre atributos `data-*` documentados y un test que falle si desaparecen.

---

### Fase 2 — Editor visual del docente (L, Opus)

Objetivo: que personalizar un curso sea directo y visual (D2). Hoy hay que abrir un formulario por sección.

- [ ] **Edición en el propio tile.** En modo edición, mostrar la rejilla del layout elegido (hoy se muestra la lista estándar) con controles por tile: cambiar portada, icono, color, subtítulo y tiempo. Implementarlo como componente reactivo del core (`core_courseformat` state + mutations) para que conviva con arrastrar y soltar. Mantener la lista estándar accesible con un conmutador «Vista de estructura / Vista de diseño».
- [ ] **Recortador de imagen con punto focal.** En lugar de recortar píxeles, guardar un punto focal (x, y en %) por sección y aplicarlo con `object-position`. Es más simple, no destruye el original y sirve para todos los layouts. Interfaz: arrastrar un punto sobre la imagen, con campos numéricos como alternativa de teclado.
- [ ] **Selector de color con contraste en vivo.** `<input type="color">` más campo hexadecimal, mostrando la relación de contraste y el color corregido antes de guardar. Reutilizar `local\color` exponiéndolo por un servicio web o portando la fórmula a JS con test que compare ambos.
- [ ] **Selector de layout y tema con miniaturas** en los ajustes del curso, en lugar de dos desplegables.
- [ ] **Aplicar estilo a varias secciones** (copiar el estilo de una sección a otras) y **paleta automática por curso**: generar colores distintos y accesibles para todas las secciones a partir del color base.
- [ ] **Plantillas de curso.** «Empezar desde plantilla»: curso SEPE por módulos y UF, curso corto de autoformación, curso con itinerario. Una plantilla fija layout, tema, categorías y estructura de secciones vacía. No incluye contenidos.
- [ ] Banco de iconos ampliado con buscador, limitado a los iconos que el core ya distribuye.

Criterios de aceptación:
- Un docente configura portada, color e icono de 6 secciones sin salir de la página del curso. Test Behat con JS que lo recorra.
- Todo lo que se puede hacer con ratón se puede hacer con teclado.
- Sin JS, el formulario clásico de sección sigue funcionando.

---

### Fase 3 — Conformidad SEPE y seguimiento (L)

**Advertencia para quien aplique esta fase.** Este plan se escribió sin el texto de la Orden TMS/369/2019 delante. Los requisitos que aparecen en `CLAUDE.md` (80 % de contenido, reanudar donde se dejó, barra de progreso por módulo/UF, mapa de navegación, información de contexto, 10 pt mínimo) son la interpretación del propietario del proyecto. La fase empieza por contrastarlos.

#### 3a. Matriz de conformidad (M, Opus)

- [ ] **[DECISIÓN]** Pedir al propietario el texto vigente del Anexo IV y cualquier guía o lista de verificación del SEPE/SCE que use en inspecciones. Sin esos documentos, esta fase no puede cerrarse.
- [ ] Crear `docs/conformidad_sepe.md` con una fila por requisito: apartado y cita literal, si aplica al formato de curso o a otro componente (plataforma, contenidos, tutoría), funcionalidad que lo cubre, prueba automática que lo demuestra, estado (cubierto / parcial / fuera de alcance).
- [ ] Separar con claridad lo que es responsabilidad de este plugin de lo que corresponde a `local_customreport`, al tema o a la configuración del sitio. Un formato de curso no puede «cumplir el Anexo IV» por sí solo, y la documentación no debe sugerirlo.

#### 3b. Implementación (L, Sonnet tras fijar la matriz)

Tareas que se prevén. Confirmarlas o descartarlas con la matriz.

- [ ] **Jerarquía módulo → UF con progreso agregado** (D4). Usar las subsecciones delegadas del core (`mod_subsection`) como unidades formativas dentro de una sección-módulo. Calcular y mostrar progreso por UF y por módulo. Extender `course_data` y su caché, con test de que no se introducen consultas N+1.
- [ ] Duración por módulo como suma de sus UF, y total del curso frente a las horas de la especialidad (campo de curso «horas totales»).
- [ ] **Informe de seguimiento para el tutor** (D5). **[DECISIÓN]** Si vive aquí o en `local_customreport`. Recomendación: este plugin expone datos y `local_customreport` informa. En ese caso:
  - [ ] Servicios web `format_customtiles_get_course_structure` y `format_customtiles_get_user_progress`, con capacidad propia y tests.
  - [ ] Documento de contrato `docs/api.md` con versión, para que `local_customreport` no dependa de clases internas.
  - [ ] Eventos propios si hacen falta (por ejemplo, «sección completada»).
- [ ] Vista «Mi progreso» para el alumno: resumen por módulo y UF, actividades evaluables pendientes agrupadas por categoría (AF, AE, PP, PO), imprimible.
- [ ] Bloque de información del curso en el hero, opcional: tutor o tutores con forma de contacto, enlace a la guía didáctica, calendario de sesiones. Configurable por el docente.
- [ ] Declaración de accesibilidad del formato (`docs/accesibilidad.md`): qué se ha comprobado, cómo y qué queda fuera.
- [ ] Preset de categorías: revisar las siglas con el propietario. El preset actual trae AF, AE, PP y PO. Las skills internas del propietario mencionan también AU, VC/SA, FD, MC, GF y EC. **[DECISIÓN]** cuáles incluir.

Criterio de aceptación: cada fila «cubierto» de la matriz enlaza a un test que pasa. Lo que no se pueda probar automáticamente lleva el procedimiento manual y la fecha de la última comprobación.

---

### Fase 4 — Seguridad y privacidad (M)

Partir de `SECURITY.md`. La revisión anterior fue lectura de código y pruebas dirigidas, no un pentest.

#### 4a. Modelo de amenazas (S, Opus)

- [ ] `docs/modelo_amenazas.md`: actores (anónimo, alumno, docente, gestor, administrador, autor de un backup malicioso), activos (datos de progreso, imágenes, configuración), superficies (páginas propias, `pluginfile`, servicios web, restauración, ajustes de administración) y controles.
- [ ] Revisar con ese modelo las piezas nuevas de las fases 1 a 3, sobre todo los servicios web y el editor reactivo.

#### 4b. Endurecimiento (M, Sonnet)

- [ ] **Capacidades propias** (D6): `format/customtiles:managecategories` (sitio), `:assigncategories` (curso), `:viewprogress` (curso). Definirlas en `db/access.php` con arquetipos sensatos y usarlas en páginas, navegación y servicios web. `api::set_activity_category()` debe comprobar permisos o documentarse como interna y moverse a un espacio de nombres `local`.
- [ ] **Compatibilidad con CSP estricta** (D7). Sustituir el atributo `style` con los tokens por una hoja generada por curso y servida por un script propio con caché (patrón `styles.php?courseid=…&rev=…`), o por clases por tema para los presets y hoja generada solo para el color base. Los estilos por sección (`--ct-c1`, `--ct-pct`) necesitan la misma solución. Test que renderice la portada y falle si queda algún `style="` propio.
- [ ] **Imágenes** (D8): rechazar imágenes por encima de un máximo de píxeles antes de pasarlas a GD, reencodar siempre (elimina EXIF y cargas incrustadas), limitar el tamaño de subida con un ajuste propio, y decidir si se admite SVG en portadas (recomendación: no).
- [ ] **Logotipos públicos**: confirmar con el propietario que deben servirse sin login. Si no, exigir login y mostrarlos solo dentro del curso.
- [ ] **Restauración**: test con un backup manipulado (opciones con HTML, colores inválidos, nombres de archivo con rutas) que demuestre que nada llega sin sanear a la salida.
- [ ] **Privacidad**: revisar el proveedor con los datos nuevos de las fases 1 a 3 (por ejemplo, última visita, punto de reanudación). Añadir borrado explícito de las preferencias `format_customtiles_last_*` al eliminar un curso.
- [ ] Tests de permisos en Behat para cada página propia con cada rol.
- [ ] Política de divulgación en `SECURITY.md`: cómo reportar, plazos y versiones con soporte.

Criterio de aceptación: cada control del modelo de amenazas tiene un test o un procedimiento manual fechado.

---

### Fase 5 — Calidad, rendimiento y compatibilidad (M, Sonnet)

- [ ] **Tests de JS** (D3): Jest para `carousel.js`, `sectionpreview.js` y los módulos nuevos, con el arnés de Jest del core (`jest.config.js`). Escenarios Behat con JS ejecutándose en la CI.
- [ ] **Matriz de CI** (D15): PHP 8.3 y 8.4 con PostgreSQL 17, MariaDB 11.4 y MySQL 8.4. Añadir la rama de desarrollo de Moodle como trabajo permitido-fallar, para ver venir las roturas.
- [ ] **Presupuesto de rendimiento** (D9):
  - [ ] Generador de un curso grande (60 secciones, 600 actividades, portadas reales de 1 a 2 MB) como script de test.
  - [ ] Test PHPUnit que cuente consultas al pintar la portada y falle si crece con el número de secciones.
  - [ ] Lighthouse en la CI con perfil de móvil de gama media y red 4G lenta. Objetivos: LCP < 2,5 s, CLS < 0,05, sin bloqueos largos. Las cifras actuales (LCP ≈ 0,2 s) son de localhost y no sirven como referencia.
  - [ ] Primera fila de tiles sin `loading="lazy"` y con `fetchpriority` adecuado; el resto, perezoso.
  - [ ] Peso del CSS: revisar los 1.200 renglones de `styles.css` y cargar los estilos de cada layout solo cuando se usa, si la medición lo justifica.
- [ ] **WebP** (D19): generar variante WebP cuando GD lo soporte, con `<picture>` y el original como respaldo.
- [ ] **Compatibilidad visual**: RTL (usar propiedades lógicas ya presentes y probar con un idioma RTL), tema Classic, un tema hijo de Boost con colores propios y el modo oscuro experimental de 5.3. Decidir y documentar cómo se combinan el modo oscuro del sitio y el tema «Oscuro» del curso.
- [ ] **Accesibilidad más allá de axe**: recorrido manual con teclado y con lector de pantalla (NVDA o VoiceOver) de portada, sección, actividad y editor. Documentar el resultado. axe detecta solo una parte de los problemas reales.
- [ ] Zoom al 200 % y reflujo a 320 px (WCAG 1.4.4 y 1.4.10) comprobados con Playwright.

---

### Fase 6 — App móvil oficial (M, Opus)

Objetivo (D10): que el curso no se degrade al formato por defecto dentro de la app.

- [ ] `db/mobile.php` y `classes/output/mobile.php` con el layout Tiles, la cabecera con progreso y el botón «Continuar».
- [ ] Plantillas en la sintaxis de la app (Ionic/Angular), con los mismos tokens de color.
- [ ] Probar con la app en modo desarrollo contra el sitio local. Si no es posible en el entorno de la sesión, dejarlo anotado y pedir la prueba al propietario: **no declarar soporte sin haberlo visto funcionar**.

---

### Fase 7 — Mantenimiento y publicación (S, Sonnet, y luego recurrente)

- [ ] Versionado semántico y política de ramas: `main` estable, una rama por versión mayor de Moodle cuando haga falta (`MOODLE_503_STABLE`, …).
- [ ] **Vigilancia del core.** Trabajo de CI semanal contra la rama de desarrollo de Moodle, y una lista de comprobación por cada versión nueva: leer los `UPGRADING.md` de `course/format`, `theme/boost` y `lib`, ejecutar la suite completa, regenerar capturas y comparar.
- [ ] Plantillas de incidencias y de pull request, y `CODE_OF_CONDUCT.md`.
- [ ] Documentación de uso para docentes y administradores, en español e inglés, con capturas: `docs/guia_docente.md`, `docs/guia_admin.md`.
- [ ] Preparar el envío al directorio de plugins de Moodle: capturas, descripción, política de soporte y revisión previa con la lista de comprobación oficial. Traducciones por AMOS una vez publicado.
- [ ] Lista de comprobación de release: tests, capturas, `CHANGES.md`, versión, etiqueta de git, zip generado con `git archive` e instalación limpia y actualización desde la versión anterior.

---

## 4. Independencia respecto a `format_tiles`

`format_tiles` es la referencia conocida, pero este plugin no deriva de él. Para que siga siendo así:

- No copiar código, plantillas, cadenas ni CSS de `format_tiles`. Se pueden estudiar sus ideas; la implementación es propia y sobre las API de 5.x.
- No depender de él ni asumir que está instalado. Si en el futuro se quiere **migrar cursos** desde `format_tiles`, hacerlo como herramienta aparte que lea sus opciones de curso y las traduzca, con su propio test.
- Diferencias de planteamiento que conviene mantener y explicar en el README:

| | `format_customtiles` |
|---|---|
| Base técnica | Clases de salida y estado reactivo de `core_courseformat` 5.x, sin jQuery |
| Vista de sección | Página de sección del core, estilizada, no un modal propio |
| Composición | Varios layouts sobre los mismos datos, con opción distinta en móvil |
| Aspecto | Tokens de diseño y temas con contraste verificado por test |
| Enfoque | Requisitos de usabilidad de formación para el empleo, con matriz de conformidad |

No se incluye una comparación punto por punto con `format_tiles` porque no se ha auditado su versión actual. Si se quiere publicar una, hay que hacerla con el plugin instalado al lado.

---

## 5. Orden recomendado y dependencias

```
Fase 0 ──► Fase 1 ──► Fase 2
              │
              ├──► Fase 3a ──► Fase 3b
              │
              └──► Fase 4a ──► Fase 4b ──► Fase 5 ──► Fase 7
                                              │
                                              └──► Fase 6
```

- La fase 0 va primero porque activa la CI real, y todo lo demás se apoya en ella.
- La fase 1 va antes que la 2: no tiene sentido construir un editor sobre una vista que va a cambiar.
- La fase 3a puede hacerse en paralelo con la 1, porque es documental. La 3b espera a la 1 (toca `course_data` y la vista de sección).
- La 4b conviene después de la 3b, para endurecer también los servicios web nuevos. Si la publicación corre prisa, adelantar de la 4b las capacidades propias y las imágenes.
- Si el presupuesto obliga a recortar, el orden de valor es: **0, 1, 3a, 4b (capacidades e imágenes), 3b, 2, 5, 7, 6**.

---

## 6. Decisiones que necesita tomar el propietario

| # | Decisión | Opciones | Recomendación |
|---|---|---|---|
| 1 | Nombre para el copyright | Nombre legal / organización | Nombre legal o entidad titular |
| 2 | Prefijo CSS | Mantener `ct-` / renombrar a `fct-` | Renombrar en la fase 0, antes de publicar |
| 3 | Documentos normativos | Aportar Anexo IV y listas de verificación del SCE | Imprescindible para la fase 3 |
| 4 | Dónde vive el seguimiento del tutor | En este plugin / en `local_customreport` | En `local_customreport`, con este plugin como fuente de datos |
| 5 | Siglas del preset de categorías | AF, AE, PP, PO / ampliar | Decidir con la guía de actividades real |
| 6 | Logotipos institucionales | Públicos / solo con login | Solo con login, salvo que deban verse en páginas públicas |
| 7 | SVG en portadas | Admitir / no admitir | No admitir |
| 8 | Versiones de Moodle con soporte | Solo 5.3 LTS / 5.3 y siguientes | 5.3 LTS como base, siguiente estable cuando salga |
| 9 | App móvil | Dentro del alcance / fuera | Dentro, pero después de publicar la versión web |

---

## 7. Cómo sabremos que ha mejorado

| Indicador | Hoy | Objetivo |
|---|---|---|
| Páginas del alumno con diseño propio | Portada | Portada, sección y actividad |
| Violaciones axe propias (portada, sección, actividad) | 0 en portada; sección y actividad sin auditar con diseño propio | 0 en las tres |
| Escenarios Behat con JS ejecutados en CI | 0 | Todos |
| LCP en móvil de gama media, red 4G lenta, curso grande | Sin medir | < 2,5 s |
| Consultas al pintar la portada | Sin test | Constantes respecto al número de secciones |
| Requisitos del Anexo IV con prueba enlazada | Sin matriz | 100 % de los que apliquen al formato |
| Pasos para personalizar 6 secciones | 6 formularios | Sin salir de la página del curso |
| Capacidades propias | 0 | 3 |
| Estilos en línea propios en la salida | Sí | Ninguno |
| Versiones de PHP y bases de datos en CI | 1 × 1 | 2 × 3 |

---

## 8. Texto para arrancar cada sesión

Copiar y adaptar el número de fase:

> Lee `CLAUDE.md`, `STATUS.md`, `SECURITY.md` y `plan_mejora_fable.md`. Aplica la **Fase N** de `plan_mejora_fable.md` y solo esa. Antes de escribir código, dime qué tareas de la fase vas a hacer, cuáles necesitan una decisión mía (sección 6) y qué no vas a poder verificar en este entorno. Trabaja en la rama indicada, con commits pequeños, y al terminar actualiza `STATUS.md` y marca en el plan las casillas hechas.

Notas de entorno para esas sesiones (aprendidas en la sesión que escribió este plan):

- Moodle 5.3 se clona fuera del repo y el plugin se enlaza en `public/course/format/customtiles`. Si se enlaza con symlink, ESLint y stylelint del core no aplican su configuración: para `grunt` hay que usar una copia real dentro del árbol de Moodle.
- `npm ci` del core puede fallar por dos paquetes que se descargan de GitHub (`shifter`, `istanbul`). Quitarlos del `package.json` y del `npm-shrinkwrap.json` de la copia de trabajo no afecta a `grunt amd` ni a stylelint.
- Behat sin JS funciona con el servidor embebido de PHP. Con JS necesita un `chromedriver` de la misma versión mayor que Chrome.
- Ejecutar solo los tests del plugin: `vendor/bin/phpunit --testsuite format_customtiles_testsuite` y `vendor/bin/behat --tags @format_customtiles`.
