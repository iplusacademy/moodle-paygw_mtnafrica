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
 * Testing security checks
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica\check;

/**
 * Testing security checks
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mtnafrica_test extends \advanced_testcase {
    /**
     * Test checks.
     * @covers \paygw_mtnafrica\check\mtnafrica
     */
    public function test_checks(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/payment/gateway/mtnafrica/lib.php');
        $checks = paygw_mtnafrica_security_checks();
        $this->assertCount(1, $checks);
        $check = new \paygw_mtnafrica\check\mtnafrica();
        $this->assertEquals('info', $check->get_result()->get_status());
        $this->assertNull($check->get_action_link());
        $generator = $this->getDataGenerator()->get_plugin_generator('core_payment');
        $account = $generator->create_payment_account(['gateways' => 'mtnafrica']);
        $config = [
            'brandname' => 'mtn',
            'environment' => 'sandbox',
            'clientid' => 'fakelogin',
            'apikey' => 'fakeapikey',
            'secret' => 'fakesecret',
            'secret1' => 'fakesecret',
        ];
        $DB->set_field('payment_gateways', 'config', json_encode($config), []);
        $check = new \paygw_mtnafrica\check\mtnafrica();
        $this->assertEquals('critical', $check->get_result()->get_status());
        $this->assertNotEmpty($check->get_action_link());
    }
}
