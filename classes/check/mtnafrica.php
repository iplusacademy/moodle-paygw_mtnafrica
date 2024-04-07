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
 * Checks.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica\check;

use action_link;
use core\check\{check, result};
use moodle_url;

/**
 * Checks.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mtnafrica extends check {
    /**
     * Collect result.
     *
     * @return result
     */
    public function get_result(): result {
        if ($this->sandbox_used()) {
            $info = result::CRITICAL;
            $summary = get_string('check_critical', 'paygw_mtnafrica');
            $details = get_string('check_critical_help', 'paygw_mtnafrica');
        } else {
            $info = result::INFO;
            $summary = get_string('check_info', 'paygw_mtnafrica');
            $details = get_string('check_info_help', 'paygw_mtnafrica');
        }
        return new result($info, $summary, $details);
    }

    /**
     * Collect result.
     *
     * @return bool
     */
    private function sandbox_used(): bool {
        global $DB;
        // If the MTN sandbox is enabled, we need to show danger, otherwise just a warning.
        $compare = $DB->sql_position("'sandbox'", 'config');
        $where = "gateway = :gateway AND enabled = :enabled AND $compare > 0";
        return $DB->count_records_select('payment_gateways', $where, ['gateway' => 'mtnafrica', 'enabled' => 1]) !== 0;
    }

    /**
     * Link to the Gateways report
     *
     * @return action_link|null
     */
    public function get_action_link(): ?action_link {
        if ($this->sandbox_used()) {
            return new action_link(
                new moodle_url('/payment/accounts.php'), get_string('paymentaccounts', 'payment'),
            );
        }
        return null;
    }
}
