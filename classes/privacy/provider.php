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
 * Privacy Subsystem implementation for paygw_mtnafrica.
 *
 * @package    paygw_mtnafrica
 * @copyright  Medical Access Uganda Limited (e-learning.medical-access.org)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\request\{writer, approved_contextlist, contextlist, core_userlist_provider, approved_userlist};
use core_privacy\local\request\{userlist, transform, deletion_criteria};
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use stdClass;

/**
 * Privacy Subsystem implementation for paygw_mtnafrica.
 *
 * @package    paygw_mtnafrica
 * @copyright  Medical Access Uganda Limited (e-learning.medical-access.org)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements core_userlist_provider, metadata_provider, paygw_provider, plugin_provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $arr = [
            'userid' => 'privacy:metadata:paygw_mtnafrica:userid',
            'transactionid' => 'privacy:metadata:paygw_mtnafrica:transactionid',
            'paymentid' => 'privacy:metadata:paygw_mtnafrica:paymentid',
            'moneyid' => 'privacy:metadata:paygw_mtnafrica:moneyid',
            'timecreated' => 'privacy:metadata:paygw_mtnafrica:timecreated',
            'timecompleted' => 'privacy:metadata:paygw_mtnafrica:timecompleted',
        ];
        $collection->add_database_table('paygw_mtnafrica', $arr, 'privacy:metadata:paygw_mtnafrica');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT id
                  FROM {context}
                 WHERE instanceid = :userid AND contextlevel = :contextlevel";
        $contextlist = new contextlist();
        $contextlist->set_component('paygw_mtnafrica');
        $contextlist->add_from_sql($sql, ['userid' => $userid, 'contextlevel' => CONTEXT_USER]);
        return $contextlist;
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $contexts = $contextlist->get_contexts();
        foreach ($contexts as $context) {
            if ($context->contextlevel == CONTEXT_USER) {
                $user = $contextlist->get_user();
                $records = $DB->get_records('paygw_mtnafrica', ['userid' => $user->id]);
                foreach ($records as $data) {
                    unset($data->id);
                    $data->timecompleted = transform::datetime($data->timecompleted);
                    $data->timecreated = transform::datetime($data->timecreated);
                    writer::with_context($context)->export_data([], $data);
                }
            }
        }
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!is_a($context, \context_user::class)) {
            return;
        }

        $params = ['contextlevel' => CONTEXT_USER, 'contextid' => $context->id];
        $sql = 'SELECT instanceid AS userid
                  FROM {context}
                 WHERE id = :contextid AND contextlevel = :contextlevel';
        $userlist->add_from_sql('userid', $sql, $params);
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if (is_a($context, \context_user::class)) {
             $DB->delete_records('paygw_mtnafrica', ['userid' => $context->instanceid]);
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $user = $contextlist->get_user();
        $DB->delete_records('paygw_mtnafrica', ['userid' => $user->id]);
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if (is_a($context, \context_user::class)) {
            [$insql, $inparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $DB->delete_records_select('paygw_mtnafrica', "userid {$insql}", $inparams);
        }
    }

    #[\Override]
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment): void {
        global $DB;
        $subcontext[] = get_string('gatewayname', 'paygw_mtnafrica');
        if ($record = $DB->get_record('paygw_mtnafrica', ['paymentid' => $payment->paymentid])) {
            $data = new stdClass();
            $data->userid = $record->userid;
            $data->orderid = $record->moneyid;
            $data->transactionid = $record->transactionid;
            writer::with_context($context)->export_data($subcontext, $data);
        }
    }

    #[\Override]
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams): void {
        global $DB;
        $DB->delete_records_select('paygw_mtnafrica', "paymentid IN ({$paymentsql})", $paymentparams);
    }
}
