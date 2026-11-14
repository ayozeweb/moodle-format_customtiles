# Contributing

Thanks for helping. This is an independent course format for Moodle 5.3+; it does not contain code from other formats and contributions must not copy any.

## Set-up

1. Clone Moodle (`MOODLE_503_STABLE`) and place this repository in `public/course/format/customtiles` (a real directory, not a
   symlink: Moodle's ESLint and stylelint configuration only applies inside the Moodle tree).
2. `npm install` in the plugin (Sass) and `npm ci` in Moodle (grunt, stylelint).

## Before sending a pull request

| Check | Command (from the Moodle root unless noted) |
|---|---|
| Coding style | `phpcs --standard=moodle public/course/format/customtiles` |
| Unit tests | `vendor/bin/phpunit --testsuite format_customtiles_testsuite` |
| Behat | `vendor/bin/behat --config <behat.yml> --tags @format_customtiles` |
| JavaScript build and lint | `npx grunt amd --root=public/course/format/customtiles` |
| CSS | `./build-css.sh` in the plugin (compiles `scss/` and formats `styles.css`) |
| Templates | `moodle-plugin-ci mustache` |

Rules of the project:

- Mobile first: check every screen at 360 px, no horizontal scroll, touch targets of at least 44 px.
- WCAG 2.1 AA; colour is never the only carrier of information; respect `prefers-reduced-motion`.
- Every visible string goes in `lang/en` and `lang/es`; singular and plural are separate strings.
- No CDN and no new jQuery. Third-party assets are listed in `thirdpartylibs.xml`.
- State-changing requests use POST and `sesskey`.
- Add or update tests with every change, and an entry in `CHANGES.md`.

Security issues: see `SECURITY.md`; please do not open a public issue for them.
