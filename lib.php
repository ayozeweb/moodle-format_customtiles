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
use format_customtiles\local\section_style;
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
     * Section options (all cached in section_info).
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        $options = [
            'ct_cover' => ['default' => '', 'type' => PARAM_FILE, 'cache' => true],
            'ct_icon' => ['default' => '', 'type' => PARAM_ALPHANUMEXT, 'cache' => true],
            'ct_color' => ['default' => '', 'type' => PARAM_RAW_TRIMMED, 'cache' => true],
            'ct_color2' => ['default' => '', 'type' => PARAM_RAW_TRIMMED, 'cache' => true],
            'ct_subtitle' => ['default' => '', 'type' => PARAM_TEXT, 'cache' => true],
            'ct_minutes' => ['default' => 0, 'type' => PARAM_INT, 'cache' => true],
        ];
        if ($foreditform) {
            $options['ct_icon'] += [
                'label' => new lang_string('sectionicon', 'format_customtiles'),
                'element_type' => 'select',
                'element_attributes' => [section_style::get_icon_options()],
            ];
            $options['ct_color'] += [
                'label' => new lang_string('sectioncolor', 'format_customtiles'),
                'help' => 'sectioncolor',
                'help_component' => 'format_customtiles',
                'element_type' => 'text',
                'element_attributes' => [['maxlength' => 7, 'size' => 8, 'placeholder' => '#1d4ed8']],
            ];
            $options['ct_color2'] += [
                'label' => new lang_string('sectioncolor2', 'format_customtiles'),
                'element_type' => 'text',
                'element_attributes' => [['maxlength' => 7, 'size' => 8, 'placeholder' => '#0f766e']],
            ];
            $options['ct_subtitle'] += [
                'label' => new lang_string('sectionsubtitle', 'format_customtiles'),
                'element_type' => 'text',
                'element_attributes' => [['maxlength' => 120, 'size' => 50]],
            ];
            $options['ct_minutes'] += [
                'label' => new lang_string('sectionminutes', 'format_customtiles'),
                'help' => 'sectionminutes',
                'help_component' => 'format_customtiles',
                'element_type' => 'text',
                'element_attributes' => [['maxlength' => 5, 'size' => 6]],
            ];
            // The cover is edited with a file manager (added separately); the stored name is a hidden field.
            $options['ct_cover'] += ['label' => '', 'element_type' => 'hidden'];
        }
        return $options;
    }

    /**
     * Section edit form (with cover image draft handling).
     *
     * @param mixed $action
     * @param array $customdata
     * @return \format_customtiles\form\editsection_form
     */
    public function editsection_form($action, $customdata = []) {
        if (!array_key_exists('course', $customdata)) {
            $customdata['course'] = $this->get_course();
        }
        return new \format_customtiles\form\editsection_form($action, $customdata);
    }

    /**
     * Save section options, including the cover image.
     *
     * @param stdClass|array $data
     * @return bool
     */
    public function update_section_format_options($data) {
        $data = (array)$data;
        if (array_key_exists('ct_cover_filemanager', $data)) {
            $data['ct_cover'] = section_style::save_cover(
                (int)$data['ct_cover_filemanager'],
                $this->courseid,
                (int)$data['id']
            );
            unset($data['ct_cover_filemanager']);
        }
        foreach (['ct_color', 'ct_color2'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = \format_customtiles\local\color::normalise((string)$data[$key]) ?? '';
            }
        }
        if (isset($data['ct_minutes'])) {
            $data['ct_minutes'] = max(0, min(99999, (int)$data['ct_minutes']));
        }
        if (isset($data['ct_icon']) && !isset(section_style::get_icons()[$data['ct_icon']])) {
            $data['ct_icon'] = '';
        }
        return parent::update_section_format_options($data);
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
        if ($forsection) {
            $elements = array_merge($elements, $this->add_section_cover_elements($mform));
        }
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
     * Cover image element and live preview of the section tile.
     *
     * @param MoodleQuickForm $mform
     * @return array
     */
    protected function add_section_cover_elements($mform): array {
        global $PAGE;
        $elements = [];
        $elements[] = $mform->addElement(
            'filemanager',
            'ct_cover_filemanager',
            get_string('sectioncover', 'format_customtiles'),
            null,
            ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['web_image']]
        );
        $mform->addHelpButton('ct_cover_filemanager', 'sectioncover', 'format_customtiles');
        $elements[] = $mform->addElement(
            'static',
            'ct_preview',
            get_string('sectionpreview', 'format_customtiles'),
            '<div class="ct-root ct-sectionpreview" data-region="ct-sectionpreview" aria-live="polite">' .
            '<div class="ct-tile ct-item"><div class="ct-tile__cover" aria-hidden="true">' .
            '<i class="fa-solid ct-tile__icon" data-region="icon"></i></div>' .
            '<div class="ct-tile__body"><p class="ct-item__title" data-region="title"></p>' .
            '<p class="ct-item__meta" data-region="subtitle"></p>' .
            '<p class="ct-item__meta" data-region="time"></p></div></div></div>'
        );
        $PAGE->requires->js_call_amd('format_customtiles/sectionpreview', 'init');
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
            if ($data['basecolor'] !== '' && !CLI_SCRIPT) {
                // Tell the teacher when the colour had to be corrected to keep AA contrast.
                $adjusted = false;
                skin::get_tokens($data['skin'] ?? skin::DEFAULT, $data['basecolor'], $adjusted);
                if ($adjusted) {
                    \core\notification::warning(get_string('contrastadjusted', 'format_customtiles'));
                }
            }
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

/**
 * Serve plugin files (section cover images) to users who can view the course.
 *
 * @param stdClass $course
 * @param stdClass|null $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false when the file does not exist
 */
function format_customtiles_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_COURSE || $filearea !== section_style::FILEAREA) {
        return false;
    }
    require_login($course);
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'format_customtiles', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        // Fall back to the original when a resized variant is missing.
        $file = $fs->get_file($context->id, 'format_customtiles', $filearea, $itemid, '/', $filename);
        if (!$file) {
            return false;
        }
    }
    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}

/**
 * Add the "Activity categories" entry to the course navigation (More menu) for teachers.
 *
 * @param navigation_node $navigation
 * @param stdClass $course
 * @param context $context
 */
function format_customtiles_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if ($course->format !== 'customtiles' || !has_capability('moodle/course:manageactivities', $context)) {
        return;
    }
    $navigation->add(
        get_string('assigncategories', 'format_customtiles'),
        new moodle_url('/course/format/customtiles/assign.php', ['id' => $course->id]),
        navigation_node::TYPE_SETTING,
        null,
        'customtiles_categories',
        new pix_icon('i/tag', '')
    );
}
