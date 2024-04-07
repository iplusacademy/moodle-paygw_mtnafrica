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
 * Privacy provider tests.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica\privacy;

use context_system;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use paygw_mtnafrica\privacy\provider;
use stdClass;

/**
 * Privacy provider test for payment gateway mtnafrica.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends provider_testcase {
    /** @var stdClass A student. */
    protected $user;

    /** @var stdClass A payment record. */
    protected $payrec;

    /**
     * Basic setup for these tests.
     * @covers \paygw_mtnafrica\privacy\provider
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'mtnafrica']);
        $user = $generator->create_user();
        $id = $generator->get_plugin_generator('core_payment')->create_payment(
            ['accountid' => $account->get('id'), 'amount' => 1, 'gateway' => 'mtnafrica', 'userid' => $user->id]
        );
        $data = new stdClass();
        $data->paymentid = $id;
        $data->userid = $user->id;
        $data->transactionid = '666666665';
        $data->moneyid = 'firstbadmoneyid';
        $data->timecreated = time();
        $DB->insert_record('paygw_mtnafrica', $data);

        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'mtnafrica']);
        $this->user = $generator->create_user();
        $id = $generator->get_plugin_generator('core_payment')->create_payment(
            ['accountid' => $account->get('id'), 'amount' => 1, 'gateway' => 'mtnafrica', 'userid' => $this->user->id]
        );
        $data = new stdClass();
        $data->paymentid = $id;
        $data->userid = $this->user->id;
        $data->transactionid = '666666666';
        $data->moneyid = 'badmoneyid';
        $data->timecreated = time();
        $pid = $DB->insert_record('paygw_mtnafrica', $data);
        $data->id = $pid;
        $this->payrec = $data;
    }

    /**
     * Test returning metadata.
     * @covers \paygw_mtnafrica\privacy\provider
     */
    public function test_get_metadata(): void {
        $collection = new collection('paygw_mtnafrica');
        $this->assertNotEmpty(provider::get_metadata($collection));
    }

    /**
     * Test for provider.
     * @covers \paygw_mtnafrica\privacy\provider
     */
    public function test_provider(): void {
        global $DB;
        $this->assertEquals(2, $DB->count_records('paygw_mtnafrica', []));
        $context = context_user::instance($this->user->id);
        $contextlist = provider::get_contexts_for_userid($this->user->id);
        $this->assertCount(1, $contextlist);
        $list = new approved_contextlist($this->user, 'paygw_mtnafrica', [$context->instanceid]);
        $this->assertNotEmpty($list);
        provider::delete_data_for_user($list);
        $this->assertEquals(1, $DB->count_records('paygw_mtnafrica', []));
        $user = self::getDataGenerator()->create_user();
        $context = context_user::instance($user->id);
        provider::delete_data_for_all_users_in_context($context);
        $this->assertEquals(1, $DB->count_records('paygw_mtnafrica', []));
        $user = self::getDataGenerator()->create_user();
        $context = context_user::instance($user->id);
        $list = new approved_contextlist($user, 'paygw_mtnafrica', [$context->instanceid]);
        $this->assertNotEmpty($list);
        provider::export_payment_data(context_system::instance(), ['course'], $this->payrec);
        $this->assertEmpty(provider::delete_data_for_payment_sql($this->payrec->paymentid, []));
        $this->assertEquals(1, $DB->count_records('paygw_mtnafrica', []));
    }

    /**
     * Test for remove.
     * @covers \paygw_mtnafrica\privacy\provider
     */
    public function test_remove(): void {
        global $DB;
        provider::export_payment_data(context_system::instance(), ['course'], $this->payrec);
        $this->assertEmpty(provider::delete_data_for_payment_sql($this->payrec->paymentid, []));
        $this->assertEquals(1, $DB->count_records('paygw_mtnafrica', []));
    }


    /**
     * Check the exporting of payments for a user.
     * @covers \paygw_mtnafrica\privacy\provider
     */
    public function test_export(): void {
        $context = context_user::instance($this->user->id);
        $this->export_context_data_for_user($this->user->id, $context, 'paygw_mtnafrica');
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $this->export_all_data_for_user($this->user->id, 'paygw_mtnafrica');
    }

    /**
     * Tests new functions.
     * @covers \paygw_mtnafrica\privacy\provider
     */
    public function test_new_functions(): void {
        global $DB;
        $context = context_user::instance($this->user->id);
        $userlist = new userlist($context, 'paygw_mtnafrica');
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist);

        $scontext = context_system::instance();
        $userlist = new userlist($scontext, 'paygw_mtnafrica');
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);

        $approved = new approved_userlist($context, 'paygw_mtnafrica', [$this->user->id]);
        $this->assertCount(1, $approved);
        $this->assertEquals($context, $approved->get_context());
        $this->assertCount(1, $approved->get_userids());
        $this->assertEquals(2, $DB->count_records('paygw_mtnafrica', []));
        provider::delete_data_for_users($approved);
        $this->assertEquals(1, $DB->count_records('paygw_mtnafrica', []));

        $userlist = new userlist($context, 'paygw_mtnafrica');
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist);
        $this->assertEquals($context, $userlist->get_context());
        $this->assertCount(1, $userlist->get_userids());
    }
}
