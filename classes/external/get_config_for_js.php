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
 * This class collects information about a payment with the MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_mtnafrica\external;

use core_payment\helper;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use paygw_mtnafrica\mtn_helper;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');

/**
 * This class collects information about a payment with the MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_config_for_js extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'Component'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'An identifier for payment area in the component'),
        ]);
    }

    /**
     * Returns the config values required by the MTN Africa JavaScript SDK.
     *
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @return string[]
     */
    public static function execute(string $component, string $paymentarea, int $itemid): array {
        global $USER;
        $gateway = 'mtnafrica';
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
        ]);
        $phone = get_string('statusunknown');
        $country = 'UG';
        $userid = 1;
        $user = \core_user::get_user($USER->id);
        if ($user) {
            $userid = $user->id;
            $country = strtoupper($user->country);
            $phone = $user->phone2 == '' ? $user->phone1 : $user->phone2;
        }
        $config = (object)helper::get_gateway_configuration($component, $paymentarea, $itemid, $gateway);
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $amount = $payable->get_amount();
        $currency = $payable->get_currency();
        $surcharge = helper::get_gateway_surcharge($gateway);
        $cost = helper::get_rounded_cost($amount, $currency, $surcharge);
        return [
            'clientid' => $config->clientid,
            'brandname' => $config->brandname,
            'country' => $config->country,
            'cost' => $cost,
            'currency' => $currency,
            'phone' => $phone,
            'usercountry' => $country,
            'userid' => $userid,
            'reference' => "$paymentarea $itemid $userid",
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'clientid' => new external_value(PARAM_TEXT, 'MTN Africa client ID'),
            'brandname' => new external_value(PARAM_TEXT, 'Brand name'),
            'country' => new external_value(PARAM_TEXT, 'Client country'),
            'cost' => new external_value(PARAM_FLOAT, 'Amount (with surcharge) that will be debited from the payer account.'),
            'currency' => new external_value(PARAM_TEXT, 'ISO4217 Currency code'),
            'phone' => new external_value(PARAM_TEXT, 'User mobile phone'),
            'usercountry' => new external_value(PARAM_TEXT, 'User country'),
            'userid' => new external_value(PARAM_INT, 'User id'),
            'reference' => new external_value(PARAM_TEXT, 'Reference'),
        ]);
    }
}
