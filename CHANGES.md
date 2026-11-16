# Changelog

Format based on [Keep a Changelog](https://keepachangelog.com/). Versions follow [Semantic Versioning](https://semver.org/).

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
