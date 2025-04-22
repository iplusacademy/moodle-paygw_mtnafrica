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
 * Testing cleanup
 *
 * @package    paygw_mtnafrica
 * @copyright  Medical Access Uganda Limited (e-learning.medical-access.org)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica\task;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Testing cleanup
 *
 * @package    paygw_mtnafrica
 * @copyright  Medical Access Uganda Limited (e-learning.medical-access.org)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(clean_up::class)]
final class clean_up_test extends \advanced_testcase {

    /**
     * Test clean up.
     */
    public function test_clean_up(): void {
        global $DB;
        $this->resetAfterTest();
        $task = new \paygw_mtnafrica\task\clean_up();
        $this->assertEquals('Clean up not completed payment task.', $task->get_name());
        $task->execute();
        $data = new \stdClass();
        $data->paymentid = 1;
        $data->userid = 1;
        $data->transactionid = 1;
        $data->moneyid = 1;
        $data->timecreated = time();
        $DB->insert_record('paygw_mtnafrica', $data);
        $cnt = $DB->count_records('paygw_mtnafrica', []);
        $this->assertEquals(1, $cnt);
        $task->execute();
        $cnt = $DB->count_records('paygw_mtnafrica', []);
        $this->assertEquals(0, $cnt);
    }
}
