# Changelog

Format based on [Keep a Changelog](https://keepachangelog.com/). Versions follow [Semantic Versioning](https://semver.org/).

## [0.8.0] - 2026-11-22

### Added
- Moodle app support, checked with the official app 5.3 (browser build) on phone and tablet widths, light and dark
  (see `docs/app_movil.md`): course header
  with progress and "Continue", grid of sections with covers, colours, states and progress, and each section through
  the app's own `<core-course-section>`, with previous/next buttons that skip locked sections. Opening a section
  scrolls to its header; "Section x of y" counts as on the course page. Surfaces follow the
  app's light or dark theme; the course skin gives the accent colour.
- "Continue where you left off" also learns from activities viewed in the app or through web services
  (observer of `\core\event\course_module_viewed`).

### Security
- Text written by teachers is escaped for Angular in the app template: braces become the constant expression
  `{{ '{' }}`. HTML entities were checked with Angular's own compiler and do not protect (they are decoded before
  expressions are looked for).

### Changed
- The course header data moved to `\format_customtiles\local\hero`, shared by the course page and the app.

## [0.7.1] - 2026-11-18

### Fixed
- Privacy: the "design view" preference of teachers (0.6.0) is declared, exported through the Privacy API and
  removed on uninstall, as the other preferences already were.

### Added
- Weekly "Core watch" workflow against `MOODLE_503_STABLE` and Moodle `main` (the latter allowed to fail).
- Issue forms, pull request template and code of conduct.
- Teacher and administrator guides in Spanish and English (`docs/`), and the release and maintenance process
  (`docs/publicacion.md`) with `release.sh`, which checks the version numbers and builds the zip with `git archive`.

## [0.7.0] - 2026-11-18

### Added
- WebP copies (quality 75) of the responsive cover variants when GD supports WebP, served with `<picture>` and the
  original format as fallback; an ad hoc task queued on upgrade converts the covers saved before.
- The first row of the grid is not lazy-loaded; the section header cover (the LCP of the section page) has
  `fetchpriority="high"`.
- The default "Light" course skin follows the experimental dark colour mode of Boost 5.3.
- Right-to-left languages: arrows and chevrons point the way the reading goes.
- Query budget test (the reads to paint the course and a section do not grow with its size) and a development CLI
  to generate a large course.
- Colour maths of the live preview in a shared module, tested with `node:test` and PHPUnit against the same table.
- CI: PHPUnit on PHP 8.4 with MariaDB 11.4 and MySQL 8.4.
- `docs/rendimiento.md`: measurements, decisions and compatibility checks (reflow at 320 px, 200 % zoom, keyboard,
  RTL, dark mode).

## [0.6.0] - 2026-11-17

### Added
- Design view for teachers in edit mode: a "Structure / Design" switch above the course page. The design view
  shows every section (and unit) as a tile with an "Edit appearance" button; the core structure view keeps
  moving, adding and deleting sections and activities.
- Appearance form in a modal: cover image, focal point (click on the preview, arrow keys, or numbers), icon,
  colours with a native colour picker and the live text contrast (WCAG AA), subtitle and time, and "also apply
  colours and icon to" other sections or units. Saving refreshes only that tile, without reloading the page.
- Automatic colours: distinct colours for every section (and unit) from a base colour, each one readable with
  white text (4.5:1), for sections without a colour or for all of them.
- Focal point of cover images, applied with `object-position` in every layout.

### Fixed
- The course index stayed empty in the design view; JavaScript errors from core dropdowns that were exported but
  not printed.

## [0.5.0] - 2026-11-17

### Added
- Units: sub-sections (`mod_subsection`) are treated as units of the section that contains them. A section
  shows the progress, activity count and estimated time of its own activities plus its units; each unit has
  its own state, progress and page, a hierarchical number ("5.2") and "Unit x of y" in the context bar.
- Units are listed on cards, path and carousel layouts, on the section header and nested in the course map;
  small tiles and the compact list show how many there are.
- Navigation from a unit: back to its section, on to the next unit, and after the last one to the next section.

