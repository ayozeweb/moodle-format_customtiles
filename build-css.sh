#!/bin/sh
# Compile scss/ into styles.css (Moodle plugins ship plain CSS) and format it with Moodle's own stylelint
# configuration (4-space indentation, at-rule spacing). Needs `npm install` once in this directory and, for the
# formatting step, a Moodle checkout with its node_modules: set MOODLE_DIR (defaults to ../../../.. when the
# plugin lives in public/course/format/customtiles).
set -e
cd "$(dirname "$0")"
npx sass --no-source-map --style=expanded scss/styles.scss styles.css
MOODLE_DIR="${MOODLE_DIR:-$(cd ../../../.. 2>/dev/null && pwd)}"
if [ -x "$MOODLE_DIR/node_modules/.bin/stylelint" ]; then
  PLUGIN_REL=$(realpath --relative-to="$MOODLE_DIR" "$PWD" 2>/dev/null || echo "public/course/format/customtiles")
  (cd "$MOODLE_DIR" && npx stylelint --fix "$PLUGIN_REL/styles.css") || true
else
  echo "Moodle's stylelint not found (set MOODLE_DIR): styles.css left unformatted." >&2
fi
