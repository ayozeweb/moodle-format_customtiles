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
 * Form to add or edit an activity category.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use format_customtiles\local\color;
use format_customtiles\local\section_style;

/**
 * Category form.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_form extends \moodleform {
    /**
     * Definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('text', 'code', get_string('catcode', 'format_customtiles'), ['size' => 8, 'maxlength' => 20]);
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required', null, 'client');
        $mform->addElement('text', 'name', get_string('catname', 'format_customtiles'), ['size' => 40, 'maxlength' => 100]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addElement(
            'text',
            'color',
            get_string('catcolor', 'format_customtiles'),
            ['size' => 8, 'maxlength' => 7, 'placeholder' => '#1d4ed8']
        );
        $mform->setType('color', PARAM_RAW_TRIMMED);
        $mform->setDefault('color', '#1d4ed8');
        $mform->addElement('select', 'icon', get_string('caticon', 'format_customtiles'), section_style::get_icon_options());
        $this->add_action_buttons();
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        $code = strtoupper(trim($data['code']));
        $select = 'code = :code AND id <> :id';
        if (
            $code === '' || $DB->record_exists_select(
                'format_customtiles_cat',
                $select,
                ['code' => $code, 'id' => (int)$data['id']]
            )
        ) {
            $errors['code'] = get_string('codeexists', 'format_customtiles');
        }
        if (color::normalise($data['color']) === null) {
            $errors['color'] = get_string('badcolor', 'format_customtiles');
        }
        return $errors;
    }
}