### Fixed
- The progress of a section ignored the activities inside its sub-sections.
- "Continue where you left off" placed the activities of sub-sections at the end of the course instead of
  where they appear on the page.
- Units shown inside a section were painted as white boxes on dark themes.

## [0.4.0] - 2026-11-17

### Security
- Own capabilities `format/customtiles:managecategories` and `format/customtiles:assigncategories`; the API
  enforces them and checks that an activity belongs to the course.
- Section covers are validated (raster formats only, 5 MB, 40 megapixels) and always re-encoded; the original
  upload is no longer kept. SVG covers are not accepted.
- Category assignments and per-user preferences are removed when an activity or a course is deleted.

### Added
- Threat model (`docs/modelo_amenazas.md`) and vulnerability reporting policy.

## [0.3.1] - 2026-11-16

### Added
- Deadline chip on activities with a close date: due today, tomorrow, in N days (up to seven) or overdue.
- Course option to keep the breadcrumb on phones (by default the context bar replaces it).

### Changed
- Copyright holder set to Ayoze M de Paz Paz.

## [0.3.0] - 2026-11-16

### Added
- Section page with its own header (cover or colour, subtitle, time, progress and state), themed activity cards,
  previous/next section links and swipe navigation on touch screens.
- Course map as a panel (bottom sheet on phones, side panel on wider screens), loaded on demand; the printable
  page remains the fallback without JavaScript.
- One-line context bar with a "back" link on section and activity pages; on phones it replaces the breadcrumb.
- Shared view transition between a section cover on the course page and on the section page.
- Empty states for courses without sections and sections without activities.
- Course option to repeat the course name in the header (off by default: the theme already shows it).

### Changed
- "New" now means content added since the user's previous visit to the course (stored as a user preference,
  exported by the Privacy API), not content created in the last seven days.
- The category badge sits on the title row of the activity.
- Carousel controls show the destination at once instead of waiting for the smooth scroll to finish.

### Fixed
- The course map did not carry the visual theme of the course.

## [0.2.0] - 2026-11-14

### Changed
- CSS class, custom property and data attribute prefix renamed from `ct-` to `fct-` to avoid collisions with other plugins.
  Themes that overrode `.ct-*` rules or `--ct-*` variables must be updated. Section option names (`ct_*`) are unchanged.
- Activity category assignments are kept in a request cache instead of a static property.
- Deleting a category now asks for confirmation and is done by POST; loading the SEPE preset is POST only.
- Administration and assignment pages are rendered from Mustache templates.
- `amd/build` is produced by Moodle's own `grunt amd`; `styles.css` is formatted with Moodle's stylelint configuration.

### Added
- Singular/plural strings for activity and section counts.
- Behat features (course page, section options, categories, carousel).
- `db/uninstall.php`: removes the plugin files and the "last visited activity" preferences.
- `$plugin->supported`, `.gitattributes`, `.editorconfig`, `CONTRIBUTING.md`, this changelog.
- CI: `grunt`, Behat and a check that `styles.css` matches `scss/`.

### Fixed
- Category add/edit form did not save.
- Covers of hidden or restricted sections were served to enrolled users who knew the section id.
- `pathLength` attribute rejected by the HTML validator (Mustache lint).
- "1 activities" / "1 sections".

### Security
- `nosniff` and a restrictive CSP on files served by the plugin; category input sanitised in the API. See `SECURITY.md`.

## [0.1.0] - 2026-11-05

### Added
- Course format with hero header, progress (MUC cached) and "Continue where you left off".
- Layouts: tiles, large cards, learning path, carousel, bento and compact list; optional different layout on mobile.
- Visual themes: light, dark, corporate, vibrant, minimal, high contrast, plus administrator-defined themes;
  accessible palette generator.
- Per-section cover image, icon, colour, subtitle and estimated time.
- Activity categories with the SEPE preset, badges, legend and public API.
- Course map, context line, skip link, print stylesheet, institutional logo strip.
- Backup and restore, Privacy API.
