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
 * Handles callback received from MTN Africa
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_DEBUG_DISPLAY', true);

// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../../config.php');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
} else {
    if ($response = json_decode(file_get_contents('php://input'), true)) {

        // Sample data:
        // [financialTransactionId] => 2026118745
        // [externalId] => 2362616710
        // [amount] => 100
        // [currency] => EUR
        // [payer] => {[partyIdType] => MSISDN, [partyId] => 1234567}
        // [payerMessage] => Thanks for your payment
        // [payeeNote] => enrol_fee-fee-13-4
        // [status] => SUCCESSFUL.

        $gateway = 'mtnafrica';
        $table = 'paygw_mtnafrica';
        $transactionid = \paygw_mtnafrica\mtn_helper::array_helper('externalId', $response);
        if ($transactionid) {
            $cond = ['transactionid' => $transactionid];
            if ($DB->record_exists($table, $cond)) {
                $payrec = $DB->get_record($table, $cond);
                $status = \paygw_mtnafrica\mtn_helper::array_helper('status', $response);
                $ramount = \paygw_mtnafrica\mtn_helper::array_helper('amount', $response);
                $rcurrency = \paygw_mtnafrica\mtn_helper::array_helper('currency', $response);
                $rmessage = \paygw_mtnafrica\mtn_helper::array_helper('payeeNote', $response);
                $exp = explode('-', $rmessage);
                $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => $exp[1], 'id' => $payrec->paymentid]);
                $eventargs = [
                    'context' => \context_course::instance($courseid),
                    'userid' => $payrec->userid,
                    'other' => [
                        'message' => $rmessage,
                        'id' => $transactionid,
                        'mtn_money_id' => $rtransid,
                    ],
                ];
                \paygw_mtnafrica\event\request_log::create($eventargs)->trigger();
                $conf = \core_payment\helper::get_gateway_configuration($exp[0], $exp[1], $payrec->paymentid, $gateway);
                $helper = new \paygw_mtnafrica\mtn_helper($conf);
                $payable = helper::get_payable($exp[0], $exp[1], $transactionid);
                $payid = $payable->get_account_id();
                $currency = $payable->get_currency();
                $surcharge = helper::get_gateway_surcharge($gateway);
                $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);
                if ($status && $status == 'SUCCESSFUL' && $currency == $rcurrency && $amount == $ramount) {
                     $helper->enrol_user($transactionid, $payrec->paymentid, $exp[0], $exp[1]);
                }
            }
        }
    }
}
