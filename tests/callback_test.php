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

/**
 * Testing generator in payments API
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class callback_test extends \advanced_testcase {
    /** @var \core_payment\account account */
    private $account;

    /** @var config configuration */
    private $config;
    /**
     * Setup function.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        set_config('country', 'UG');
        $generator = $this->getDataGenerator()->get_plugin_generator('core_payment');
        $this->account = $generator->create_payment_account(['gateways' => 'mtnafrica']);
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
    }

    /**
     * Test callback
     * @covers \paygw_mtnafrica\mtn_helper
     */
    public function test_callback(): void {
        if ($this->config['secret'] == '') {
            $this->markTestSkipped('No login credentials');
        }
        $location = \paygw_mtnafrica\mtn_helper::get_hostname();
        $location .= '/payment/gateway/mtnafrica/callback.php';
        $data = [
            'financialTransactionId' => 2026118745,
            'externalId' => 2362616710,
            'amount' => 100,
            'currency' => 'EUR',
            'payer' => ['partyIdType' => 'MSISDN', 'partyId' => '1234567'],
            'payerMessage' => 'Thanks for your payment',
            'payeeNote' => 'enrol_fee-fee-13-4',
            'status' => 'SUCCESSFUL',
        ];
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

    /**
     * Test continue.
     * @coversNothing
     */
    public function test_continue(): void {
        if ($this->config['secret'] == '') {
            $this->markTestSkipped('No login credentials');
        }
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $data = [
            'courseid' => $course->id,
            'customint1' => $this->account->get('id'),
            'cost' => 100,
            'currency' => 'EUR',
            'roleid' => 5,
        ];
        $feeplugin = enrol_get_plugin('fee');
        $itemid = $feeplugin->add_instance($course, $data);

        $user = $gen->create_and_enrol($course, 'student', ['country' => 'UG', 'phone2' => '123456789'], 'fee');
        $this->setUser($user);
        $client = new \GuzzleHttp\Client();
        $data = [
            'paymentarea' => 'fee',
            'itemid' => $itemid,
        ];
        $location = \paygw_mtnafrica\mtn_helper::get_hostname();
        $location .= '/payment/gateway/mtnafrica/continue.php';
        $response = $client->request('POST', $location, ['form_params' => $data]);
        $result = json_decode($response->getBody()->getContents(), true);
        $this->assertEmpty($result);
    }
}
