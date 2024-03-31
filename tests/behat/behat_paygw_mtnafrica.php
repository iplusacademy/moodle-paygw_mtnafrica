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
 * Step definitions related to  MTN Africa payment callback.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
// For that reason, we can't even rely on $CFG->admin being available here.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Step definitions related to MTN Africa payment callback.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_paygw_mtnafrica extends behat_base {
    /**
     * Get the secrets from the environment.
     * @Then I configure mtn
     */
    public function i_configure_mtn(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/enrol/locallib.php');
        set_config('country', 'UG');
        $enabled = enrol_get_plugins(true);
        unset($enabled['guest']);
        unset($enabled['self']);
        $enabled['fee'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
        $account = new \stdClass();
        $account->name = 'Test';
        $account->idnumber = 'testid';
        $account->gateways = 'mtnafrica';
        $account->enabled = 1;
        $account = \core_payment\helper::save_payment_account((object)$account);
        $gateway = new \stdClass();
        $gateway->accountid = $account->get('id');
        $gateway->gateway = 'mtnafrica';
        $gateway->enabled = 1;
        \core_payment\helper::save_payment_gateway((object)$gateway);

        $secret = getenv('secret', true) ?: getenv('secret');
        $secret1 = getenv('secret1', true) ?: getenv('secret1');
        $config = new \stdClass();
        $config->clientid = 'fakelogin';
        $config->brandname = 'maul';
        $config->environment = 'sandbox';
        $config->apikey = 'fakeapi';
        $config->secret = $secret;
        $config->secret1 = $secret1;
        $config->country = 'UG';
        $DB->set_field('payment_gateways', 'config', json_encode($config), []);
    }
}
