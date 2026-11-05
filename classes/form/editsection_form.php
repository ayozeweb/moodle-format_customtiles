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
 * Section edit form with cover image preparation.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/editsection_form.php');

use format_customtiles\local\section_style;

/**
 * Prepares the cover filemanager draft area.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editsection_form extends \editsection_form {
    /**
     * Set data, preparing the draft area of the cover image.
     *
     * @param array|\stdClass $defaultvalues
     */
    public function set_data($defaultvalues) {
        $defaultvalues = (object)$defaultvalues;
        $course = $this->_customdata['course'];
        $draftid = file_get_submitted_draft_itemid('ct_cover_filemanager');
        file_prepare_draft_area(
            $draftid,
            \context_course::instance($course->id)->id,
            'format_customtiles',
            section_style::FILEAREA,
            (int)$defaultvalues->id,
            ['subdirs' => 0, 'maxfiles' => 1]
        );
        $defaultvalues->ct_cover_filemanager = $draftid;
        parent::set_data($defaultvalues);
    }
}
