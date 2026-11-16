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
 * Data model of the course page: sections, progress, states and the "continue" target.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles\local;

use core_courseformat\base as format_base;

/**
 * Builds everything the layouts need from a single pass over the course.
 *
 * Progress counts are cached per user and course (MUC "progress") and invalidated by completion
 * events and by the course cache revision, so painting the course issues no per-section queries.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze M de Paz Paz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_data {
    /** @var string State: locked by an access restriction. */
    public const STATE_LOCKED = 'locked';
    /** @var string State: hidden from students (teachers only). */
    public const STATE_HIDDEN = 'hidden';
    /** @var string State: nothing done yet. */
    public const STATE_PENDING = 'pending';
    /** @var string State: partially done. */
    public const STATE_INPROGRESS = 'inprogress';
    /** @var string State: everything done. */
    public const STATE_COMPLETED = 'completed';
    /** @var string State: content added since the user's previous visit. */
    public const STATE_NEW = 'new';

    /** @var array Progress row of a section without figures. */
    private const EMPTY_ROW = ['activities' => 0, 'tracked' => 0, 'done' => 0, 'new' => false, 'units' => []];

    /** @var int Seconds without activity after which a course view counts as a new visit. */
    private const VISIT_GAP = 30 * MINSECS;

    /** @var string[] State => icon (Font Awesome) used together with a text label. */
    private const STATE_ICONS = [
        self::STATE_LOCKED => 'fa-lock',
        self::STATE_HIDDEN => 'fa-eye-slash',
        self::STATE_PENDING => 'fa-circle',
        self::STATE_INPROGRESS => 'fa-circle-half-stroke',
        self::STATE_COMPLETED => 'fa-circle-check',
        self::STATE_NEW => 'fa-star',
    ];

    /** @var format_base */
    private $format;
    /** @var \course_modinfo */
    private $modinfo;
    /** @var \stdClass */
    private $course;
    /** @var int */
    private $userid;
    /** @var array|null */
    private $progress = null;
    /** @var array[]|null Exported sections, built once per object. */
    private $sections = null;

    /**
     * Constructor.
     *
     * @param format_base $format
     * @param int|null $userid defaults to the current user
     */
    public function __construct(format_base $format, ?int $userid = null) {
        global $USER;
        $this->format = $format;
        $this->course = $format->get_course();
        $this->modinfo = $format->get_modinfo();
        $this->userid = $userid ?? (int)$USER->id;
    }

    /**
     * Drop the cached progress of a user in a course.
     *
     * @param int $userid
     * @param int $courseid
     */
    public static function invalidate(int $userid, int $courseid): void {
        \cache::make('format_customtiles', 'progress')->delete($userid . '_' . $courseid);
    }

    /**
     * Store the last activity visited by the current user in a course (a user preference).
     *
     * @param int $courseid
     * @param int $cmid
     */
    public static function remember_last_activity(int $courseid, int $cmid): void {
        $name = self::preference_name($courseid);
        if ((int)get_user_preferences($name, 0) !== $cmid) {
            set_user_preference($name, $cmid);
        }
    }

    /**
     * Name of the user preference holding the previous and current visit times ("previous,current").
     *
     * @param int $courseid
     * @return string
     */
    public static function visit_preference_name(int $courseid): string {
        return 'format_customtiles_seen_' . $courseid;
    }

    /**
     * Time of the visit before the current one (0 on the first visit). Content added after it is "new".
     *
     * @param int $courseid
     * @param int $userid
     * @return int
     */
    public static function get_previous_visit(int $courseid, int $userid): int {
        $value = (string)get_user_preferences(self::visit_preference_name($courseid), '', $userid);
        return (int)(explode(',', $value)[0] ?? 0);
    }

    /**
     * Record that the current user is looking at the course page. A view more than VISIT_GAP after the
     * last one starts a new visit: the old "current" time becomes the "previous" one.
     *
     * @param int $courseid
     * @param int|null $now for tests
     */
    public static function register_visit(int $courseid, ?int $now = null): void {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            return;
        }
        $now ??= time();
        $name = self::visit_preference_name($courseid);
        [$previous, $current] = array_map('intval', explode(',', (string)get_user_preferences($name, '0,0')) + [0, 0]);
        if ($current === 0) {
            set_user_preference($name, '0,' . $now);
        } else if ($now - $current > self::VISIT_GAP) {
            set_user_preference($name, $current . ',' . $now);
            self::invalidate((int)$USER->id, $courseid);
        }
    }

    /**
     * Name of the user preference holding the last visited activity.
     *
     * @param int $courseid
     * @return string
     */
    public static function preference_name(int $courseid): string {
        return 'format_customtiles_last_' . $courseid;
    }

    /**
     * Whether progress can be computed for the user.
     *
     * @return bool
     */
    public function has_tracking(): bool {
        if (!$this->userid || isguestuser($this->userid)) {
            return false;
        }
        return (bool)(new \completion_info($this->course))->is_enabled();
    }

    /**
     * Cached progress figures.
     *
     * @return array ['rev', 'sections' => [num => [...]], 'pending' => cmid[], 'all' => cmid[], 'tracked', 'done']
     */
    public function get_progress(): array {
        if ($this->progress !== null) {
            return $this->progress;
        }
        $cache = \cache::make('format_customtiles', 'progress');
        $key = $this->userid . '_' . $this->course->id;
        $stored = $cache->get($key);
        if (is_array($stored) && ($stored['rev'] ?? null) === (int)$this->course->cacherev) {
            return $this->progress = $stored;
        }
        $this->progress = $this->compute_progress();
        $cache->set($key, $this->progress);
        return $this->progress;
    }

    /**
     * Compute the figures for all sections with one pass (completion data of the whole course is loaded once).
     *
     * Sub-sections (units) are counted inside the section that contains them: the figures of a section
     * are its own activities plus those of its units, in the order they appear on the page.
     *
     * @return array
     */
    private function compute_progress(): array {
        $completion = new \completion_info($this->course);
        $tracking = $this->has_tracking();
        $previousvisit = self::get_previous_visit((int)$this->course->id, $this->userid);

        // Own figures of every section (units included), and where each unit sits in its parent.
        $own = [];
        foreach ($this->modinfo->get_section_info_all() as $num => $section) {
            $row = ['activities' => 0, 'tracked' => 0, 'done' => 0, 'new' => false, 'all' => [], 'pending' => [],
                'order' => [], 'units' => []];
            foreach ($this->modinfo->sections[$num] ?? [] as $cmid) {
                $cm = $this->modinfo->cms[$cmid];
                $unit = $cm->get_delegated_section_info();
                if ($unit) {
                    if ($cm->uservisible) {
                        $row['order'][] = ['unit' => (int)$unit->section];
                        $row['units'][] = (int)$unit->section;
                    }
                    continue;
                }
                if (!$cm->uservisible || !$cm->url || $cm->is_stealth()) {
                    continue;
                }
                $row['activities']++;
                $row['all'][] = (int)$cm->id;
                $row['order'][] = ['cm' => (int)$cm->id];
                if ($previousvisit > 0 && !empty($cm->added) && $cm->added > $previousvisit) {
                    $row['new'] = true;
                }
                if (!$tracking || !$completion->is_enabled($cm)) {
                    continue;
                }
                $row['tracked']++;
                $data = $completion->get_data($cm, true, $this->userid);
                if (in_array((int)$data->completionstate, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS])) {
                    $row['done']++;
                } else {
                    $row['pending'][] = (int)$cm->id;
                }
            }
            $own[$num] = $row;
        }

        $result = [
            'rev' => (int)$this->course->cacherev,
            'sections' => [],
            'pending' => [],
            'all' => [],
            'tracked' => 0,
            'done' => 0,
        ];
        foreach ($this->modinfo->get_section_info_all() as $num => $section) {
            $row = $own[$num];
            if (!$section->is_delegated() && $row['units']) {
                // Aggregate the units into their parent, keeping the page order.
                $total = ['activities' => 0, 'tracked' => 0, 'done' => 0, 'new' => false, 'all' => [], 'pending' => []];
                $ownpending = array_flip($row['pending']);
                foreach ($row['order'] as $item) {
                    if (isset($item['cm'])) {
                        $total['all'][] = $item['cm'];
                        if (isset($ownpending[$item['cm']])) {
                            $total['pending'][] = $item['cm'];
                        }
                        continue;
                    }
                    $unit = $own[$item['unit']];
                    $total['all'] = array_merge($total['all'], $unit['all']);
                    $total['pending'] = array_merge($total['pending'], $unit['pending']);
                    $total['activities'] += $unit['activities'];
                    $total['tracked'] += $unit['tracked'];
                    $total['done'] += $unit['done'];
                    $total['new'] = $total['new'] || $unit['new'];
                }
                $row = [
                    'activities' => $row['activities'] + $total['activities'],
                    'tracked' => $row['tracked'] + $total['tracked'],
                    'done' => $row['done'] + $total['done'],
                    'new' => $row['new'] || $total['new'],
                    'all' => $total['all'],
                    'pending' => $total['pending'],
                    'units' => $row['units'],
                ];
            }
            unset($row['order']);
            $result['sections'][$num] = $row;
            if (!$section->is_delegated()) {
                $result['all'] = array_merge($result['all'], $row['all']);
                $result['pending'] = array_merge($result['pending'], $row['pending']);
                $result['tracked'] += $row['tracked'];
                $result['done'] += $row['done'];
            }
        }
        return $result;
    }

    /**
     * Overall course progress.
     *
     * @return array|null ['done', 'total', 'percent'] or null when nothing is tracked
     */
    public function get_overall(): ?array {
        $progress = $this->get_progress();
        if (!$progress['tracked']) {
            return null;
        }
        return [
            'done' => $progress['done'],
            'total' => $progress['tracked'],
            'percent' => (int)floor(100 * $progress['done'] / $progress['tracked']),
        ];
    }

    /**
     * Section states, ready for templates. Each section carries its units (sub-sections), if any.
     *
     * @return array[]
     */
    public function get_sections(): array {
        if ($this->sections !== null) {
            return $this->sections;
        }
        $progress = $this->get_progress();
        $out = [];
        $position = 0;
        foreach ($this->modinfo->get_listed_section_info_all() as $num => $section) {
            if (!$this->format->is_section_visible($section)) {
                continue;
            }
            $row = $progress['sections'][$num] ?? self::EMPTY_ROW;
            $item = $this->export_section($section, $row, ++$position);
            $units = [];
            foreach ($row['units'] ?? [] as $index => $unitnum) {
                $unit = $this->modinfo->get_section_info($unitnum);
                if (!$unit) {
                    continue;
                }
                $unititem = $this->export_section($unit, $progress['sections'][$unitnum] ?? self::EMPTY_ROW, $index + 1);
                $unititem['isunit'] = true;
                $unititem['displaynum'] = $item['displaynum'] . '.' . ($index + 1);
                $unititem['parentid'] = (int)$section->id;
                if ($item['locked']) {
                    $unititem['locked'] = true;
                    $unititem['url'] = '';
                }
                $units[] = $unititem;
            }
            $item['units'] = $units;
            $item['hasunits'] = !empty($units);
            $item['unitslabel'] = $units ? plural::units(count($units)) : '';
            // A section without its own estimated time takes the sum of its units.
            if ($units && !$item['minutes']) {
                $item['minutes'] = array_sum(array_column($units, 'minutes'));
                $item['timelabel'] = $item['minutes'] ? section_style::format_minutes($item['minutes']) : '';
            }
            $out[] = $item;
        }
        return $this->sections = $out;
    }

    /**
     * One section or unit by id, with what is needed to move around it.
     *
     * @param int $sectionid
     * @return array|null ['item', 'parent' (for units), 'previous', 'next'] where previous/next are open neighbours
     */
    public function get_section_view(int $sectionid): ?array {
        $sections = $this->get_sections();
        $open = array_values(array_filter($sections, fn($s) => !$s['locked']));
        foreach ($sections as $section) {
            if ($section['id'] === $sectionid) {
                $index = array_search($sectionid, array_column($open, 'id'));
                return [
                    'item' => $section,
                    'parent' => null,
                    'previous' => $index !== false ? ($open[$index - 1] ?? null) : null,
                    'next' => $index !== false ? ($open[$index + 1] ?? null) : null,
                ];
            }
            foreach ($section['units'] as $unit) {
                if ($unit['id'] !== $sectionid) {
                    continue;
                }
                $siblings = array_values(array_filter($section['units'], fn($u) => !$u['locked']));
                $index = array_search($sectionid, array_column($siblings, 'id'));
                $parentindex = array_search($section['id'], array_column($open, 'id'));
                return [
                    'item' => $unit,
                    'parent' => $section,
                    // Before the first unit comes its section; after the last one, the next section.
                    'previous' => $index !== false && isset($siblings[$index - 1]) ? $siblings[$index - 1] : $section,
                    'next' => $index !== false && isset($siblings[$index + 1]) ? $siblings[$index + 1]
                        : ($parentindex !== false ? ($open[$parentindex + 1] ?? null) : null),
                ];
            }
        }
        return null;
    }

    /**
     * Template data of a section or unit.
     *
     * @param \section_info $section
     * @param array $row progress row
     * @param int $position 1-based position among its siblings
     * @return array
     */
    private function export_section(\section_info $section, array $row, int $position): array {
        $context = \context_course::instance($this->course->id);
        $marker = (int)($this->course->marker ?? 0);
        $state = $this->get_state($section, $row);
        $locked = $state === self::STATE_LOCKED;
        $percent = $row['tracked'] ? (int)floor(100 * $row['done'] / $row['tracked']) : null;
        $item = [
            'id' => (int)$section->id,
            'num' => (int)$section->section,
            // Number shown to the user: units are numbered inside their section ("5.2").
            'displaynum' => (string)$section->section,
            'position' => $position,
            'name' => $this->format->get_section_name($section),
            'url' => $locked ? '' : $this->section_url($section),
            'state' => $state,
            'statelabel' => get_string('state_' . $state, 'format_customtiles'),
            'stateicon' => self::STATE_ICONS[$state],
            'is' . $state => true,
            'locked' => $locked,
            'reason' => $locked ? (string)$section->availableinfo : '',
            'summary' => $locked ? '' : $this->excerpt($section, $context),
            'activities' => $row['activities'],
            'activitieslabel' => plural::activities((int)$row['activities']),
            'hasprogress' => $percent !== null,
            'done' => $row['done'],
            'tracked' => $row['tracked'],
            'percent' => $percent ?? 0,
            'progresslabel' => $percent === null ? '' : get_string(
                'sectionprogress',
                'format_customtiles',
                ['done' => $row['done'], 'total' => $row['tracked']]
            ),
            'iscurrent' => $marker > 0 && $marker === (int)$section->section,
            'visible' => (bool)$section->visible,
            'isunit' => false,
            'units' => [],
            'hasunits' => false,
        ] + section_style::export($section, (int)$this->course->id);
        if ($locked) {
            $item['hascolor'] = false;
            $item['hascover'] = false;
        }
        return $item;
    }

    /**
     * URL of a section in focused view.
     *
     * @param \section_info $section
     * @return string
     */
    private function section_url(\section_info $section): string {
        return (new \moodle_url('/course/section.php', ['id' => $section->id]))->out(false);
    }

    /**
     * State of a section for the current user.
     *
     * @param \section_info $section
     * @param array $row progress row
     * @return string
     */
    private function get_state(\section_info $section, array $row): string {
        if (!$section->uservisible) {
            return self::STATE_LOCKED;
        }
        if (!$section->visible) {
            return self::STATE_HIDDEN;
        }
        if ($row['tracked'] > 0 && $row['done'] >= $row['tracked']) {
            return self::STATE_COMPLETED;
        }
        if ($row['done'] > 0) {
            return self::STATE_INPROGRESS;
        }
        return $row['new'] ? self::STATE_NEW : self::STATE_PENDING;
    }

    /**
     * Plain-text excerpt of a section summary.
     *
     * @param \section_info $section
     * @param \context $context
     * @return string
     */
    private function excerpt(\section_info $section, \context $context): string {
        if (trim((string)$section->summary) === '') {
            return '';
        }
        $text = file_rewrite_pluginfile_urls(
            $section->summary,
            'pluginfile.php',
            $context->id,
            'course',
            'section',
            $section->id
        );
        $text = format_text($text, $section->summaryformat, ['context' => $context, 'noclean' => false]);
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')));
        return shorten_text($text, 110, true);
    }

    /**
     * Target of the "Continue where you left off" button.
     *
     * Order: last visited activity if still pending; else the next pending activity after it;
     * else the first pending one; else (course complete) the last visited or the first activity.
     *
     * @return array|null ['url', 'name', 'cmid', 'iscomplete', 'isfirst']
     */
    public function get_continue(): ?array {
        $progress = $this->get_progress();
        if (!$progress['all']) {
            return null;
        }
        $last = $this->userid ? (int)get_user_preferences(self::preference_name($this->course->id), 0, $this->userid) : 0;
        $all = $progress['all'];
        $pending = $progress['pending'];
        $target = null;
        $iscomplete = false;
        if ($last && in_array($last, $all)) {
            if (!$this->has_tracking() || in_array($last, $pending) || !$pending) {
                $target = $last;
                $iscomplete = $this->has_tracking() && !$pending;
            } else {
                // The last one is done: next pending after it in course order, else the first pending.
                $after = array_values(array_filter($pending, fn($id) => array_search($id, $all) > array_search($last, $all)));
                $target = $after[0] ?? $pending[0];
            }
        } else if ($pending) {
            $target = $pending[0];
        } else {
            $target = $all[0];
            $iscomplete = $this->has_tracking() && $progress['tracked'] > 0;
        }
        $cm = $this->modinfo->cms[$target] ?? null;
        if (!$cm || !$cm->url) {
            return null;
        }
        return [
            'url' => $cm->url->out(false),
            'name' => $cm->get_formatted_name(),
            'cmid' => (int)$cm->id,
            'iscomplete' => $iscomplete,
            'isfirst' => !$last,
        ];
    }

    /**
     * Course-level figures for the "context pills".
     *
     * @return array ['sections', 'units', 'activities', 'minutes']
     */
    public function get_context_figures(): array {
        $progress = $this->get_progress();
        $sections = 0;
        $units = 0;
        $minutes = 0;
        foreach ($this->get_sections() as $section) {
            if ($section['num'] === 0 || $section['locked']) {
                continue;
            }
            $sections++;
            $units += count($section['units']);
            $minutes += (int)$section['minutes'];
        }
        return [
            'sections' => $sections,
            'units' => $units,
            'activities' => count($progress['all']),
            'minutes' => $minutes,
        ];
    }
}
