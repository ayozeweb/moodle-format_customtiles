# Custom tiles (`format_customtiles`)

**English** · [Español](#español)

A Moodle 5.3 course format that makes courses beautiful, visual and *mobile-first*.
One course can be shown with different layouts (tiles, cards, path, carousel…) and visual
themes (palettes, shapes), while meeting the usability and accessibility requirements checked by
Spanish public employment services (SEPE/SCE, Orden TMS/369/2019, Annex IV) without looking like an "official template".

* Mobile-first (360 px up), touch targets ≥ 44 px, container queries, `prefers-reduced-motion`.
* Design tokens as CSS variables (`--fct-*`) inheriting from Boost when available.
* WCAG 2.1 AA: every built-in skin is contrast-checked by PHPUnit; custom accent colours are auto-corrected.
* Works with reactive editing, the course index and Boost child themes.
* No CDN: everything ships inside the plugin.

Requirements: Moodle 5.3 (MOODLE_503_STABLE), PHP 8.3/8.4.

## Install

Copy this repository to `public/course/format/customtiles` and run the upgrade.
To rebuild the CSS: `npm install && npm run build:css` (the compiled `styles.css` is committed).

## Features

* **Hero**: cover, summary, context pills (sections, activities, duration, dates), overall progress ring and a *Continue where you left off* button (last visited activity, or the next pending one).
* **Layouts** (per course, optionally a different one on mobile): Tiles, Large cards, Learning path, Carousel (scroll-snap, buttons, keyboard), Bento and Compact list.
* **Visual themes**: Light, Dark, Corporate, Vibrant, Minimal, High contrast (AAA) + extra themes defined by the administrator; accent colour with automatic WCAG AA correction; local OFL fonts (Inter, Atkinson Hyperlegible).
* **Sections**: cover image (responsive `srcset`), icon, colour/gradient, subtitle, estimated time, with live preview in the section form.
* **Progress and states**: per section and overall (MUC-cached, invalidated by completion events); locked (with reason), hidden, pending, in progress, completed and new — always icon + text + colour.
* **Activity categories**: administrator-defined (SEPE preset: AF, AE, PP, PO), badge on each activity, legend, public API `\format_customtiles\api`.
* **Orientation / accessibility**: course map, context line (“Module · Activity 4 of 9”), skip link, print stylesheet, optional institutional logo strip (you upload the logos; none included), `prefers-reduced-motion`, touch targets ≥ 44 px.
* Backup/restore of options, covers and categories; Privacy API (last visited activity as user preference).

### Public API

`\format_customtiles\api::get_categories()`, `get_course_assignments($courseid)` (cmid → code, name, colour, icon), `set_activity_category()`, `load_sepe_preset()`.

### Development

```bash
npm install && npm run build:css      # SCSS -> styles.css
php vendor/bin/phpunit --testsuite format_customtiles_testsuite
```

Status and roadmap: see [STATUS.md](STATUS.md). License: GPL v3 or later.

---

## Español

Formato de curso para Moodle 5.3 que hace los cursos bonitos, visuales y pensados primero para móvil y tablet.
Un mismo curso puede mostrarse con distintos *layouts* (tiles, tarjetas, itinerario, carrusel…) y temas visuales,
y cumple de serie los requisitos de usabilidad y accesibilidad de los servicios públicos de empleo (SEPE/SCE,
Orden TMS/369/2019, Anexo IV) sin parecer una «plantilla oficial».

* Mobile-first (desde 360 px), objetivos táctiles ≥ 44 px, *container queries*, respeto de `prefers-reduced-motion`.
* Tokens de diseño como variables CSS (`--fct-*`) que heredan de Boost cuando existen.
* WCAG 2.1 AA: cada tema incluido se verifica con PHPUnit; los colores de acento propios se corrigen automáticamente.
* Compatible con edición reactiva, índice del curso y temas hijos de Boost.
* Sin CDN: todo va dentro del plugin.

Requisitos: Moodle 5.3 (MOODLE_503_STABLE), PHP 8.3/8.4.

### Instalación

Copia este repositorio en `public/course/format/customtiles` y ejecuta la actualización.
Para recompilar el CSS: `npm install && npm run build:css` (el `styles.css` compilado está versionado).

### Funcionalidades

* **Hero**: portada, resumen, píldoras de contexto (secciones, actividades, duración, fechas), anillo de progreso global y botón *Continuar donde lo dejaste* (última actividad visitada o la siguiente pendiente).
* **Layouts** (por curso, con opción distinta en móvil): Tiles, Tarjetas grandes, Itinerario, Carrusel (scroll-snap, botones y teclado), Bento y Lista compacta.
* **Temas visuales**: Claro, Oscuro, Corporativo, Vibrante, Minimal y Alto contraste (AAA) + temas adicionales del administrador; color de acento con corrección automática a contraste WCAG AA; tipografías locales OFL (Inter, Atkinson Hyperlegible).
* **Secciones**: portada (con `srcset`), icono, color/degradado, subtítulo y tiempo estimado, con vista previa en vivo.
* **Progreso y estados**: por sección y global (caché MUC invalidada por eventos de finalización); bloqueada (con motivo), oculta, pendiente, en curso, completada y nueva — siempre icono + texto + color.
* **Categorías de actividad**: definidas por el administrador (preset SEPE: AF, AE, PP, PO), insignia en cada actividad, leyenda y API pública.
* **Orientación / accesibilidad**: mapa del curso, línea de contexto («Módulo · Actividad 4 de 9»), enlace «Saltar al contenido», hoja de impresión, franja institucional opcional (los logotipos los subes tú; no se incluye ninguno), `prefers-reduced-motion`, objetivos táctiles ≥ 44 px.
* Backup/restauración de opciones, portadas y categorías; Privacy API (última actividad visitada como preferencia de usuario).

Estado y hoja de ruta: [STATUS.md](STATUS.md). Licencia: GPL v3 o posterior.

## Gallery / Galería

Layouts (`docs/screenshots/`):

| | 360 px | 768 px | 1280 px |
|---|---|---|---|
| **Tiles** | <img src="docs/screenshots/layout-tiles-360.png" width="120" alt="Tiles 360px"> | <img src="docs/screenshots/layout-tiles-768.png" width="160" alt="Tiles 768px"> | <img src="docs/screenshots/layout-tiles-1280.png" width="240" alt="Tiles 1280px"> |
| **Cards / Tarjetas** | <img src="docs/screenshots/layout-cards-360.png" width="120" alt="Cards / Tarjetas 360px"> | <img src="docs/screenshots/layout-cards-768.png" width="160" alt="Cards / Tarjetas 768px"> | <img src="docs/screenshots/layout-cards-1280.png" width="240" alt="Cards / Tarjetas 1280px"> |
| **Path / Itinerario** | <img src="docs/screenshots/layout-path-360.png" width="120" alt="Path / Itinerario 360px"> | <img src="docs/screenshots/layout-path-768.png" width="160" alt="Path / Itinerario 768px"> | <img src="docs/screenshots/layout-path-1280.png" width="240" alt="Path / Itinerario 1280px"> |
| **Carousel / Carrusel** | <img src="docs/screenshots/layout-carousel-360.png" width="120" alt="Carousel / Carrusel 360px"> | <img src="docs/screenshots/layout-carousel-768.png" width="160" alt="Carousel / Carrusel 768px"> | <img src="docs/screenshots/layout-carousel-1280.png" width="240" alt="Carousel / Carrusel 1280px"> |
| **Bento** | <img src="docs/screenshots/layout-bento-360.png" width="120" alt="Bento 360px"> | <img src="docs/screenshots/layout-bento-768.png" width="160" alt="Bento 768px"> | <img src="docs/screenshots/layout-bento-1280.png" width="240" alt="Bento 1280px"> |
| **List / Lista** | <img src="docs/screenshots/layout-list-360.png" width="120" alt="List / Lista 360px"> | <img src="docs/screenshots/layout-list-768.png" width="160" alt="List / Lista 768px"> | <img src="docs/screenshots/layout-list-1280.png" width="240" alt="List / Lista 1280px"> |

Visual themes / Temas visuales:

| | 360 px | 768 px | 1280 px |
|---|---|---|---|
| **Light / Claro** | <img src="docs/screenshots/skin-light-360.png" width="120" alt="Light / Claro 360px"> | <img src="docs/screenshots/skin-light-768.png" width="160" alt="Light / Claro 768px"> | <img src="docs/screenshots/skin-light-1280.png" width="240" alt="Light / Claro 1280px"> |
| **Dark / Oscuro** | <img src="docs/screenshots/skin-dark-360.png" width="120" alt="Dark / Oscuro 360px"> | <img src="docs/screenshots/skin-dark-768.png" width="160" alt="Dark / Oscuro 768px"> | <img src="docs/screenshots/skin-dark-1280.png" width="240" alt="Dark / Oscuro 1280px"> |
| **Corporate / Corporativo** | <img src="docs/screenshots/skin-corporate-360.png" width="120" alt="Corporate / Corporativo 360px"> | <img src="docs/screenshots/skin-corporate-768.png" width="160" alt="Corporate / Corporativo 768px"> | <img src="docs/screenshots/skin-corporate-1280.png" width="240" alt="Corporate / Corporativo 1280px"> |
| **Vibrant / Vibrante** | <img src="docs/screenshots/skin-vibrant-360.png" width="120" alt="Vibrant / Vibrante 360px"> | <img src="docs/screenshots/skin-vibrant-768.png" width="160" alt="Vibrant / Vibrante 768px"> | <img src="docs/screenshots/skin-vibrant-1280.png" width="240" alt="Vibrant / Vibrante 1280px"> |
| **Minimal** | <img src="docs/screenshots/skin-minimal-360.png" width="120" alt="Minimal 360px"> | <img src="docs/screenshots/skin-minimal-768.png" width="160" alt="Minimal 768px"> | <img src="docs/screenshots/skin-minimal-1280.png" width="240" alt="Minimal 1280px"> |
| **High contrast / Alto contraste** | <img src="docs/screenshots/skin-highcontrast-360.png" width="120" alt="High contrast / Alto contraste 360px"> | <img src="docs/screenshots/skin-highcontrast-768.png" width="160" alt="High contrast / Alto contraste 768px"> | <img src="docs/screenshots/skin-highcontrast-1280.png" width="240" alt="High contrast / Alto contraste 1280px"> |

