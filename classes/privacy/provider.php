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
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica\privacy;

use core_payment\privacy\paygw_provider;
use core_privacy\local\request\{writer, approved_contextlist, contextlist, core_userlist_provider, approved_userlist};
use core_privacy\local\request\{userlist, transform, deletion_criteria};
use core_privacy\local\metadata\collection;

/**
 * Privacy Subsystem implementation for paygw_mtnafrica.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    core_userlist_provider,
    paygw_provider,
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns meta data about this system.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
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

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT id
                  FROM {context}
                 WHERE instanceid = :userid AND contextlevel = :contextlevel";
        $contextlist = new contextlist();
        $contextlist->set_component('paygw_mtnafrica');
        $contextlist->add_from_sql($sql, ['userid' => $userid, 'contextlevel' => CONTEXT_USER]);
        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $contexts = $contextlist->get_contexts();
        if (count($contexts) > 0) {
            $context = reset($contexts);
            if ($context->contextlevel == CONTEXT_USER) {
                $user = $contextlist->get_user();
                if ($records = $DB->get_records('paygw_mtnafrica', ['userid' => $user->id])) {
                    foreach ($records as $data) {
                        unset($data->id);
                        $data->timecompleted = transform::datetime($data->timecompleted);
                        $data->timecreated = transform::datetime($data->timecreated);
                        writer::with_context($context)->export_data([], $data);
                    }
                }
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     *
     */
    public static function get_users_in_context(userlist $userlist) {
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

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if (is_a($context, \context_user::class)) {
             $DB->delete_records('paygw_mtnafrica', ['userid' => $context->instanceid]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $DB->delete_records('paygw_mtnafrica', ['userid' => $user->id]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist    $userlist The approved context and user information to delete information for.
     *
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        if (is_a($context, \context_user::class)) {
            [$insql, $inparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $DB->delete_records_select('paygw_mtnafrica', "userid $insql", $inparams);
        }
    }

    /**
     * Export all user data for the specified payment record, and the given context.
     *
     * @param \context $context Context
     * @param array $subcontext The location within the current context that the payment data belongs
     * @param \stdClass $payment The payment record
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;
        $subcontext[] = get_string('gatewayname', 'paygw_mtnafrica');
        if ($record = $DB->get_record('paygw_mtnafrica', ['paymentid' => $payment->paymentid])) {
            $data = ['userid' => $record->userid, 'orderid' => $record->moneyid, 'transactionid' => $record->transactionid];
            writer::with_context($context)->export_data($subcontext, (object)$data);
        }
    }

    /**
     * Delete all user data related to the given payments.
     *
     * @param string $paymentsql SQL query that selects payment.id field for the payments
     * @param array $paymentparams Array of parameters for $paymentsql
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;
        $DB->delete_records_select('paygw_mtnafrica', "paymentid IN ({$paymentsql})", $paymentparams);
    }
}
