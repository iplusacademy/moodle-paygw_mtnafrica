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
final class gateway_test extends \advanced_testcase {
    /** @var \core_payment\account account */
    private $account;

    /**
     * Setup function.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        set_config('country', 'UG');
        $generator = $this->getDataGenerator()->get_plugin_generator('core_payment');
        $this->account = $generator->create_payment_account(['gateways' => 'mtnafrica']);
    }

    /**
     * Test gateway.
     * @covers \paygw_mtnafrica\gateway
     */
    public function test_gateway(): void {
        $this->assertCount(11, gateway::get_supported_currencies());
        $this->assertCount(15, gateway::get_country_currencies());
        $this->assertCount(14, gateway::get_countries());
        $errors = [];
        $gateway = $this->account->get_gateways()['mtnafrica'];
        $form = new \core_payment\form\account_gateway('', ['persistent' => $gateway]);
        $data = new \stdClass();
        $data->enabled = true;
        gateway::validate_gateway_form($form, $data, [], $errors);
        $this->assertCount(1, $errors);
    }

    /**
     * Test create account.
     * @covers \paygw_mtnafrica\gateway
     */
    public function test_create_account(): void {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('core_payment');
        $this->assertTrue($generator instanceof \core_payment_generator);
        $account1 = $generator->create_payment_account();
        $account2 = $generator->create_payment_account(['name' => 'My name', 'gateways' => 'mtnafrica']);
        $record1 = $DB->get_record('payment_accounts', ['id' => $account1->get('id')]);
        $record2 = $DB->get_record('payment_accounts', ['id' => $account2->get('id')]);

        $this->assertEquals(1, $record1->enabled);
        $this->assertEquals('My name', $record2->name);
        // First account does not have gateways configurations.
        $this->assertEmpty($DB->get_records('payment_gateways', ['accountid' => $account1->get('id')]));
        // Second account has.
        $this->assertCount(1, $DB->get_records('payment_gateways', ['accountid' => $account2->get('id')]));
    }

    /**
     * Test create payment.
     * @covers \paygw_mtnafrica\gateway
     */
    public function test_create_payment(): void {
        global $DB;
        $generator = $this->getDataGenerator()->get_plugin_generator('core_payment');
        $user = $this->getDataGenerator()->create_user(['phone1' => '888888888']);
        $paymentid = $generator->create_payment(['accountid' => $this->account->get('id'), 'amount' => 10, 'userid' => $user->id]);
        $this->assertEquals('testcomponent', $DB->get_field('payments', 'component', ['id' => $paymentid]));
    }

    /**
     * Test for get_payable().
     *
     * @covers \paygw_mtnafrica\gateway
     */
    public function test_get_payable(): void {
        $feeplugin = enrol_get_plugin('fee');
        $course = $this->getDataGenerator()->create_course();
        $data = [
            'courseid' => $course->id,
            'customint1' => $this->account->get('id'),
            'cost' => 250,
            'currency' => 'USD',
            'roleid' => 5,
        ];
        $id = $feeplugin->add_instance($course, $data);
        $payable = \enrol_fee\payment\service_provider::get_payable('fee', $id);
        $this->assertEquals($this->account->get('id'), $payable->get_account_id());
        $this->assertEquals(250, $payable->get_amount());
        $this->assertEquals('USD', $payable->get_currency());
    }
}
