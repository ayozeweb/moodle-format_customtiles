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
 * Every template renders with its documented example context.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_customtiles;

/**
 * Template tests.
 *
 * @package    format_customtiles
 * @copyright  2026 Ayoze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class templates_test extends \advanced_testcase {
    /**
     * Render each template with the JSON of its "Example context".
     */
    public function test_templates_render_with_example_context(): void {
        global $PAGE;
        $this->resetAfterTest();
        $dir = realpath(__DIR__ . '/../templates');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        $count = 0;
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'mustache') {
                continue;
            }
            $source = file_get_contents($file->getPathname());
            $this->assertMatchesRegularExpression('/@template format_customtiles\/[a-z_\/]+/', $source, $file->getFilename());
            $this->assertSame(
                1,
                preg_match('/Example context \(json\):\s*(\{.*?\n    \})\s*\n\}\}/s', $source, $m),
                'No example context in ' . $file->getFilename()
            );
            $context = json_decode($m[1], true);
            $this->assertNotNull($context, 'Invalid example JSON in ' . $file->getFilename());
            $name = 'format_customtiles/' . substr($file->getPathname(), strlen($dir) + 1, -9);
            $html = $PAGE->get_renderer('core')->render_from_template($name, $context);
            $this->assertNotEmpty(trim($html), $name);
            $count++;
        }
        $this->assertGreaterThanOrEqual(8, $count);
    }
}
