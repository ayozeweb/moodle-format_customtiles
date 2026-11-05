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
