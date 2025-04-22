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
 * @copyright  Medical Access Uganda Limited (e-learning.medical-access.org)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica\privacy;

use context_system;
use context_user;
use core_payment\helper;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\{approved_userlist, approved_contextlist, userlist, writer};
use core_privacy\tests\provider_testcase;
use paygw_mtnafrica\privacy\provider;
use stdClass;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Privacy provider test for payment gateway mtnafrica.
 *
 * @package    paygw_mtnafrica
 * @copyright  Medical Access Uganda Limited (e-learning.medical-access.org)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
final class provider_test extends provider_testcase {
    /** @var stdClass A student. */
    protected $user;

    /** @var stdClass A payment record. */
    protected $payrec;

    /**
     * Basic setup for these tests.
     */
    public function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $account = $generator->get_plugin_generator('core_payment')->create_payment_account(['gateways' => 'mtnafrica']);
        $user = $generator->create_user();
        $id = $generator->get_plugin_generator('core_payment')->create_payment(
            ['accountid' => $account->get('id'), 'amount' => 1, 'gateway' => 'mtnafrica', 'userid' => $user->id]
        );
        $feeplugin = enrol_get_plugin('fee');
        $course = $generator->create_course();
        $data = [
            'courseid' => $course->id,
            'customint1' => $id,
            'cost' => 250,
            'currency' => 'USD',
            'roleid' => 5,
        ];
        $feeid = $feeplugin->add_instance($course, $data);
        $saved = helper::save_payment($id, 'enrol_fee', 'fee', $feeid, $user->id, 30, 'EUR', 'mtnafrica');
        helper::deliver_order('enrol_fee', 'fee', $feeid, $saved, $user->id);
        $data = new stdClass();
        $data->paymentid = $id;
        $data->userid = 2;
        $data->transactionid = '666666665';
        $data->moneyid = 'firstbadmoneyid';
        $data->timecreated = time();
        $data->component = 'mtnafrica';
        $data->paymentarea = 'fee';
        $DB->insert_record('paygw_mtnafrica', $data);

