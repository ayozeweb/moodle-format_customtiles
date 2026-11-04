CLAUDE.md — format_customtiles

Formato de curso para Moodle que hace los cursos bonitos, visuales y pensados primero para móvil y tablet. Un mismo curso puede mostrarse con distintos layouts (tiles, tarjetas, itinerario, carrusel…) y temas visuales (paletas, tipografía, formas). Además cumple de serie los requisitos de usabilidad y accesibilidad que verifican los servicios públicos de empleo (SEPE/SCE, Orden TMS/369/2019, Anexo IV), sin que eso se note como «plantilla oficial».

Trabajas sin supervisión. Decide con criterio, documenta cada decisión en STATUS.md y sigue avanzando. Commits pequeños y push frecuente.

1. Principios de diseño
Mobile-first de verdad. Se diseña primero a 360 px y luego se amplía. Breakpoints: 360 / 600 / 768 (tablet vertical) / 1024 (tablet horizontal) / 1280+. Usa container queries para que los componentes se adapten al hueco disponible (drawers abiertos o cerrados) y no solo al viewport.
Táctil. Objetivos de toque ≥ 44×44 px, separación suficiente y gestos de deslizar en carruseles y en la navegación entre secciones, siempre con alternativa por botones y teclado. Respeta env(safe-area-inset-*).
Contenido protagonista. El contenido ocupa al menos el 80 % del espacio y la navegación no más del 20 % (requisito del Anexo IV). Nada de cajas dentro de cajas.
Estética cuidada y coherente. Jerarquía tipográfica clara, espacio en blanco generoso, imágenes de portada, iconografía consistente, sombras y radios suaves y microanimaciones discretas. Debe parecer una app moderna, no un Moodle de 2012.
Rendimiento. LCP < 2,5 s y CLS ≈ 0 en un móvil de gama media. Imágenes con srcset, tamaños fijados con aspect-ratio, loading="lazy" fuera de la primera pantalla y WebP/AVIF cuando el servidor pueda generarlos. Sin librerías pesadas.
Accesible sin renunciar a lo bonito. WCAG 2.1 AA: contraste, foco visible y elegante, teclado completo, ARIA correcto, el color nunca es el único portador de información y prefers-reduced-motion anula animaciones.
Convive con el core. Funciona con edición reactiva, índice del curso, temas hijos de Boost y el modo oscuro experimental de 5.3. La navegación lineal y el pie fijo de 5.3 no se duplican: se integran o se respetan.
2. Funcionalidades
2.1 Cabecera «hero» del curso
Imagen de portada (la del curso o una propia), título, descripción breve, progreso global (anillo o barra) y botón «Continuar donde lo dejaste», que lleva a la última actividad visitada o a la siguiente pendiente. El Anexo IV pide poder reanudar en el punto donde se dejó la sesión anterior.
Píldoras de contexto: duración, fechas y número de módulos.
Franja de imagen institucional opcional y configurable (logotipos de financiación SEPE, SCE, FSE+… que sube el administrador; el plugin no incluye logotipos oficiales).
2.2 Layouts de curso (seleccionables por curso)

Todos comparten datos, plantillas base y tokens; solo cambia la composición.

Tiles: rejilla de secciones con portada, icono, color, progreso y estado. Al tocar un tile se abre la sección en vista enfocada con transición suave.
Tarjetas grandes: tarjetas horizontales con imagen, resumen, número de actividades, tiempo estimado y progreso. Pensado para tablet.
Itinerario: ruta vertical paso a paso con nodos conectados, estado de cada paso y desbloqueo visual cuando hay restricciones de acceso. Ideal para cursos secuenciales SEPE (módulo → UF).
Carrusel: secciones a pantalla casi completa que se deslizan horizontalmente con scroll-snap, indicador de posición y botones. Pensado para móvil.
Bento / magazine (si da tiempo): rejilla asimétrica con secciones destacadas.
Lista compacta (si da tiempo): para cursos con muchas secciones.

El docente elige el layout en los ajustes del curso. Opcional: un layout distinto en móvil y en escritorio.

