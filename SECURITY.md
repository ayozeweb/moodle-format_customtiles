# Security review / Revisión de seguridad

Scope: `format_customtiles` 0.1.x on Moodle 5.3. Method: manual code review (all entry points, templates, SQL, file serving, backup/restore) plus dynamic checks against a local instance (anonymous, student, admin; CSRF; path traversal; hidden-section files) and axe-core.

| Area | Result |
|---|---|
| SQL | All queries use the DML API with bound parameters; no string-built SQL. |
| Authentication / authorisation | `map.php`, `assign.php` call `require_login($course)`; `assign.php` requires `moodle/course:manageactivities`; `categories.php` is an admin external page (`moodle/site:config`). Students/anonymous are rejected. Content shown is filtered by `uservisible`. |
| CSRF | State-changing actions (assign, delete, preset) check `sesskey`; forms use moodleform. Verified: delete/preset without sesskey change nothing. |
| XSS | Output is escaped by Mustache. The few `{{{ }}}` outputs come from core sanitisers (`format_string`, `get_formatted_name`, availability info). Colours go through `color::normalise()` before reaching inline `style`; icons are checked against a whitelist; user text uses `PARAM_TEXT`. JS uses `textContent`/`setAttribute`. |
| File serving | `format_customtiles_pluginfile`: login required for covers, section must be visible to the user, file lookup scoped to the course context (no path traversal: DB lookup), `nosniff` + restrictive CSP. SVG is force-downloaded by the core. Logos are public by design (administrator-uploaded, shown on course pages). |
| Backup/restore | Restored values are re-validated at render time (colours, icons, layout, skin). Category assignments are matched by code; unknown codes are ignored. |
| Privacy | One user preference (last visited activity per course); exported through the Privacy API. |

Fixed during this review: category add/edit form did not save (lost its `action` parameter); `map.php`/`assign.php` now ignore courses with another format; category input is sanitised in the API (code, name, icon whitelist, colour); covers of hidden/restricted sections are no longer served; added `nosniff`/CSP headers on served files.

Known / accepted: `\format_customtiles\api::set_activity_category()` does not check capabilities (callers must, as `assign.php` does); the activity category badge shares the core "after link" area; cover resizing uses GD on teacher-uploaded images (bounded by the site upload limit).

## Since 0.4.0

- Own capabilities: `format/customtiles:managecategories` (site) and `format/customtiles:assigncategories` (course). The public API checks them, and that an activity belongs to the course it is addressed through.
- Section covers: only PNG, JPEG, GIF and WebP, up to 5 MB and 40 megapixels; always re-encoded (metadata and embedded payloads are dropped) and the original upload is not kept. SVG is not accepted.
- Clean-up of category assignments and per-user preferences when an activity or a course is deleted.
- A test restores a hand-edited backup and checks nothing reaches the page unescaped.
- Threat model: `docs/modelo_amenazas.md`.

## Reporting a vulnerability

Please do not open a public issue. Use GitHub's private vulnerability reporting on this repository
("Security" tab → "Report a vulnerability"). Include the plugin and Moodle versions and steps to reproduce.
You will get an acknowledgement within 7 days. Only the latest released version receives security fixes.

