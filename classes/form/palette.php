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
 * Modal form: automatic, accessible colours for every section of the course.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\form;

use context;
use context_course;
use core_form\dynamic_form;
use format_customtiles\local\color;
use format_customtiles\local\palette as colours;
use format_customtiles\local\skin;
use moodle_url;

/**
 * Generates a set of distinct colours from a base colour and applies them to the sections.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class palette extends dynamic_form {
    /**
     * Course id from the request (the course must use this format).
     *
     * @return int
     */
    protected function get_courseid(): int {
        $courseid = $this->optional_param('courseid', 0, PARAM_INT);
        if (\core_courseformat\base::instance($courseid)->get_format() !== 'customtiles') {
            throw new \moodle_exception('invalidcourseformat');
        }
        return $courseid;
    }

    /**
     * Course context.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_course::instance($this->get_courseid());
    }

    /**
     * Changing the look of sections needs the core permission to edit sections.
     */
    protected function check_access_for_dynamic_submission(): void {
        require_capability('moodle/course:update', $this->get_context_for_dynamic_submission());
    }

    /**
     * Page the form belongs to.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {
        return new moodle_url('/course/view.php', ['id' => $this->get_courseid()]);
    }

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('html', \html_writer::tag('p', get_string('palette_intro', 'format_customtiles')));
        $mform->addElement(
            'text',
            'base',
            get_string('palette_base', 'format_customtiles'),
            ['size' => 8, 'maxlength' => 7, 'data-fct-colour' => '1']
        );
        $mform->setType('base', PARAM_RAW_TRIMMED);
        $mform->addRule('base', null, 'required', null, 'client');
        $mform->addElement('select', 'scope', get_string('palette_scope', 'format_customtiles'), [
            'empty' => get_string('palette_scope_empty', 'format_customtiles'),
            'all' => get_string('palette_scope_all', 'format_customtiles'),
        ]);
        $mform->addElement('advcheckbox', 'units', get_string('palette_units', 'format_customtiles'));
        $mform->setDefault('units', 1);
    }

    /**
     * Base colour must be a colour.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (color::normalise((string)($data['base'] ?? '')) === null) {
            $errors['base'] = get_string('badcolor', 'format_customtiles');
        }
        return $errors;
    }

    /**
     * Defaults: the course accent colour, or the primary colour of its theme.
     */
    public function set_data_for_dynamic_submission(): void {
        $courseid = $this->get_courseid();
        $options = \core_courseformat\base::instance($courseid)->get_format_options();
        $base = color::normalise((string)($options['basecolor'] ?? ''))
            ?? skin::get_tokens((string)($options['skin'] ?? skin::DEFAULT))['primary'];
        $this->set_data(['courseid' => $courseid, 'base' => $base, 'scope' => 'empty', 'units' => 1]);
    }

    /**
     * Sections (and units) to colour, in page order.
     *
     * @param int $courseid
     * @param bool $units
     * @param bool $onlyempty
     * @return \section_info[]
     */
    public static function targets(int $courseid, bool $units, bool $onlyempty): array {
        $modinfo = get_fast_modinfo($courseid);
        $targets = [];
        foreach ($modinfo->get_listed_section_info_all() as $num => $section) {
            if ($num > 0) {
                $targets[] = $section;
            }
            if (!$units) {
                continue;
            }
            foreach ($modinfo->sections[$num] ?? [] as $cmid) {
                $unit = $modinfo->cms[$cmid]->get_delegated_section_info();
                if ($unit) {
                    $targets[] = $unit;
                }
            }
        }
        if ($onlyempty) {
            $targets = array_values(array_filter(
                $targets,
                fn($s) => color::normalise((string)($s->ct_color ?? '')) === null
            ));
        }
        return $targets;
    }

    /**
     * Apply the generated colours.
     *
     * @return array ['count' => number of sections changed]
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $courseid = $this->get_courseid();
        $targets = self::targets($courseid, !empty($data->units), $data->scope !== 'all');
        $series = colours::series((string)$data->base, count($targets));
        $format = \core_courseformat\base::instance($courseid);
        foreach ($targets as $index => $section) {
            $format->update_section_format_options([
                'id' => (int)$section->id,
                'ct_color' => $series[$index]['color'],
                'ct_color2' => $series[$index]['color2'],
            ]);
        }
        return ['count' => count($targets)];
    }
}
