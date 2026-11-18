#!/bin/sh
# This file is part of Moodle - http://moodle.org/
#
# Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
#
# Build the installable zip of a release from a git revision (default: HEAD), after checking that the version
# numbers agree. The zip only holds what .gitattributes does not mark export-ignore, inside a "customtiles"
# folder, as the Moodle plugin installer and the plugins directory expect. See docs/publicacion.md.
#
#   ./release.sh            # zip of HEAD in ./dist/
#   ./release.sh v0.6.0     # zip of an older release (to test upgrades)
set -eu

REV="${1:-HEAD}"
OUT="$(pwd)/dist"
cd "$(dirname "$0")"

show() { git show "$REV:$1"; }
RELEASE=$(show version.php | sed -n "s/^\$plugin->release *= *'\([^']*\)'.*/\1/p")
VERSION=$(show version.php | sed -n 's/^\$plugin->version *= *\([0-9]*\).*/\1/p')
if [ -z "$RELEASE" ] || [ -z "$VERSION" ]; then
  echo "Cannot read version and release from version.php at $REV" >&2
  exit 1
fi

if [ "$REV" = "HEAD" ]; then
  if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "The working tree has uncommitted changes: commit them first." >&2
    exit 1
  fi
  # The newest entry of the changelog is this release.
  if ! show CHANGES.md | grep -m1 '^## \[' | grep -q "\[$RELEASE\]"; then
    echo "CHANGES.md does not start with an entry for $RELEASE." >&2
    exit 1
  fi
  # The upgrade script reaches this version.
  if ! show db/upgrade.php | grep -q "upgrade_plugin_savepoint(true, $VERSION, 'format', 'customtiles')"; then
    echo "db/upgrade.php has no savepoint for $VERSION." >&2
    exit 1
  fi
  # The compiled JavaScript and CSS are committed (the zip has no build step).
  for f in styles.css amd/build/carousel.min.js; do
    show "$f" > /dev/null
  done
fi

mkdir -p "$OUT"
ZIP="$OUT/format_customtiles_${RELEASE}_${VERSION}.zip"
git archive --format=zip --prefix=customtiles/ -o "$ZIP" "$REV"
echo "$ZIP"
unzip -l "$ZIP" | tail -1
