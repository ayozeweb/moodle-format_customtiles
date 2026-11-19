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
 * Moodle app support.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\output;

use format_customtiles\local\course_data;
use format_customtiles\local\hero;
use format_customtiles\local\skin;

/**
 * Course page in the Moodle app (see db/mobile.php).
 *
 * The app loads the sections and activities itself and passes them to the template as `sections`; this
 * method adds what only the format knows (covers, colours, states, progress, the continue target) and
 * the template shows a grid of sections and, when one is chosen, that section through the app's own
 * <core-course-section> component, so activities, completion and downloads behave as in any course.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Template and data of the course page.
     *
     * @param array $args ['courseid' => int, plus the app's own arguments]
     * @return array templates, otherdata
     */
    public static function mobile_course_view(array $args): array {
        $courseid = (int)($args['courseid'] ?? 0);
        $course = get_course($courseid);
        require_login($course, false, null, false, true);
        $format = course_get_format($course);
        if ($format->get_format() !== 'customtiles') {
            throw new \moodle_exception('invalidcourseid');
        }
        return [
            'templates' => [[
                'id' => 'main',
                'html' => self::render('course', self::export($format)),
            ]],
            // The section open in the template; "" is the grid of sections.
            'otherdata' => ['section' => ''],
        ];
    }

    /**
     * Data for the mobile template.
     *
     * @param \core_courseformat\base $format
     * @return array
     */
    public static function export(\core_courseformat\base $format): array {
        $course = $format->get_course();
        $model = new course_data($format);
        course_data::register_visit((int)$course->id);
        $hero = hero::export($model, $format, true);

        $sections = array_values($model->get_sections());
        $count = count($sections);
        foreach ($sections as $i => &$section) {
            $section['prev'] = self::neighbour($sections, $i, -1);
            $section['next'] = self::neighbour($sections, $i, 1);
            $section['reasontext'] = trim(html_to_text((string)$section['reason'], 0, false));
            $section['ofx'] = get_string('sectionxofy', 'format_customtiles', ['x' => $i + 1, 'y' => $count]);
            $section['stateappicon'] = self::app_icon($section['stateicon']);
        }
        unset($section);

        // In the app the surfaces and the text follow the app's light or dark theme; the course skin
        // gives the accent colour (and a text colour checked against it).
        foreach ($hero->pills as &$pill) {
            $pill['appicon'] = self::app_icon($pill['icon']);
        }
        unset($pill);
        $tokens = skin::get_tokens(skin::for_format($format)['skin'], (string)($format->get_format_options()['basecolor'] ?? ''));
        return [
            'courseid' => (int)$course->id,
            'accent' => '--fct-primary:' . $tokens['primary'] . ';--fct-onprimary:' . $tokens['onprimary'],
            'hero' => $hero,
            'continue' => $hero->continue ?? null,
            'sections' => $sections,
            'hassections' => !empty($sections),
            'str' => [
                'backtocourse' => get_string('backtocourse', 'format_customtiles'),
                'overallprogress' => get_string('overallprogress', 'format_customtiles'),
                'prevslide' => get_string('prevslide', 'format_customtiles'),
                'nextslide' => get_string('nextslide', 'format_customtiles'),
                'sectionnavigation' => get_string('sectionnavigation', 'format_customtiles'),
            ],
        ];
    }

    /**
     * Render a template of the mobile/ folder.
     *
     * The app compiles the result as an Angular template, where "{{" starts an expression (and a lone "{"
     * is a syntax error). HTML entities do not help: Angular decodes them before looking for expressions.
     * So the escaping also turns every brace into the constant expression Angular documents for a literal
     * brace, and text written by teachers (section names, summaries) can never become Angular code.
     *
     * @param string $name template name, without extension
     * @param array $data
     * @return string
     */
    public static function render(string $name, array $data): string {
        $engine = new \Mustache\Engine([
            'loader' => new \Mustache\Loader\FilesystemLoader(__DIR__ . '/../../mobile', ['extension' => '.mustache']),
            'escape' => [self::class, 'escape'],
        ]);
        return $engine->render($name, $data);
    }

    /**
     * Escape a value for the app template: HTML, and braces for Angular (see render()).
     *
     * @param mixed $value
     * @return string
     */
    public static function escape($value): string {
        return strtr(
            htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            ['{' => "{{ '{' }}", '}' => "{{ '}' }}"]
        );
    }

    /**
     * Font Awesome class used on the web ("fa-lock") to the icon name of the app ("fas-lock").
     *
     * @param string $class
     * @return string
     */
    protected static function app_icon(string $class): string {
        return 'fas-' . preg_replace('/^fa-/', '', $class);
    }

    /**
     * Nearest section before or after another one that can be opened (locked ones are skipped).
     *
     * @param array[] $sections
     * @param int $index position of the current section
     * @param int $step -1 for the previous one, 1 for the next one
     * @return array|null ['id', 'name'], null when there is none
     */
    protected static function neighbour(array $sections, int $index, int $step): ?array {
        for ($i = $index + $step; isset($sections[$i]); $i += $step) {
            if (!$sections[$i]['locked']) {
                return ['id' => $sections[$i]['id'], 'name' => $sections[$i]['name']];
            }
        }
        return null;
    }
}
