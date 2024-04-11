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
 * This class completes a payment with the MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_mtnafrica\external;

use context_user;
use context_system;
use core_payment\helper;
use core_external\{external_api, external_function_parameters, external_value, external_single_structure};
use paygw_mtnafrica\mtn_helper;

/**
 * This class completes a payment with the MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transaction_complete extends external_api {
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
            'xreferenceid' => new external_value(PARAM_TEXT, 'The order id coming back from MTN Africa'),
        ]);
    }

    /**
     * Perform what needs to be done when a transaction is reported to be complete.
     * This function does not take cost as a parameter as we cannot rely on any provided value.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea
     * @param int $itemid An internal identifier that is used by the component
     * @param string $xreferenceid MTN Africa order ID
     * @return array
     */
    public static function execute(string $component, string $paymentarea, int $itemid, string $xreferenceid): array {
        global $USER;
        $usercontext = context_user::instance($USER->id);
        self::validate_context($usercontext);
        $systencontext = context_system::instance();
        self::validate_context($systencontext);
        self::validate_parameters(self::execute_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'xreferenceid' => $xreferenceid,
        ]);
        $config = helper::get_gateway_configuration($component, $paymentarea, $itemid, 'mtnafrica');
        $helper = new mtn_helper($config);
        $trans = $helper->enrol_user($xreferenceid, $itemid, $component, $paymentarea);
        return ['success' => $trans != 'FAILED', 'message' => $trans];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function execute_returns() {
        return new external_function_parameters([
            'success' => new external_value(PARAM_BOOL, 'Whether everything was successful or not.'),
            'message' => new external_value(PARAM_RAW, 'Message (usually the error message).'),
        ]);
    }
}
