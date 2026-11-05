<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Per-section appearance: cover image, icon, colours, subtitle and estimated time.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Helpers for the section options.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_style {
    /** @var string File area of the cover images (itemid = section id). */
    public const FILEAREA = 'sectioncover';

    /** @var int[] Widths of the generated cover variants. */
    public const WIDTHS = [480, 960];

    /**
     * Icons available for sections (Font Awesome names shipped with Moodle).
     *
     * @return string[] name => string identifier suffix
     */
    public static function get_icons(): array {
        $names = ['book-open', 'pen-to-square', 'flask', 'code', 'calculator', 'chart-line', 'laptop', 'shield-halved',
            'users', 'comments', 'lightbulb', 'trophy', 'clipboard-check', 'globe', 'graduation-cap', 'gear',
            'wrench', 'heart-pulse', 'language', 'puzzle-piece'];
        return array_combine($names, $names);
    }

    /**
     * Select options for the icon: '' (none) plus the available icons, labelled with their name.
     *
     * @return string[]
     */
    public static function get_icon_options(): array {
        $options = ['' => get_string('iconnone', 'format_customtiles')];
        foreach (self::get_icons() as $name) {
            $options[$name] = get_string('icon_' . $name, 'format_customtiles');
        }
        return $options;
    }

    /**
     * Computed look of a section from its stored options.
     *
     * @param \section_info $section
     * @param int $courseid
     * @return array template fields
     */
    public static function export(\section_info $section, int $courseid): array {
        $c1 = color::normalise((string)($section->ct_color ?? ''));
        $c2 = color::normalise((string)($section->ct_color2 ?? ''));
        $minutes = max(0, (int)($section->ct_minutes ?? 0));
        $icon = (string)($section->ct_icon ?? '');
        $out = [
            'subtitle' => trim((string)($section->ct_subtitle ?? '')),
            'minutes' => $minutes,
            'timelabel' => $minutes ? self::format_minutes($minutes) : '',
            'icon' => isset(self::get_icons()[$icon]) ? $icon : '',
            'hasicon' => isset(self::get_icons()[$icon]),
            'hascolor' => $c1 !== null,
            'coverstyle' => '',
            'hascover' => false,
        ];
        if ($c1 !== null) {
            $c2 ??= color::mix($c1, '#000000', 0.35);
            $mid = color::mix($c1, $c2, 0.5);
            $out['coverstyle'] = "--ct-c1:$c1;--ct-c2:$c2;--ct-fg:" . color::readable_on($mid);
        }
        $cover = (string)($section->ct_cover ?? '');
        if ($cover !== '') {
            $ctx = \context_course::instance($courseid);
            $out += self::cover_urls($ctx->id, (int)$section->id, $cover);
            $out['hascover'] = true;
        }
        return $out;
    }

    /**
     * URLs and srcset of a cover image.
     *
     * @param int $contextid
     * @param int $sectionid
     * @param string $filename
     * @return array
     */
    public static function cover_urls(int $contextid, int $sectionid, string $filename): array {
        $base = fn(string $path) => \moodle_url::make_pluginfile_url(
            $contextid,
            'format_customtiles',
            self::FILEAREA,
            $sectionid,
            $path,
            $filename
        )->out(false);
        $srcset = [];
        foreach (self::WIDTHS as $w) {
            $srcset[] = $base('/w' . $w . '/') . ' ' . $w . 'w';
        }
        return ['coversrc' => $base('/w480/'), 'coversrcset' => implode(', ', $srcset)];
    }

    /**
     * Save the cover from a draft area, generate the responsive variants and return the file name.
     *
     * @param int $draftid
     * @param int $courseid
     * @param int $sectionid
     * @return string file name, '' when no image
     */
    public static function save_cover(int $draftid, int $courseid, int $sectionid): string {
        $ctx = \context_course::instance($courseid);
        $fs = get_file_storage();
        $fs->delete_area_files($ctx->id, 'format_customtiles', self::FILEAREA, $sectionid);
        file_save_draft_area_files(
            $draftid,
            $ctx->id,
            'format_customtiles',
            self::FILEAREA,
            $sectionid,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['web_image']]
        );
        $files = $fs->get_area_files($ctx->id, 'format_customtiles', self::FILEAREA, $sectionid, 'id', false);
        if (!$files) {
            return '';
        }
        $file = reset($files);
        foreach (self::WIDTHS as $w) {
            $record = [
                'contextid' => $ctx->id, 'component' => 'format_customtiles', 'filearea' => self::FILEAREA,
                'itemid' => $sectionid, 'filepath' => '/w' . $w . '/', 'filename' => $file->get_filename(),
            ];
            try {
                $fs->convert_image($record, $file, $w, null, true, 82);
            } catch (\Throwable $e) {
                // Keep the original only; the pluginfile callback falls back to it.
                debugging('format_customtiles: could not resize cover: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        return $file->get_filename();
    }

    /**
     * Human readable duration.
     *
     * @param int $minutes
     * @return string
     */
    public static function format_minutes(int $minutes): string {
        if ($minutes < 60) {
            return get_string('minutesshort', 'format_customtiles', $minutes);
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $m ? get_string('hoursminutesshort', 'format_customtiles', ['h' => $h, 'm' => $m])
            : get_string('hoursshort', 'format_customtiles', $h);
    }
}
