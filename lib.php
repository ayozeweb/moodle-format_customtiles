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
 * Course format class for format_customtiles.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/format/lib.php');

use core\lang_string;
use format_customtiles\local\layouts;
use format_customtiles\local\skin;

/**
 * Custom tiles course format: visual, mobile-first layouts and skins.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_customtiles extends core_courseformat\base {
    /**
     * The format uses sections.
     *
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * The format uses the course index drawer.
     *
     * @return bool
     */
    public function uses_course_index() {
        return true;
    }

    /**
     * Sections are shown one per page (focused view), the course page is the overview.
     *
     * @return int
     */
    public function get_course_display(): int {
        return COURSE_DISPLAY_MULTIPAGE;
    }

    /**
     * Section name.
     *
     * @param int|stdClass|section_info $section
     * @return string
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true, ['context' => context_course::instance($this->courseid)]);
        }
        return $this->get_default_section_name($section);
    }

    /**
     * Default section name.
     *
     * @param int|stdClass|section_info $section
     * @return string
     */
    public function get_default_section_name($section) {
        $section = $this->get_section($section);
        if ($section->sectionnum == 0) {
            return get_string('section0name', 'format_customtiles');
        }
        return get_string('newsection', 'format_customtiles');
    }

    /**
     * Page title.
     *
     * @return string
     */
    public function page_title(): string {
        return get_string('sectionoutline');
    }

    /**
     * URL of the course page or of a section (single section page).
     *
     * @param int|stdClass|section_info|null $section
     * @param array $options
     * @return moodle_url
     */
    public function get_view_url($section, $options = []) {
        $course = $this->get_course();
        $section = (is_null($section) || $section instanceof section_info)
            ? $section
            : $this->get_section($section, IGNORE_MISSING);

        if (array_key_exists('pagesectionid', $options)) {
            $modinfo = $this->get_modinfo();
            $pagesectionid = $options['pagesectionid'] ?? null;
            $pagesection = $pagesectionid ? $modinfo->get_section_info_by_id($pagesectionid, IGNORE_MISSING) : null;
        } else if (array_key_exists('sr', $options)) {
            // TODO: Remove in Moodle 7.0 (MDL-88498).
            $pagesection = !is_null($options['sr']) ? $this->get_section($options['sr'], IGNORE_MISSING) : null;
        } else if ($options['navigation'] ?? false) {
            $pagesection = ($section && $section->get_component_instance())
                ? $section->get_component_instance()->get_parent_section()
                : $section;
        } else {
            $pagesection = null;
        }

        if (is_null($pagesection)) {
            $url = new moodle_url('/course/view.php', ['id' => $course->id]);
        } else {
            $url = new moodle_url('/course/section.php', ['id' => $pagesection->id]);
        }

        if ($this->uses_sections() && $section && ($section->id != $pagesection?->id)) {
            $url->set_anchor('section-' . $section->section);
        }
        return $url;
    }

    /**
     * Ajax support.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;
        return $ajaxsupport;
    }

    /**
     * Reactive components support.
     *
     * @return bool
     */
    public function supports_components() {
        return true;
    }

    /**
     * Linear (previous/next) navigation is provided by the core.
     *
     * @return bool
     */
    #[\Override]
    public static function uses_linear_navigation(): bool {
        return true;
    }

    /**
     * Course navigation node tweaks (hide empty general section).
     *
     * @param global_navigation $navigation
     * @param navigation_node $node
     */
    public function extend_course_navigation($navigation, navigation_node $node) {
        parent::extend_course_navigation($navigation, $node);
        $modinfo = get_fast_modinfo($this->get_course());
        $sections = $modinfo->get_sections();
        if (!isset($sections[0])) {
            $section = $modinfo->get_section_info(0);
            $generalsection = $node->get($section->id, navigation_node::TYPE_SECTION);
            if ($generalsection) {
                $generalsection->remove();
            }
        }
    }

    /**
     * Default blocks.
     *
     * @return array
     */
    public function get_default_blocks() {
        return [BLOCK_POS_LEFT => [], BLOCK_POS_RIGHT => []];
    }

    /**
     * Course format options.
     *
     * @param bool $foreditform
     * @return array
     */
    public function course_format_options($foreditform = false) {
        static $options = false;
        if ($options === false) {
            $courseconfig = get_config('moodlecourse');
            $options = [
                'hiddensections' => ['default' => $courseconfig->hiddensections ?? 1, 'type' => PARAM_INT],
                'layout' => ['default' => layouts::DEFAULT, 'type' => PARAM_ALPHANUMEXT],
                'layoutmobile' => ['default' => '', 'type' => PARAM_ALPHANUMEXT],
                'skin' => ['default' => skin::DEFAULT, 'type' => PARAM_ALPHANUMEXT],
                'basecolor' => ['default' => '', 'type' => PARAM_RAW_TRIMMED],
            ];
        }
        if ($foreditform && !isset($options['layout']['label'])) {
            $hidden = new core\output\choicelist();
            $hidden->set_allow_empty(false);
            $hidden->add_option(
                1,
                new lang_string('hiddensectionsinvisible'),
                ['description' => new lang_string('hiddensectionsinvisible_description')]
            );
            $hidden->add_option(
                0,
                new lang_string('hiddensectionscollapsed'),
                ['description' => new lang_string('hiddensectionscollapsed_description')]
            );

            $layoutoptions = layouts::get_options();
            $edit = [
                'hiddensections' => [
                    'label' => new lang_string('hiddensections'),
                    'element_type' => 'choicedropdown',
                    'element_attributes' => [$hidden],
                ],
                'layout' => [
                    'label' => new lang_string('layout', 'format_customtiles'),
                    'help' => 'layout',
                    'help_component' => 'format_customtiles',
                    'element_type' => 'select',
                    'element_attributes' => [$layoutoptions],
                ],
                'layoutmobile' => [
                    'label' => new lang_string('layoutmobile', 'format_customtiles'),
                    'element_type' => 'select',
                    'element_attributes' => [
                        ['' => get_string('layoutmobile_same', 'format_customtiles')] + $layoutoptions,
                    ],
                ],
                'skin' => [
                    'label' => new lang_string('skin', 'format_customtiles'),
                    'help' => 'skin',
                    'help_component' => 'format_customtiles',
                    'element_type' => 'select',
                    'element_attributes' => [skin::get_options()],
                ],
                'basecolor' => [
                    'label' => new lang_string('basecolor', 'format_customtiles'),
                    'help' => 'basecolor',
                    'help_component' => 'format_customtiles',
                    'element_type' => 'text',
                    'element_attributes' => [['maxlength' => 7, 'size' => 8, 'placeholder' => '#1d4ed8']],
                ],
            ];
            $options = array_merge_recursive($options, $edit);
        }
        return $options;
    }

    /**
     * Edit form elements.
     *
     * @param MoodleQuickForm $mform
     * @param bool $forsection
     * @return array
     */
    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $COURSE;
        $elements = parent::create_edit_form_elements($mform, $forsection);
        if (!$forsection && (empty($COURSE->id) || $COURSE->id == SITEID)) {
            $courseconfig = get_config('moodlecourse');
            $element = $mform->addElement('hidden', 'numsections');
            $mform->setType('numsections', PARAM_INT);
            $mform->setDefault('numsections', $courseconfig->numsections);
            array_unshift($elements, $element);
        }
        return $elements;
    }

    /**
     * Validate the colour option and keep unspecified options from the old course.
     *
     * @param stdClass|array $data
     * @param stdClass|null $oldcourse
     * @return bool
     */
    public function update_course_format_options($data, $oldcourse = null) {
        $data = (array)$data;
        if ($oldcourse !== null) {
            $oldcourse = (array)$oldcourse;
            foreach (array_keys($this->course_format_options()) as $key) {
                if (!array_key_exists($key, $data) && array_key_exists($key, $oldcourse)) {
                    $data[$key] = $oldcourse[$key];
                }
            }
        }
        if (isset($data['basecolor'])) {
            $data['basecolor'] = \format_customtiles\local\color::normalise((string)$data['basecolor']) ?? '';
        }
        if (isset($data['layout']) && !layouts::exists($data['layout'])) {
            $data['layout'] = layouts::DEFAULT;
        }
        if (isset($data['skin']) && !skin::exists($data['skin'])) {
            $data['skin'] = skin::DEFAULT;
        }
        return $this->update_format_options($data);
    }

    /**
     * Sections can be deleted.
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Course news forum supported.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Stealth activities allowed in visible sections.
     *
     * @param cm_info $cm
     * @param section_info $section
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        return !$section->section || $section->visible;
    }

    /**
     * Options exposed to the mobile app / web services.
     *
     * @return array
     */
    public function get_config_for_external() {
        return $this->get_format_options();
    }
}

/**
 * In-place editing callback for section names.
 *
 * @param string $itemtype
 * @param int $itemid
 * @param string $newvalue
 * @return \core\output\inplace_editable|null
 */
function format_customtiles_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/course/lib.php');
    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            [$itemid, 'customtiles'],
            MUST_EXIST
        );
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
    return null;
}
