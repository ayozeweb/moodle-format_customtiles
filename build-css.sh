#!/bin/sh
# Compile scss/ into styles.css (Moodle plugins ship plain CSS). Needs `npm i` once.
cd "$(dirname "$0")" && npx sass --no-source-map --style=expanded scss/styles.scss styles.css
