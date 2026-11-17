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
 * Modal form to change the appearance of a section from the design view.
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
use format_customtiles\local\section_style as style;
use moodle_url;
use section_info;

/**
 * Cover (with focal point), icon, colours, subtitle and time of a section, plus "apply also to".
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section_style extends dynamic_form {
    /** @var section_info|null */
    private $section = null;

    /**
     * Section being edited (it must belong to a Custom tiles course).
     *
     * @return section_info
     */
    protected function get_section(): section_info {
        global $DB;
        if ($this->section === null) {
            $sectionid = $this->optional_param('sectionid', 0, PARAM_INT);
            $record = $DB->get_record('course_sections', ['id' => $sectionid], 'id, course', MUST_EXIST);
            if (\core_courseformat\base::instance($record->course)->get_format() !== 'customtiles') {
                throw new \moodle_exception('invalidcourseformat');
            }
            $this->section = get_fast_modinfo($record->course)->get_section_info_by_id($sectionid, MUST_EXIST);
        }
        return $this->section;
    }

    /**
     * Course context of the section.
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        return context_course::instance($this->get_section()->course);
    }

    /**
     * Same permission the core asks to edit a section.
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
        return new moodle_url('/course/view.php', ['id' => $this->get_section()->course]);
    }

    /**
     * Other sections and units of the course the style can be copied to.
     *
     * @return string[] id => name
     */
    protected function get_other_sections(): array {
        $section = $this->get_section();
        $format = \core_courseformat\base::instance($section->course);
        $modinfo = $format->get_modinfo();
        $options = [];
        foreach ($modinfo->get_listed_section_info_all() as $num => $info) {
            if ($num > 0 && (int)$info->id !== (int)$section->id) {
                $options[(int)$info->id] = $format->get_section_name($info);
            }
            foreach ($modinfo->sections[$num] ?? [] as $cmid) {
                $unit = $modinfo->cms[$cmid]->get_delegated_section_info();
                if ($unit && (int)$unit->id !== (int)$section->id) {
                    $options[(int)$unit->id] = $format->get_section_name($info) . ' › ' . $format->get_section_name($unit);
                }
            }
        }
        return $options;
    }

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $section = $this->get_section();
        $format = \core_courseformat\base::instance($section->course);
        $look = style::export($section, (int)$section->course);

        $mform->addElement('hidden', 'sectionid');
        $mform->setType('sectionid', PARAM_INT);

        // Live preview: the tile as it will look, the contrast of its text and the focal point of the cover.
        $preview = \html_writer::div(
            \html_writer::div(
                \html_writer::div(
                    \html_writer::empty_tag('img', ['class' => 'fct-cover__img', 'alt' => '', 'data-region' => 'coverimg',
                        'src' => $look['coversrc'] ?? '', 'hidden' => empty($look['hascover']) ? 'hidden' : null]) .
                    \html_writer::tag('i', '', ['class' => 'fa-solid fct-cover__icon', 'data-region' => 'icon',
                        'aria-hidden' => 'true']) .
                    \html_writer::span('', 'fct-sectionpreview__focus', ['data-region' => 'focusmark', 'hidden' => 'hidden']),
                    'fct-tile__cover',
                    ['data-region' => 'cover']
                ) .
                \html_writer::div(
                    \html_writer::tag('p', s($format->get_section_name($section)), ['class' => 'fct-item__title',
                        'data-region' => 'title']) .
                    \html_writer::tag('p', '', ['class' => 'fct-item__meta', 'data-region' => 'subtitle']) .
                    \html_writer::tag('p', '', ['class' => 'fct-item__meta', 'data-region' => 'time']),
                    'fct-tile__body'
                ),
                'fct-tile fct-item'
            ) .
            \html_writer::tag('p', '', ['class' => 'fct-sectionpreview__contrast', 'data-region' => 'contrast',
                'aria-live' => 'polite']) .
            \html_writer::tag('p', get_string('focushelp', 'format_customtiles'), ['class' => 'fct-sectionpreview__hint',
                'data-region' => 'focushint', 'hidden' => empty($look['hascover']) ? 'hidden' : null]),
            'fct-root fct-sectionpreview',
            ['data-region' => 'fct-sectionpreview', 'data-name' => $format->get_section_name($section)]
        );
        $mform->addElement('html', $preview);

        $mform->addElement(
            'filemanager',
            'ct_cover_filemanager',
            get_string('sectioncover', 'format_customtiles'),
            null,
            style::filemanager_options()
        );
        $mform->addHelpButton('ct_cover_filemanager', 'sectioncover', 'format_customtiles');

        $focus = [];
        $focus[] = $mform->createElement(
            'text',
            'focusx',
            get_string('focusx', 'format_customtiles'),
            ['size' => 4, 'maxlength' => 3, 'inputmode' => 'numeric']
        );
        $focus[] = $mform->createElement(
            'text',
            'focusy',
            get_string('focusy', 'format_customtiles'),
            ['size' => 4, 'maxlength' => 3, 'inputmode' => 'numeric']
        );
        $mform->addGroup($focus, 'focusgroup', get_string('focuspoint', 'format_customtiles'), ' ', false);
        $mform->setType('focusx', PARAM_INT);
        $mform->setType('focusy', PARAM_INT);
        $mform->addHelpButton('focusgroup', 'focuspoint', 'format_customtiles');

        $mform->addElement('select', 'ct_icon', get_string('sectionicon', 'format_customtiles'), style::get_icon_options());
        $mform->addElement(
            'text',
            'ct_color',
            get_string('sectioncolor', 'format_customtiles'),
            ['size' => 8, 'maxlength' => 7, 'placeholder' => '#1d4ed8', 'data-fct-colour' => '1']
        );
        $mform->setType('ct_color', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('ct_color', 'sectioncolor', 'format_customtiles');
        $mform->addElement(
            'text',
            'ct_color2',
            get_string('sectioncolor2', 'format_customtiles'),
            ['size' => 8, 'maxlength' => 7, 'placeholder' => '#0f766e', 'data-fct-colour' => '1']
        );
        $mform->setType('ct_color2', PARAM_RAW_TRIMMED);
        $mform->addElement(
            'text',
            'ct_subtitle',
            get_string('sectionsubtitle', 'format_customtiles'),
            ['size' => 40, 'maxlength' => 120]
        );
        $mform->setType('ct_subtitle', PARAM_TEXT);
        $mform->addElement(
            'text',
            'ct_minutes',
            get_string('sectionminutes', 'format_customtiles'),
            ['size' => 6, 'maxlength' => 5, 'inputmode' => 'numeric']
        );
        $mform->setType('ct_minutes', PARAM_INT);
        $mform->addHelpButton('ct_minutes', 'sectionminutes', 'format_customtiles');

        $others = $this->get_other_sections();
        if ($others) {
            $mform->addElement(
                'autocomplete',
                'applyto',
                get_string('applystyleto', 'format_customtiles'),
                $others,
                ['multiple' => true, 'noselectionstring' => get_string('applystyletonone', 'format_customtiles')]
            );
            $mform->addHelpButton('applyto', 'applystyleto', 'format_customtiles');
        }
    }

    /**
     * Validation of the values that have a format.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        foreach (['ct_color', 'ct_color2'] as $field) {
            if (trim((string)($data[$field] ?? '')) !== '' && color::normalise((string)$data[$field]) === null) {
                $errors[$field] = get_string('badcolor', 'format_customtiles');
            }
        }
        foreach (['focusx', 'focusy'] as $field) {
            $value = (int)($data[$field] ?? 50);
            if ($value < 0 || $value > 100) {
                $errors['focusgroup'] = get_string('focusrange', 'format_customtiles');
            }
        }
        $minutes = (int)($data['ct_minutes'] ?? 0);
        if ($minutes < 0 || $minutes > 99999) {
            $errors['ct_minutes'] = get_string('minutesrange', 'format_customtiles');
        }
        return $errors;
    }

    /**
     * Current values and draft area of the cover.
     */
    public function set_data_for_dynamic_submission(): void {
        $section = $this->get_section();
        $options = \core_courseformat\base::instance($section->course)->get_format_options($section);
        [$x, $y] = style::parse_focus((string)($options['ct_focus'] ?? '')) ?? [50, 50];
        $draftid = file_get_submitted_draft_itemid('ct_cover_filemanager');
        file_prepare_draft_area(
            $draftid,
            $this->get_context_for_dynamic_submission()->id,
            'format_customtiles',
            style::FILEAREA,
            (int)$section->id,
            style::filemanager_options()
        );
        $this->set_data([
            'sectionid' => (int)$section->id,
            'ct_cover_filemanager' => $draftid,
            'focusx' => $x,
            'focusy' => $y,
            'ct_icon' => $options['ct_icon'] ?? '',
            'ct_color' => $options['ct_color'] ?? '',
            'ct_color2' => $options['ct_color2'] ?? '',
            'ct_subtitle' => $options['ct_subtitle'] ?? '',
            'ct_minutes' => (int)($options['ct_minutes'] ?? 0),
        ]);
    }

    /**
     * Save the section and, if asked, copy colours and icon to other sections.
     *
     * @return array ['sectionid' => int, 'updated' => int[] ids of every section changed]
     */
    public function process_dynamic_submission() {
        $data = $this->get_data();
        $section = $this->get_section();
        $format = \core_courseformat\base::instance($section->course);
        $format->update_section_format_options([
            'id' => (int)$section->id,
            'ct_cover_filemanager' => (int)$data->ct_cover_filemanager,
            'ct_focus' => (int)$data->focusx . ',' . (int)$data->focusy,
            'ct_icon' => (string)$data->ct_icon,
            'ct_color' => (string)$data->ct_color,
            'ct_color2' => (string)$data->ct_color2,
            'ct_subtitle' => (string)$data->ct_subtitle,
            'ct_minutes' => (int)$data->ct_minutes,
        ]);
        $updated = [(int)$section->id];
        $allowed = $this->get_other_sections();
        foreach ((array)($data->applyto ?? []) as $targetid) {
            $targetid = (int)$targetid;
            if (!isset($allowed[$targetid])) {
                continue;
            }
            \core_courseformat\base::instance($section->course)->update_section_format_options([
                'id' => $targetid,
                'ct_icon' => (string)$data->ct_icon,
                'ct_color' => (string)$data->ct_color,
                'ct_color2' => (string)$data->ct_color2,
            ]);
            $updated[] = $targetid;
        }
        return ['sectionid' => (int)$section->id, 'updated' => $updated];
    }
}