        $paygen = $generator->get_plugin_generator('core_payment');
        $account = $paygen->create_payment_account(['gateways' => 'mtnafrica']);
        $accountid = $account->get('id');
        $user = $generator->create_user();
        $paygen->create_payment(
            ['accountid' => $accountid, 'amount' => 2, 'gateway' => 'mtnafrica', 'userid' => $user->id]
        );
        $this->user = $generator->create_user();
        $id = $paygen->create_payment(
            ['accountid' => $accountid, 'amount' => 1, 'gateway' => 'mtnafrica', 'userid' => $this->user->id]
        );
        $paygen->create_payment(
            ['accountid' => $accountid, 'amount' => 3, 'gateway' => 'airtelafrica', 'userid' => $this->user->id]
        );
        $data = new stdClass();
        $data->paymentid = $id;
        $data->userid = $this->user->id;
        $data->transactionid = '666666666';
        $data->moneyid = 'badmoneyid';
        $data->timecreated = time();
        $data->component = 'mtnafrica';
        $data->paymentarea = 'fee';
        $pid = $DB->insert_record('paygw_mtnafrica', $data);
        $data->id = $pid;
        $this->payrec = $data;
    }

    /**
     * Test returning metadata.
     */
    public function test_get_metadata(): void {
        $collection = new collection('paygw_mtnafrica');
        $metadata = provider::get_metadata($collection);
        $this->assertInstanceOf('core_privacy\local\metadata\collection', $metadata);
        $this->assertEquals('paygw_mtnafrica', $metadata->get_component());
        $table = $metadata->get_collection();
        $table = reset($table);
        $this->assertInstanceOf('core_privacy\local\metadata\types\database_table', $table);
        $in = [
            'userid' => 'privacy:metadata:paygw_mtnafrica:userid',
            'transactionid' => 'privacy:metadata:paygw_mtnafrica:transactionid',
            'paymentid' => 'privacy:metadata:paygw_mtnafrica:paymentid',
            'moneyid' => 'privacy:metadata:paygw_mtnafrica:moneyid',
            'timecreated' => 'privacy:metadata:paygw_mtnafrica:timecreated',
            'timecompleted' => 'privacy:metadata:paygw_mtnafrica:timecompleted',
        ];
        $this->assertEquals('paygw_mtnafrica', $table->get_name());
        $this->assertEquals('privacy:metadata:paygw_mtnafrica', $table->get_summary());
        $this->assertEquals($in, $table->get_privacy_fields());
    }

    /**
     * Test for provider.
     */
    public function test_provider(): void {
        global $DB;
        $this->assertEquals(2, $DB->count_records('paygw_mtnafrica', []));
        $context = context_user::instance($this->user->id);
        $contextlist = provider::get_contexts_for_userid($this->user->id);
        $this->assertInstanceOf('core_privacy\local\request\contextlist', $contextlist);
        $this->assertEquals('paygw_mtnafrica', $contextlist->get_component());
        $this->assertEquals([$context], $contextlist->get_contexts());
        $list = new approved_contextlist($this->user, 'paygw_mtnafrica', [$context->instanceid]);
        $this->assertInstanceOf('core_privacy\local\request\approved_contextlist', $list);
        $this->assertEquals($list->get_user(), $this->user);
        provider::delete_data_for_user($list);
        $this->assertEquals(1, $DB->count_records('paygw_mtnafrica', []));
        $user = $this->create_user_with_payment();
        $context = context_user::instance($user->id);
        provider::delete_data_for_all_users_in_context($context);
        $this->assertEquals(2, $DB->count_records('paygw_mtnafrica', []));
        $user = $this->create_user_with_payment();
        $context = context_user::instance($user->id);
        $list = new approved_contextlist($user, 'paygw_mtnafrica', [$context->instanceid]);
        $this->assertInstanceOf('core_privacy\local\request\approved_contextlist', $list);
        $this->assertEquals($list->get_user(), $user);
        $this->assertEquals(null, provider::export_payment_data(context_system::instance(), ['course'], $this->payrec));
        $this->assertEmpty(provider::delete_data_for_payment_sql($this->payrec->paymentid, []));
        $this->assertEquals(4, $DB->count_records('paygw_mtnafrica', []));
    }

    /**
     * Test for remove.
     */
    public function test_remove(): void {
        global $DB;
        provider::export_payment_data(context_system::instance(), ['course'], $this->payrec);
        $this->assertEmpty(provider::delete_data_for_payment_sql($this->payrec->paymentid, []));
        $this->assertEquals(1, $DB->count_records('paygw_mtnafrica', []));
    }


    /**
     * Check the exporting of payments for a user.
     */
    public function test_export(): void {
        $context = context_user::instance($this->user->id);
        $this->export_context_data_for_user($this->user->id, $context, 'paygw_mtnafrica');
        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
        $this->export_all_data_for_user($this->user->id, 'paygw_mtnafrica');
        $data = $writer->get_data();
        $this->assertEquals($data->userid, $this->user->id);
        $this->assertEquals($data->moneyid, 'badmoneyid');
        $this->assertEquals($data->transactionid, '666666666');
    }

    /**
     * Tests new functions.
     */
    public function test_new_functions(): void {
        global $DB;
        $context = context_user::instance($this->user->id);
        $userlist = new userlist($context, 'paygw_mtnafrica');
        $this->assertInstanceOf('core_privacy\local\request\userlist', $userlist);
        provider::get_users_in_context($userlist);
        $this->assertCount(1, $userlist);

        $scontext = context_system::instance();
        $userlist = new userlist($scontext, 'paygw_mtnafrica');
        provider::get_users_in_context($userlist);
        $this->assertCount(0, $userlist);

        $approved = new approved_userlist($context, 'paygw_mtnafrica', [$this->user->id]);
        $this->assertInstanceOf('core_privacy\local\request\approved_userlist', $approved);
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


    /**
     * Create a user with a payment.
     * @return stdClass user
     */
    private function create_user_with_payment(): stdClass {
        global $DB;
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
        $data->component = 'mtnafrica';
        $data->paymentarea = 'fee';
        $data->timecompleted = time();
        $DB->insert_record('paygw_mtnafrica', $data);

        $user2 = $generator->create_user();
        $id = $generator->get_plugin_generator('core_payment')->create_payment(
            ['accountid' => $account->get('id'), 'amount' => 2, 'gateway' => 'mtnafrica', 'userid' => $user2->id]
        );
        $data = new stdClass();
        $data->paymentid = $id;
        $data->userid = $user2->id;
        $data->transactionid = '666666664';
        $data->moneyid = 'secondbadmoneyid';
        $data->timecreated = time();
        $data->component = 'mtnafrica';
        $data->paymentarea = 'fee';
        $data->timecompleted = time();
        $DB->insert_record('paygw_mtnafrica', $data);
        return $user;
    }
}
