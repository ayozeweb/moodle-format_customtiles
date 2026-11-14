# Changelog

Format based on [Keep a Changelog](https://keepachangelog.com/). Versions follow [Semantic Versioning](https://semver.org/).

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
