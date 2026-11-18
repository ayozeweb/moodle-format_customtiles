## What and why / Qué y por qué

<!-- One or two sentences. Link the issue if there is one. -->

## How it was tested / Cómo se ha probado

<!-- Tests added or updated, and what was checked by hand (widths, layouts, skins, edit mode). -->

## Checklist

- [ ] PHPUnit and Behat pass; new behaviour has tests.
- [ ] `phpcs` (moodle standard), `moodle-plugin-ci mustache` and `grunt` without warnings.
- [ ] `styles.css` rebuilt with `build-css.sh` and `amd/build` with `grunt amd`, if `scss/` or `amd/src/` changed.
- [ ] Checked at 360, 768 and 1280 px: no horizontal scroll, touch targets of at least 44 px.
- [ ] Keyboard and contrast (WCAG 2.1 AA); colour is not the only carrier of information.
- [ ] Strings in `lang/en` and `lang/es`.
- [ ] Entry in `CHANGES.md`; upgrade step and version bump if the database or caches change.
- [ ] No code copied from other plugins.