2.3 Temas visuales (skins) basados en tokens
Sistema de design tokens con variables CSS bajo un espacio de nombres propio: colores (primario, acento, superficie, texto, estados), tipografía (familia, escala modular, interlineado), radios, sombras, espaciado y densidad.
Presets incluidos: «Claro», «Oscuro», «Corporativo», «Vibrante», «Minimal» y «Alto contraste». El administrador crea más y el docente elige uno por curso.
Tipografías locales (incluidas en el plugin con licencia OFL o del core de 5.3), nunca desde CDN.
Los tokens heredan de las variables de Boost cuando existen, para no chocar con el tema del sitio.
Generación automática de una paleta accesible a partir de un color base, con comprobación de contraste AA. Si el docente elige un color sin contraste suficiente, se ajusta y se avisa.
2.4 Personalización de secciones
Por sección: imagen de portada (File API), icono (del conjunto disponible en el core), color o degradado, subtítulo y tiempo estimado.
Editor visual en modo edición con vista previa en vivo y selector de imagen con recorte a la proporción del layout.
2.5 Progreso y estados
Progreso por sección (y por módulo/UF) calculado con la finalización de actividades. Si local_customreport está instalado, usa su API de progreso; si no, lo calcula el propio formato. El Anexo IV exige una barra de progreso por módulo/UF.
Estados visibles y diferenciados con icono, texto y color (nunca solo color): bloqueada (restricción de acceso, con el motivo), oculta, pendiente, en curso, completada y nueva.
Pequeña celebración al completar una sección (animación sutil, desactivada con prefers-reduced-motion).
2.6 Categorías de actividad
El administrador define categorías con nombre, código, color e icono. El docente asigna una categoría a cada actividad, que se muestra como insignia dentro de la sección, con leyenda en el curso.
Preset «SEPE» con AF, AE, PP y PO. Se carga, edita o ignora libremente.
Expone las asignaciones con una API pública para que local_customreport pueda importarlas.
2.7 Orientación y contexto (Anexo IV)
Mapa de navegación del curso (vista de estructura completa) a uno o dos toques.
Información de contexto siempre visible: «Módulo 2 · UF3 · Actividad 4 de 9».
Enlace «Saltar al contenido» al principio de la página.
Hoja de estilos de impresión: el contenido y el mapa del curso se imprimen bien.
Texto base de 16 px en móvil y nunca menos del equivalente a 10 pt.
2.8 App móvil de Moodle (último hito, si da tiempo)
Soporte básico en la app oficial mediante classes/output/mobile.php y plantillas móviles, como mínimo con el layout Tiles y el progreso.
3. Entorno técnico
Moodle 5.3 LTS (MOODLE_503_STABLE). PHP 8.3/8.4 de 64 bits, extensión sodium, max_input_vars ≥ 5000. PostgreSQL 17+, MariaDB 11.4+ o MySQL 8.4+.
Componente format_customtiles. La raíz del repo es la raíz del plugin. En un checkout de Moodle va en public/course/format/customtiles (desde 5.1 el webroot es public/).
Clona Moodle fuera del repo (git clone --depth 1 --branch MOODLE_503_STABLE https://github.com/moodle/moodle.git) y enlaza el plugin. Prueba primero moodle-docker; si Docker no funciona en el sandbox, instala PHP y la base de datos directamente. Si una vía falla por red o permisos, cambia de estrategia en lugar de reintentar en bucle.
Antes de usar APIs, lee los UPGRADING.md de 5.3 de public/course/format, public/theme/boost y public/lib (5.3 cambia tarjetas de actividad, navegación lineal y estilos globales, y añade una API para montar componentes React).
Usa el formato topics del core como referencia de estructura. format_tiles puede inspirar ideas, pero no copies su código.
Ejecuta solo los tests de este plugin, nunca la suite completa de Moodle. Explora el core con grep/glob y no leas archivos enormes enteros.
4. Reglas de código
Sobre core_courseformat: clase base, clases de salida, estado reactivo y plantillas Mustache sobrescribibles por el tema.
JavaScript como módulos ESM/AMD del core, sin jQuery nuevo. Si usas la API de React de 5.3, que sea solo donde aporte y con fallback sin JS para el contenido.
SCSS del plugin organizado por tokens → base → componentes → layouts. Sin !important salvo justificación anotada.
Sin librerías desde CDN. Recursos (fuentes, iconos) dentro del plugin y con licencia compatible documentada en thirdpartylibs.xml.
Todo texto visible va en cadenas de idioma: lang/en obligatorio y lang/es completo.
Caché (MUC) del progreso calculado por usuario y curso, invalidada por eventos de finalización. Nada de consultas N+1 al pintar el curso.
Backup y restauración de todas las opciones de curso, sección y categorías. Privacy API (null provider si no se guardan datos personales; provider completo si se guarda, por ejemplo, la última actividad visitada).
Licencia GPL v3 con cabeceras en todos los archivos.
5. Hitos (en este orden)

Al cerrar cada hito: tests en verde, STATUS.md actualizado, commit y push. Si el presupuesto se acaba, lo que quede hecho debe instalarse y verse bien.

Base y sistema visual: version.php (requires tomado del checkout de 5.3, madurez ALPHA, 0.1.0), clase de formato, cadenas en/es, Privacy, README, GitHub Actions con moodle-plugin-ci, tokens de diseño y los presets «Claro» y «Oscuro». Un curso se crea y se ve sin errores, también en edición.
Hero + layout Tiles + progreso: cabecera con «Continuar», rejilla responsive, vista enfocada de sección, estados y progreso por sección. Impecable en 360 px, 768 px y 1280 px.
Layouts Itinerario, Tarjetas y Carrusel, con selector por curso (y opción distinta en móvil).
Personalización: portada, icono, color y tiempo por sección con vista previa en vivo; resto de presets visuales con generador de paleta accesible; categorías de actividad con preset SEPE.
Orientación, accesibilidad y rendimiento: mapa de navegación, contexto, skip link, impresión y franja institucional; revisión WCAG 2.1 AA; medición de rendimiento y ajustes.
Calidad y presentación: PHPUnit (opciones, progreso, categorías, backup/restore), phpcs y Mustache lint sin avisos. Capturas de cada layout y preset a 360, 768 y 1280 px en docs/screenshots/ (con Playwright o Behat contra el Moodle local; si no es posible en el sandbox, anótalo) y README con galería.
Extra si queda margen: layouts Bento y Lista y soporte para la app móvil.
6. Definición de terminado
Se instala y actualiza sin errores ni avisos en 5.3. Funciona con Boost, en modo edición y con el índice del curso.
Ninguna página tiene scroll horizontal a 360 px y todos los objetivos táctiles miden ≥ 44 px.
Contraste AA en todos los presets, comprobado de forma automática.
PHPUnit verde, phpcs sin avisos y Mustache lint correcto.
Capturas en docs/screenshots/ y README en español e inglés.
7. STATUS.md

Mantenlo siempre al día con: hito actual, hecho, decisiones (con su porqué), bloqueos y siguiente paso.
