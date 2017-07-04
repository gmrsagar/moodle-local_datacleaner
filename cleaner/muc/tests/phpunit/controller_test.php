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
 * @package     cleaner_muc
 * @subpackage  local_cleanurls
 * @author      Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright   2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use cleaner_muc\controller;

defined('MOODLE_INTERNAL') || die();

class  local_cleanurls_cleaner_muc_controller_test extends advanced_testcase {
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        // Trigger classloaders.
        class_exists(controller::class);
    }

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
        self::setAdminUser();
    }

    public function test_it_downloads_the_config_file() {
        global $CFG;

        $mucfile = "{$CFG->dataroot}/muc/config.php";
        $create = !file_exists($mucfile);
        if ($create) {
            $dirname = dirname($mucfile);
            if (!is_dir($dirname)) {
                mkdir($dirname);
            }
            file_put_contents($mucfile, '<?php // Test MUC File');
        }

        $_GET['sesskey'] = sesskey();
        $actual = $this->get_download_file();

        $expected = file_get_contents($mucfile);

        self::assertSame($expected, $actual);
        $this->resetDebugging(); // This may show some debugging messages because cache definitions changed.
    }

    public function test_it_requires_sesskey_to_download_file() {
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('sesskey');
        $this->get_download_file();
    }

    public function test_it_does_not_allow_download_if_not_admin() {
        // It should already be blocked by downloader page, but add one more layer of check.

        self::setUser($this->getDataGenerator()->create_user());

        $_GET['sesskey'] = sesskey();
        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Only admins can download MUC configuration');
        $this->get_download_file();
    }

    public function test_it_generates_the_correct_filename() {
        global $CFG;

        $CFG->wwwroot = 'http://thesite.url.to-use';
        $expected = rawurlencode($CFG->wwwroot) . '.muc';
        $actual = controller::get_download_filename();
        self::assertSame($expected, $actual);
    }

    private function get_download_file() {
        ob_start();
        try {
            (new controller())->download();
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return $html;
    }
}
