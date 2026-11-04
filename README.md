# Custom tiles (`format_customtiles`)

**English** · [Español](#español)

A Moodle 5.3 course format that makes courses beautiful, visual and *mobile-first*.
One course can be shown with different layouts (tiles, cards, path, carousel…) and visual
themes (palettes, shapes), while meeting the usability and accessibility requirements checked by
Spanish public employment services (SEPE/SCE, Orden TMS/369/2019, Annex IV) without looking like an "official template".

* Mobile-first (360 px up), touch targets ≥ 44 px, container queries, `prefers-reduced-motion`.
* Design tokens as CSS variables (`--ct-*`) inheriting from Boost when available.
* WCAG 2.1 AA: every built-in skin is contrast-checked by PHPUnit; custom accent colours are auto-corrected.
* Works with reactive editing, the course index and Boost child themes.
* No CDN: everything ships inside the plugin.

Requirements: Moodle 5.3 (MOODLE_503_STABLE), PHP 8.3/8.4.

## Install

Copy this repository to `public/course/format/customtiles` and run the upgrade.
To rebuild the CSS: `npm install && npm run build:css` (the compiled `styles.css` is committed).

Status and roadmap: see [STATUS.md](STATUS.md). License: GPL v3 or later.

---

## Español

Formato de curso para Moodle 5.3 que hace los cursos bonitos, visuales y pensados primero para móvil y tablet.
Un mismo curso puede mostrarse con distintos *layouts* (tiles, tarjetas, itinerario, carrusel…) y temas visuales,
y cumple de serie los requisitos de usabilidad y accesibilidad de los servicios públicos de empleo (SEPE/SCE,
Orden TMS/369/2019, Anexo IV) sin parecer una «plantilla oficial».

* Mobile-first (desde 360 px), objetivos táctiles ≥ 44 px, *container queries*, respeto de `prefers-reduced-motion`.
* Tokens de diseño como variables CSS (`--ct-*`) que heredan de Boost cuando existen.
* WCAG 2.1 AA: cada tema incluido se verifica con PHPUnit; los colores de acento propios se corrigen automáticamente.
* Compatible con edición reactiva, índice del curso y temas hijos de Boost.
* Sin CDN: todo va dentro del plugin.

Requisitos: Moodle 5.3 (MOODLE_503_STABLE), PHP 8.3/8.4.

### Instalación

Copia este repositorio en `public/course/format/customtiles` y ejecuta la actualización.
Para recompilar el CSS: `npm install && npm run build:css` (el `styles.css` compilado está versionado).

Estado y hoja de ruta: [STATUS.md](STATUS.md). Licencia: GPL v3 o posterior.
