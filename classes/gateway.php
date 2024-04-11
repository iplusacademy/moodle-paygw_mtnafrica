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
 * Contains class for MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica;

/**
 * The gateway class for MTN Africa payment gateway.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * Country - Currencies supported
     *
     * @return array
     */
    public static function get_country_currencies(): array {
        return [
            'BJ' => 'XOF',
            'CM' => 'XAF',
            'TD' => 'XAF',
            'CG' => 'XAF',
            'CD' => 'CDF',
            'GH' => 'GHS',
            'GN' => 'GNF',
            'CI' => 'XOF',
            'LR' => 'LRD',
            'NE' => 'XOF',
            'RW' => 'RWF',
            'ZA' => 'ZAR',
            'UG' => 'UGX',
            'ZM' => 'ZMW',
            'sandbox' => 'EUR',
        ];
    }

    /**
     * Currencies supported
     *
     * @return array
     */
    public static function get_supported_currencies(): array {
        return ['CDF', 'EUR', 'GHS', 'GNF', 'LRD', 'RWF', 'UGX', 'XAF', 'XOF', 'ZAR', 'ZMW'];
    }

    /**
     * Countries supported
     *
     * @return array
     */
    public static function get_countries(): array {
        return ['BJ', 'CM', 'TD', 'CG', 'CD', 'GA', 'GH', 'CI', 'LR', 'NE', 'RW', 'ZA', 'UG', 'ZM'];
    }

    /**
     * Countries supported
     *
     * @return array
     */
    private static function get_supported_countries(): array {
        $countries = self::get_countries();
        $strs = get_strings($countries, 'countries');
        $return = [];
        foreach ($countries as $country) {
            $return[$country] = $strs->$country;
        }
        return $return;
    }

    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $arr = ['apikey', 'brandname', 'clientid', 'country', 'environment', 'live', 'sandbox', 'secret', 'secret1'];
        $strs = get_strings($arr, 'paygw_mtnafrica');
        $mform = $form->get_mform();

        $mform->addElement('text', 'brandname', $strs->brandname);
        $mform->setType('brandname', PARAM_TEXT);
        $mform->addHelpButton('brandname', 'brandname', 'paygw_mtnafrica');

        $mform->addElement('text', 'clientid', $strs->clientid);
        $mform->setType('clientid', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('clientid', 'clientid', 'paygw_mtnafrica');

        $mform->addElement('passwordunmask', 'apikey', $strs->apikey);
        $mform->setType('apikey', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('apikey', 'apikey', 'paygw_mtnafrica');

        $mform->addElement('passwordunmask', 'secret', $strs->secret);
        $mform->setType('secret', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('secret', 'secret', 'paygw_mtnafrica');

        $mform->addElement('passwordunmask', 'secret1', $strs->secret1);
        $mform->setType('secret1', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('secret1', 'secret1', 'paygw_mtnafrica');

        $options = self::get_supported_countries();
        $mform->addElement('select', 'country', $strs->country, $options, 'UG');
        $mform->addHelpButton('country', 'country', 'paygw_mtnafrica');

        $options = ['live' => $strs->live, 'sandbox' => $strs->sandbox];
        $mform->addElement('select', 'environment', $strs->environment, $options);
        $mform->addHelpButton('environment', 'environment', 'paygw_mtnafrica');

        $mform->addRule('clientid', get_string('required'), 'required');
        $mform->addRule('apikey', get_string('required'), 'required');
        $mform->addRule('secret', get_string('required'), 'required');
        $mform->addRule('secret1', get_string('required'), 'required');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        $vals = empty($data->clientid) || empty($data->apikey) || empty($data->secret) || empty($data->secret1);
        if ($data->enabled && $vals) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
    }
}
