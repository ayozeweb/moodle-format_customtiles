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
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

/**
 * Helpers for the section options.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_style {
    /** @var string File area of the cover images (itemid = section id). */
    public const FILEAREA = 'sectioncover';

    /** @var int[] Widths of the generated cover variants. */
    public const WIDTHS = [480, 960];

    /** @var int Largest width kept for the sanitised main image. */
    public const MAIN_WIDTH = 1920;

    /** @var string[] Raster formats accepted for covers. SVG is not accepted: it can carry scripts. */
    public const ACCEPTED_TYPES = ['.png', '.jpg', '.jpeg', '.gif', '.webp'];

    /** @var int Largest upload accepted for a cover, in bytes. */
    public const MAX_BYTES = 5 * 1024 * 1024;

    /** @var int Largest image accepted, in pixels (width x height), to bound the memory used when resizing. */
    public const MAX_PIXELS = 40000000;

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
            'focus' => self::focus_css((string)($section->ct_focus ?? '')),
        ];
        if ($c1 !== null) {
            $c2 ??= color::mix($c1, '#000000', 0.35);
            $mid = color::mix($c1, $c2, 0.5);
            $out['coverstyle'] = "--fct-c1:$c1;--fct-c2:$c2;--fct-fg:" . color::readable_on($mid);
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
     * Normalise a focal point "x,y" (percentages) or return null when invalid.
     *
     * @param string $value
     * @return int[]|null [x, y]
     */
    public static function parse_focus(string $value): ?array {
        if (!preg_match('/^\s*(\d{1,3})\s*,\s*(\d{1,3})\s*$/', $value, $m)) {
            return null;
        }
        return [min(100, (int)$m[1]), min(100, (int)$m[2])];
    }

    /**
     * CSS object-position of a focal point (centre when not set).
     *
     * @param string $value "x,y"
     * @return string
     */
    public static function focus_css(string $value): string {
        [$x, $y] = self::parse_focus($value) ?? [50, 50];
        return $x . '% ' . $y . '%';
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
     * Options of the cover file manager.
     *
     * @return array
     */
    public static function filemanager_options(): array {
        return ['subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => self::MAX_BYTES, 'accepted_types' => self::ACCEPTED_TYPES];
    }

    /**
     * Save the cover from a draft area and return its file name.
     *
     * The upload is never served as it came: it is checked (raster image, bounded size in bytes and
     * pixels) and re-encoded, which also drops any metadata (EXIF, GPS) or payload embedded in the
     * original file. What is kept is a sanitised main image (at most MAIN_WIDTH wide) and the
     * responsive variants. If the image in the draft area is the one already stored, nothing is done.
     *
     * @param int $draftid
     * @param int $courseid
     * @param int $sectionid
     * @return string file name, '' when there is no valid image
     */
    public static function save_cover(int $draftid, int $courseid, int $sectionid): string {
        global $USER;
        $ctx = \context_course::instance($courseid);
        $fs = get_file_storage();
        $area = [$ctx->id, 'format_customtiles', self::FILEAREA, $sectionid];

        // Main image currently stored and image now in the draft area (both at the root path).
        $current = self::root_file($fs->get_area_files(...[...$area, 'id', false]));
        $draft = self::root_file($fs->get_area_files(
            \context_user::instance($USER->id)->id,
            'user',
            'draft',
            $draftid,
            'id',
            false
        ));
        if (!$draft) {
            $fs->delete_area_files(...$area);
            return '';
        }
        if (
            $current && $current->get_contenthash() === $draft->get_contenthash()
                && $current->get_filename() === $draft->get_filename()
        ) {
            return $current->get_filename();
        }

        $filename = $draft->get_filename();
        $info = $draft->get_imageinfo();
        $valid = $info && !empty($info['width']) && !empty($info['height'])
            && in_array($info['mimetype'] ?? '', ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)
            && ($info['width'] * $info['height']) <= self::MAX_PIXELS
            && $draft->get_filesize() <= self::MAX_BYTES;
        $fs->delete_area_files(...$area);
        $created = 0;
        if ($valid) {
            $paths = ['/' => self::MAIN_WIDTH];
            foreach (self::WIDTHS as $w) {
                $paths['/w' . $w . '/'] = $w;
            }
            foreach ($paths as $path => $w) {
                $record = [
                    'contextid' => $ctx->id, 'component' => 'format_customtiles', 'filearea' => self::FILEAREA,
                    'itemid' => $sectionid, 'filepath' => $path, 'filename' => $filename,
                ];
                try {
                    // Never upscale: small images are re-encoded at their own width.
                    $fs->convert_image($record, $draft, min($w, (int)$info['width']), null, true, 82);
                    $created++;
                } catch (\Throwable $e) {
                    debugging('format_customtiles: could not re-encode cover: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }
        if ($created !== count(self::WIDTHS) + 1) {
            $fs->delete_area_files(...$area);
            if (!CLI_SCRIPT) {
                \core\notification::warning(get_string('coverrejected', 'format_customtiles'));
            }
            return '';
        }
        return $filename;
    }

    /**
     * The file at the root path among the files of an area.
     *
     * @param \stored_file[] $files
     * @return \stored_file|null
     */
    private static function root_file(array $files): ?\stored_file {
        foreach ($files as $file) {
            if ($file->get_filepath() === '/' && !$file->is_directory()) {
                return $file;
            }
        }
        return null;
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
