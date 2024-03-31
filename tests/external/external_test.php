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
 * Testing externals in payments API
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica\external;

use core_external;

/**
 * Testing externals in payments API
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @runTestsInSeparateProcesses
 */
final class external_test extends \advanced_testcase {
    /** @var string phone */
    private $phone = '46733123454';

    /** @var int feeid. */
    private $feeid;

    /** @var string login */
    private $login;

    /**
     * Tests initial setup.
     *
     */
    protected function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'mtnafrica']);
        $course = $generator->create_course();
        $user = $generator->create_user(['country' => 'UG', 'phone2' => $this->phone]);
        $data = ['courseid' => $course->id, 'customint1' => $account->get('id'), 'cost' => 66, 'currency' => 'EUR', 'roleid' => 5];
        $feeplugin = enrol_get_plugin('fee');
        $secret = getenv('secret', true) ?: getenv('secret');
        $secret1 = getenv('secret1', true) ?: getenv('secret1');
        $this->feeid = $feeplugin->add_instance($course, $data);
        $this->login = $secret == '' ? 'fakelogin' : 'maul';
        $config = new \stdClass();
        $config->clientid = $this->login;
        $config->brandname = 'maul';
        $config->environment = 'sandbox';
        $config->apikey = 'fakeapikey';
        $config->secret = $secret;
        $config->secret1 = $secret1;
        $config->country = 'UG';
        $DB->set_field('payment_gateways', 'config', json_encode($config), []);
        $this->setUser($user);
    }

    /**
     * Test external config for js.
     * @covers \paygw_mtnafrica\external\get_config_for_js
     */
    public function test_config_for_js(): void {
        $this->assertInstanceOf('core_external\external_function_parameters', get_config_for_js::execute_parameters());
        $this->assertInstanceOf('core_external\external_single_structure', get_config_for_js::execute_returns());
    }

    /**
     * Test external config for js with credits.
     * @covers \paygw_mtnafrica\external\get_config_for_js
     */
    public function test_config_for_jscredits(): void {
        if ($this->login == 'fakelogin') {
            $this->markTestSkipped('No login credentials');
        }
        $result = get_config_for_js::execute('enrol_fee', 'fee', $this->feeid);
        $this->assertEquals('UG', $result['country']);
    }

    /**
     * Test external transaction_start.
     * @covers \paygw_mtnafrica\external\transaction_start
     */
    public function test_transaction_start(): void {
        $this->assertInstanceOf('core_external\external_function_parameters', transaction_start::execute_parameters());
        $this->assertInstanceOf('core_external\external_single_structure', transaction_start::execute_returns());
    }

    /**
     * Test external transaction_start with credits.
     * @covers \paygw_mtnafrica\external\transaction_start
     */
    public function test_transaction_startcredits(): void {
        global $USER;
        if ($this->login == 'fakelogin') {
            $this->markTestSkipped('No login credentials');
        }
        $result = transaction_start::execute('enrol_fee', 'fee', $this->feeid, 'random', $this->phone, $USER->country);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test external transaction complete.
     * @covers \paygw_mtnafrica\external\transaction_complete
     */
    public function test_transaction_complete(): void {
        $this->assertInstanceOf('core_external\external_function_parameters', transaction_complete::execute_parameters());
        $this->assertInstanceOf('core_external\external_single_structure', transaction_complete::execute_returns());
    }

    /**
     * Test external transaction complete with valid credits.
     * @covers \paygw_mtnafrica\external\transaction_complete
     */
    public function test_transaction_completecredits(): void {
        global $USER;
        if ($this->login == 'fakelogin') {
            $this->markTestSkipped('No login credentials');
        }
        $result = transaction_start::execute('enrol_fee', 'fee', $this->feeid, 'random', $this->phone, $USER->country);
        $this->assertArrayHasKey('transactionid', $result);
        $xref = $result['transactionid'];
        $result = transaction_complete::execute('enrol_fee', 'fee', $this->feeid, $xref);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Test complete cycle.
     * @covers \paygw_mtnafrica\external\get_config_for_js
     * @covers \paygw_mtnafrica\external\transaction_start
     * @covers \paygw_mtnafrica\external\transaction_complete
     */
    public function test_complete_cycle(): void {
        if ($this->login == 'fakelogin') {
            $this->markTestSkipped('No login credentials');
        }
        $random = random_int(1000000000, 9999999999);
        $result = get_config_for_js::execute('enrol_fee', 'fee', $this->feeid);
        $this->assertArrayHasKey('clientid', $result);
        $this->assertEquals('maul', $result['brandname']);
        $this->assertEquals('UG', $result['country']);
        $this->assertEquals(66, $result['cost']);
        $this->assertEquals('EUR', $result['currency']);
        $this->assertEquals($this->phone, $result['phone']);
        $this->assertEquals('UG', $result['usercountry']);
        $this->assertArrayHasKey('reference', $result);
        $result = transaction_start::execute('enrol_fee', 'fee', $this->feeid, $random, $result['phone'], $result['country']);
        $this->assertArrayHasKey('transactionid', $result);
        $xref = $result['transactionid'];
        $this->assertEquals('Accepted', $result['message']);

        for ($i = 1; $i < 6; $i++) {
            sleep(16);
            $result = transaction_complete::execute('enrol_fee', 'fee', $this->feeid, $xref);
            $this->assertArrayHasKey('success', $result);
            $this->assertArrayHasKey('message', $result);
            if ($result['success']) {
                break;
            }
        }
    }


    /**
     * Test request log.
     * @covers \paygw_mtnafrica\event\request_log
     * @covers \paygw_mtnafrica\mtn_helper
     */
    public function test_request_log(): void {
        global $DB;
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $this->setUser($user);
        $configs = $DB->get_records('payment_gateways');
        $config = reset($configs);
        $config = json_decode($config->config);
        \paygw_mtnafrica\event\request_log::get_name();
        $arr = [
            'context' => \context_system::instance(),
            'relateduserid' => $user->id,
            'other' => [
                'currentcy' => 'EUR',
                'amount' => 66,
                'orderId' => 20,
                'paymentId' => 333,
            ],
        ];
        $event = \paygw_mtnafrica\event\request_log::create($arr);
        $event->trigger();
        $event->get_description();
    }

    /**
     * Test payable.
     * @covers \paygw_mtnafrica\external\get_config_for_js
     */
    public function test_payable(): void {
        global $CFG;
        if ($this->login == 'fakelogin') {
            $this->markTestSkipped('No login credentials');
        }
        $generator = $this->getDataGenerator();
        $user = $generator->create_user(['country' => 'UG']);
        $course = $generator->create_course();
        $feeplugin = enrol_get_plugin('fee');
        $this->setUser($user);
        $paygen = $generator->get_plugin_generator('core_payment');
        $account = $paygen->create_payment_account(['gateways' => 'mtnafrica']);
        $data = ['courseid' => $course->id, 'customint1' => $account->get('id'), 'cost' => 66, 'currency' => 'EUR', 'roleid' => 5];
        $this->feeid = $feeplugin->add_instance($course, $data);

        $paymentid = $paygen->create_payment([
            'accountid' => $account->get('id'),
            'amount' => 10,
            'userid' => $user->id,
        ]);
        $payable = \enrol_fee\payment\service_provider::get_payable('fee', $this->feeid);
        $this->assertEquals($account->get('id'), $payable->get_account_id());
        $this->assertEquals(66, $payable->get_amount());
        $this->assertEquals('EUR', $payable->get_currency());
        $successurl = \enrol_fee\payment\service_provider::get_success_url('fee', $this->feeid);
        $this->assertEquals($CFG->wwwroot . '/course/view.php?id=' . $course->id, $successurl->out(false));
        $account = new \core_payment\account($payable->get_account_id());

        \enrol_fee\payment\service_provider::deliver_order('fee', $this->feeid, $paymentid, $user->id);
        $context = \context_course::instance($course->id);
        $this->assertTrue(is_enrolled($context, $user));
        $this->assertTrue(user_has_role_assignment($user->id, 5, $context->id));
    }

    /**
     * Test inserting record.
     * @covers \paygw_mtnafrica\external\transaction_start
     */
    public function test_inserting_record(): void {
        global $DB;
        $str = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSMjU2In0.eyJjbGllbnRJZCI6IjFmYmFmNmRhLTJjODQtNDc4NC1iOWQ0LTExMzc0MTIzYjllNCIsImV4cGlyZ';
        $str .= '-8-hBKkY1fo8XZpo_qyCP3BBTKi4KOW96_THSbUHn0_t1w2Noq-Xv1z1keCMY6USScv17S0lf8zMxHBg4GouB_ok1BwtiI-4LG6yy4pMQYRbiG4n';
        $str .= 'XMiOiIyMDIzLTA2LTMwVDE1OjI1OjQ1LjcwNCIsInNlc3Npb25JZCI6IjIxNzY1NTEzLWJkZjEtNGIwZC05MTFhLTMxYTNiYjU3ZDQzOSJ9.DsWH';
        $str .= 'nCTDURtza_3d7P6tTtmBKFh3YrD8Xu-bocWCW3ke9gDJ1D7DsXPQ3YIkp1Pq0Xm9L7AKDIzlYf83HW5R2ZoryG-dFXYDmJxYzO5CPMquT8LMhy0S';
        $str .= '6te5SCS-9eiHZN2vv9QCYgSIcZlMNdlysJv_wVUdgK5AQVIYvRJHtPNR7rYK8FBW5ke_mH-w8KbVW-lLibZPKroLmQ9OOZ_vzKY9GAkE8NDa0o9c';
        $str .= 'nCTDURtza_3d7P6tTtmBKFh3YrD8Xu-bocWCW3ke9gDJ1D7DsXPQ3YIkp1Pq0Xm9L7AKDIzlYf83HW5R2ZoryG-dFXYDmJxYzO5CPMquT8LMhy0S';
        $data = new \stdClass();
        $data->paymentid = '33';
        $data->userid = 2;
        $data->transactionid = '94559210-0b27-4077-98bc-56035ef472f2';
        $data->moneyid = $str;
        $data->component = 'enrol_fee';
        $data->paymentarea = 'fee';
        $data->timecreated = time();
        $DB->insert_record('paygw_mtnafrica', $data);
    }
}
