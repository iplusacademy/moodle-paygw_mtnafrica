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
 * This class starts a payment with the MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_mtnafrica\external;

use core_payment\helper;
use core_external\{external_api, external_function_parameters, external_value, external_single_structure};
use paygw_mtnafrica\mtn_helper;

/**
 * This class starts a payment with the MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transaction_start extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area'),
        ]);
    }

    /**
     * Perform what needs to be done when a transaction is reported to be complete.
     * This function does not take cost as a parameter as we cannot rely on any provided value.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea
     * @param int $itemid An internal identifier that is used by the component
     * @return array
     */
    public static function execute(string $component, string $paymentarea, int $itemid): array {
        global $DB;
        $gateway = 'mtnafrica';
        $transactionid = '0';
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
        ]);
        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, $gateway);
        $helper = new mtn_helper($config);
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $user = $helper->current_user_data();
        $userid = $user['id'];
        $amount = $payable->get_amount();
        $currency = $payable->get_currency();
        $surcharge = helper::get_gateway_surcharge($gateway);
        $cost = helper::get_rounded_cost($amount, $currency, $surcharge);
        $reference = implode('-', [$component, $paymentarea, $itemid, $userid]);
        $code = 409;
        while ($code == 409) {
            $random = random_int(1000000000, 9999999999);
            $result = $helper->request_payment($random, $reference, $cost, $currency, $user['phone'], $user['country']);
            $code = mtn_helper::array_helper('code', $result);
        }
        if ($code && $code == 202) {
            $cond = ['paymentid' => $itemid, 'userid' => $userid];
            $DB->delete_records('paygw_mtnafrica', $cond);
            $transactionid = mtn_helper::array_helper('xreferenceid', $result) ?? '0';
            $data = new \stdClass;
            $data->paymentid = $itemid;
            $data->userid = $userid;
            $data->transactionid = $transactionid;
            $data->moneyid = $helper->token;
            $data->component = $component;
            $data->paymentarea = $paymentarea;
            $data->timecreated = time();
            $DB->insert_record('paygw_mtnafrica', $data);
        }
        return ['transactionid' => $transactionid, 'reference' => $reference, 'message' => mtn_helper::ta_code($code)];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'transactionid' => new external_value(PARAM_RAW, 'A valid transaction id or 0 when not successful'),
            'reference' => new external_value(PARAM_RAW, 'A reference'),
            'message' => new external_value(PARAM_RAW, 'Usualy the error message'),
        ]);
    }
}
