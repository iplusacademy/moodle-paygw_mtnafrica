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
 * Testing generator in payments API
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica;

use paygw_mtnafrica\mtn_helper;
/**
 * Testing generator in payments API
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class mtn_helper_test extends \advanced_testcase {
    /** @var config configuration */
    private $config;

    /**
     * Setup function.
     */
    protected function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('country', 'UG');
        $secret = getenv('secret', true) ?: getenv('secret');
        $secret1 = getenv('secret1', true) ?: getenv('secret1');
        $this->config = [
            'brandname' => 'maul',
            'environment' => 'sandbox',
            'clientid' => 'fakelogin',
            'apikey' => 'fakeapikey',
            'secret' => $secret,
            'secret1' => $secret1,
        ];
        $DB->set_field('payment_gateways', 'config', json_encode($this->config), []);
    }

    /**
     * Test MTN Africa helper
     * @covers \paygw_mtnafrica\mtn_helper
     */
    public function test_empty_helper(): void {
        $this->assertEquals('Accepted', mtn_helper::ta_code(202));
        $this->assertEquals('sandbox', mtn_helper::target_code('BE'));
        $this->assertNotEmpty(mtn_helper::get_hostname());

        $this->assertEquals(null, mtn_helper::array_helper('BE', ['FR' => 'France']));
        $this->assertEquals('Belgium', mtn_helper::array_helper('BE', ['BE' => 'Belgium']));

        $arr = ['FR' => 'France', 'BE' => 'Belgium'];
        $this->assertEquals(null, mtn_helper::array_helper('BE', ['countries' => $arr]));
        $this->assertEquals($arr, mtn_helper::array_helper('countries', ['countries' => $arr]));

        $key = 'user<script>alert(1);</script>xss';
        $obj = (object)['name' => $key, $key => 'heslo', 'email' => 'xssuser@example.com'];
        $arr = (array)$obj;
        $this->assertEquals(null, mtn_helper::array_helper('name', ['countries' => $arr]));
        $this->assertEquals(null, mtn_helper::array_helper($key, ['countries' => $arr]));
        $this->assertEquals('me', mtn_helper::array_helper('name', ['name' => 'me', $key => $arr]));
    }

    /**
     * Test MTN Africa helper
     * @covers \paygw_mtnafrica\mtn_helper
     * @covers \paygw_mtnafrica\event\request_log
     */
    public function test_other_helper(): void {
        global $DB;
        if ($this->config['secret'] == '') {
            $this->markTestSkipped('No login credentials');
        }
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'mtnafrica']);
        $course = $generator->create_course();
        $user = $generator->create_user(['country' => 'UG', 'phone2' => '123456789']);
        $data = ['courseid' => $course->id, 'customint1' => $account->get('id'), 'cost' => 100, 'currency' => 'EUR', 'roleid' => 5];
        $feeplugin = enrol_get_plugin('fee');
        $feeid = $feeplugin->add_instance($course, $data);
        $this->setUser($user);
        $mtnhelper = new mtn_helper($this->config);
        $this->assertEquals(get_class($mtnhelper), 'paygw_mtnafrica\mtn_helper');
        $random = random_int(1000000000, 9999999999);
        try {
            $mtnhelper->request_payment($random, 'enrol_fee-fee-13-' . $user->id, 100, 'EUR', '1234567', 'BE');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('Invalid country code', $e->getmessage());
        }
        $result = $mtnhelper->request_payment($random, 'enrol_fee-fee-13-' . $user->id, 100, 'EUR', '123456789', 'UG');
        $xref = $result['xreferenceid'];
        $token = $result['token'];
        $mtnhelper->transaction_enquiry($xref, $token);
        $mtnhelper->valid_user('123456789');
        $result = $mtnhelper->current_user_data();
        $this->assertEquals('123456789', $result['phone']);
        $data = new \stdClass();
        $data->paymentid = $feeid;
        $data->userid = $user->id;
        $data->transactionid = $xref;
        $data->moneyid = $token;
        $data->timecreated = time();
        $DB->insert_record('paygw_mtnafrica', $data);
        $mtnhelper->enrol_user($xref, $feeid, 'enrol_fee', 'fee');
    }

    /**
     * Test using datasource for MTN Africa payment
     * @param string $input
     * @param string $output
     * @param string $reason
     * @covers \paygw_mtnafrica\mtn_helper
     * @covers \paygw_mtnafrica\event\request_log
     * @covers \paygw_mtnafrica\external\transaction_complete
     * @dataProvider provide_user_data
     */
    public function test_with_dataprovider(string $input, string $output, string $reason = ''): void {
        if ($this->config['secret'] == '') {
            $this->markTestSkipped('No login credentials');
        }
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'mtnafrica']);
        $course = $generator->create_course();
        $user = $generator->create_user(['country' => 'UG', 'phone2' => $input]);
        $data = ['courseid' => $course->id, 'customint1' => $account->get('id'), 'cost' => 66, 'currency' => 'EUR', 'roleid' => 5];
        $feeplugin = enrol_get_plugin('fee');
        $feeid = $feeplugin->add_instance($course, $data);
        $this->setUser($user);
        $random = random_int(1000000000, 9999999999);
        $mtnhelper = new mtn_helper($this->config);
        $userid = $user->id;
        $result = $mtnhelper->request_payment($random, "enrol_fee-fee-$feeid-$userid", 10, 'EUR', $input, 'UG');
        $xref = $result['xreferenceid'];
        $token = $result['token'];
        $result = $mtnhelper->transaction_enquiry($xref, $token);
        $output = strtoupper($output);
        $this->assertEquals($output, $result['status']);
        if ($result['status'] == 'FAILED') {
            $this->assertEquals($reason, $result['reason']);
        }
        if ($result['status'] == 'PENDING' && $input == '46733123454') {
            for ($i = 1; $i < 11; $i++) {
                sleep(16);
                $result = $mtnhelper->transaction_enquiry($xref, $token);
                if ($result['status'] == 'SUCCESSFUL') {
                    $this->assertEquals('EUR', $result['currency']);
                    break;
                } else {
                    $this->assertEquals($output, $result['status']);
                }
            }
        }
    }

    /**
     * Data to test
     * @return string[][]
     */
    public static function provide_user_data(): array {
        return [
            'Failed' => ['46733123450', 'failed', 'INTERNAL_PROCESSING_ERROR'],
            'Rejected' => ['46733123451', 'failed', 'APPROVAL_REJECTED'],
            'Timeout' => ['46733123452', 'failed', 'EXPIRED'],
            'Ongoing' => ['46733123453', 'pending'],
            'Pending' => ['46733123454', 'pending'],
            'Succes' => ['46733123999', 'successful', 'SUCCESSFUL'],
        ];
    }

    /**
     * Test success codes
     * @covers \paygw_mtnafrica\mtn_helper
     * @covers \paygw_mtnafrica\event\request_log
     */
    public function test_mtn_codes(): void {
        if ($this->config['secret'] == '') {
            $this->markTestSkipped('No login credentials');
        }
        $phone = '56733123999';
        $user = $this->getDataGenerator()->create_user(['country' => 'UG', 'phone2' => $phone]);
        $this->setUser($user);
        $random = random_int(1000000000, 9999999999);
        $mtnhelper = new mtn_helper($this->config);
        $result = $mtnhelper->request_payment($random, "enrol_fee-fee-14-$user->id", 11, 'EUR', $phone, 'UG');
        $this->assertEquals(202, $result['code']);
        $result = $mtnhelper->transaction_enquiry($result['xreferenceid'], $result['token']);
        $this->assertEquals('SUCCESSFUL', $result['status']);
        $this->assertEquals(11, $result['amount']);
        $this->assertEquals('EUR', $result['currency']);
        $this->assertEquals("enrol_fee-fee-14-$user->id", $result['payeeNote']);
    }
}
