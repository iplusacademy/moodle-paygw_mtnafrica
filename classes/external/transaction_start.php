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
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_mtnafrica\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use paygw_mtnafrica\mtn_helper;

/**
 * This class starts a payment with the MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda
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
            'reference' => new external_value(PARAM_RAW, 'The reference we use'),
            'phone' => new external_value(PARAM_RAW, 'The phone of the payer'),
            'country' => new external_value(PARAM_RAW, 'The country of the payer'),
        ]);
    }

    /**
     * Perform what needs to be done when a transaction is reported to be complete.
     * This function does not take cost as a parameter as we cannot rely on any provided value.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea
     * @param int $itemid An internal identifier that is used by the component
     * @param string $reference
     * @param string $phone
     * @param string $country
     * @return array
     */
    public static function execute(
        string $component, string $paymentarea, int $itemid, string $reference, string $phone, string $country): array {
        $gateway = 'mtnafrica';

        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'reference' => $reference,
            'phone' => $phone,
            'country' => $country,
        ]);

        $config = (object)\core_payment\helper::get_gateway_configuration($component, $paymentarea, $itemid, $gateway);
        $payable = \core_payment\helper::get_payable($component, $paymentarea, $itemid);
        $amount = $payable->get_amount();
        $currency = $payable->get_currency();
        $surcharge = \core_payment\helper::get_gateway_surcharge($gateway);
        $cost = \core_payment\helper::get_rounded_cost($amount, $currency, $surcharge);
        $random = random_int(1000000000, 9999999999);
        $helper = new \paygw_mtnafrica\mtn_helper(
            $config->clientid,
            $config->secret,
            $config->secret1,
            $config->country,
            $config->environment);
        $result = $helper->request_payment($random, $reference, $cost, $currency, $phone, $country);
        $code = $result['code'];
        return ['returncode' => $code, 'message' => $helper->ta_code($code), 'xreferenceid' => $result['xreferenceid'],
                'token' => $result['token']];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'returncode' => new external_value(PARAM_INT, '202 when success'),
            'message' => new external_value(PARAM_RAW, 'Usualy the error message'),
            'xreferenceid' => new external_value(PARAM_RAW, 'The xreference transaction id'),
            'token' => new external_value(PARAM_RAW, 'The MTN token'),
        ]);
    }
}
