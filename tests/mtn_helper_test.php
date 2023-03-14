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
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica;

/**
 * Testing generator in payments API
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mtn_helper_test extends \advanced_testcase {

    /** @var string phone */
    private $phone = '46733123454';

    /** @var string login */
    private $login;

    /** @var string secret */
    private $secret;

    /** @var string secret */
    private $secret1;

    /**
     * Setup function.
     */
    protected function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        set_config('country', 'UG');
        $this->login = getenv('login') ? getenv('login') : 'fakelogin';
        $this->secret = getenv('secret') ? getenv('secret') : 'fakesecret';
        $this->secret1 = getenv('secret1') ? getenv('secret1') : 'fakesecret';

        $config = new \stdClass();
        $config->clientid = $this->login;
        $config->brandname = 'maul';
        $config->environment = 'sandbox';
        $config->secret = $this->secret;
        $config->secret1 = $this->secret1;
        $config->country = 'UG';
        $DB->set_field('payment_gateways', 'config', json_encode($config), []);
    }

    /**
     * Test MTN Africa helper
     * @covers \paygw_mtnafrica\mtn_helper
     * @covers \paygw_mtnafrica\event\request_log
     */
    public function test_empty_helper() {
        $helper = new \paygw_mtnafrica\mtn_helper('fakelogin', 'user', 'fake');
        $this->assertEquals(get_class($helper), 'paygw_mtnafrica\mtn_helper');
        $this->assertEquals('Accepted', $helper->ta_code(202));
        $this->assertEquals('sandbox', $helper->target_code('BE'));
        $random = random_int(1000000000, 9999999999);
        try {
            $helper->request_payment($random, "fee 13 4", 100, 'EUR', '1234567', 'BE');
        } catch (\moodle_exception $e) {
            $this->assertStringContainsString('Invalid country code', $e->getmessage());
        }
    }

    /**
     * Test using datasource for MTN Africa payment
     * @param string $input
     * @param string $output
     * @covers \paygw_mtnafrica\mtn_helper
     * @covers \paygw_mtnafrica\event\request_log
     * @covers \paygw_mtnafrica\external\transaction_complete
     * @dataProvider provide_user_data
     */
    public function test_with_dataprovider(string $input, string $output) {
        if ($this->login == 'fakelogin') {
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
        $helper = new \paygw_mtnafrica\mtn_helper($this->login, $this->secret, $this->secret1);
        $userid = $user->id;
        $result = $helper->request_payment($random, "fee $feeid $userid", 10, 'EUR', $input, 'UG');
        $xref = $result['xreferenceid'];
        $token = $result['token'];
        $result = $helper->transaction_enquiry($xref, $token);
        $output = strtoupper($output);
        $this->assertEquals($output, $result['status']);
        if ($result['status'] == 'PENDING' && $input == '46733123454') {
            for ($i = 1; $i < 11; $i++) {
                sleep(15);
                $result = $helper->transaction_enquiry($xref, $token);
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
    public function provide_user_data(): array {
        return [
            'Failed' => ['46733123450', 'failed'],
            'Rejected' => ['46733123451', 'failed'],
            'Timeout' => ['46733123452', 'failed'],
            'Ongoing' => ['46733123453', 'pending'],
            'Pending' => ['46733123454', 'pending'],
            'Succes' => ['46733123455', 'failed']];
    }

    /**
     * Test manual MTN Africa payment
     * @covers \paygw_mtnafrica\mtn_helper
     * @covers \paygw_mtnafrica\event\request_log
     */
    public function test_mtn_manualy() {
        if ($this->login == 'fakelogin') {
            $this->markTestSkipped('No login credentials');
        }
        $user = $this->getDataGenerator()->create_user(['country' => 'UG', 'phone2' => $this->phone]);
        $this->setUser($user);
        $random = random_int(1000000000, 9999999999);
        $helper = new \paygw_mtnafrica\mtn_helper($this->login, $this->secret, $this->secret1);
        $result = $helper->request_payment($random, "fee 13 $user->id", 1000, 'EUR', $this->phone, 'UG');
        $this->assertEquals(202, $result['code']);
        $result = $helper->transaction_enquiry($result['xreferenceid'], $result['token']);
        $this->assertEquals('PENDING', $result['status']);
        $this->assertTrue = $helper->valid_user($this->phone);
    }

    /**
     * Test error codes
     * @covers \paygw_mtnafrica\mtn_helper
     * @covers \paygw_mtnafrica\event\request_log
     */
    public function test_mtn_codes() {
        if ($this->login == 'fakelogin') {
            $this->markTestSkipped('No login credentials');
        }
        $phone = '46733123451';
        $user = $this->getDataGenerator()->create_user(['country' => 'UG', 'phone2' => $phone]);
        $this->setUser($user);
        $random = random_int(1000000000, 9999999999);
        $helper = new \paygw_mtnafrica\mtn_helper($this->login, $this->secret, $this->secret1);

        $result = $helper->request_payment($random, "fee 13 $user->id", 1000, 'EUR', $phone, 'UG');
        $this->assertEquals(202, $result['code']);
        $result = $helper->transaction_enquiry($result['xreferenceid'], $result['token']);
        $this->assertEquals('FAILED', $result['status']);
    }

    /**
     * Test callback
     * @covers \paygw_mtnafrica\mtn_helper
     */
    public function test_callback() {
        if ($this->login != 'fakelogin') {
            $this->markTestSkipped('No login credentials');
        }
        // TODO: we should use an external server to test out the callback.
        $location = 'https://test.ewallah.net/payment/gateway/mtnafrica/callback.php';
        $data = ['transaction' => [
           'id' => 'BBZMiscxy',
           'message' => 'Paid UGX 5,000 to MAUL, Charge UGX 140, Trans ID MP210603.1234.L06941.',
           'status_code' => 'TS',
           'mtn_money_id' => 'MP210603.1234.L06941']];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_PROXY, $location);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_VERBOSE, false);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_URL, $location);
        $result = curl_exec($curl);
        $this->assertStringNotContainsString('MAUL', $result);
        @curl_close($curl);
    }
}
