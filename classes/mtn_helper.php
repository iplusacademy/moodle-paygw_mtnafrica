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
 * Contains helper class to work with MTN Africa REST API.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica;

use curl;
use stdClass;
use core_payment\helper;
use core_text;

/**
 * Contains helper class to work with MTN Africa REST API.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mtn_helper {
    /**
     * @var string The base API URL
     */
    public string $baseurl = 'https://proxy.momoapi.mtn.com/';

    /**
     * @var bool Are we working in sandbox
     */
    public bool $sandbox;

    /**
     * @var string Client ID
     */
    public $clientid;

    /**
     * @var string apikey
     */
    public $apikey;

    /**
     * @var string MTN Africa Apikey
     */
    private $secret;

    /**
     * @var string MTN Africa Apikey2
     */
    private $secret1;

    /**
     * @var string The country where MTN Africa client is located
     */
    private $country;

    /**
     * @var string The oath bearer token
     */
    public $token = '';

    /**
     * @var bool testing
     */
    public $testing = false;

    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzle;


    /**
     * Helper constructor.
     *
     * @param array $config The gateway configuration.
     * @param string $country MTN Africa location.
     */
    public function __construct(array $config, string $country = 'UG') {
        $this->guzzle = new \GuzzleHttp\Client();
        $this->sandbox = (strtolower($config['environment']) == 'sandbox');
        $this->clientid = $config['clientid'];
        $this->apikey = $config['apikey'];
        $this->secret = $config['secret'];
        $this->secret1 = $config['secret1'];
        if ($this->sandbox) {
            $this->clientid = self::gen_uuid4();
            $this->baseurl = 'https://sandbox.momodeveloper.mtn.com/';
            // We try to create a new user.
            $this->testing = (defined('BEHAT_SITE_RUNNING') || (defined('PHPUNIT_TEST') && PHPUNIT_TEST));
            $headers = ['X-Reference-Id' => $this->clientid, 'Ocp-Apim-Subscription-Key' => $this->secret1];
            $data = ['providerCallbackHost' => $this->get_callback_host()];
            $this->request_post('v1_0/apiuser', $data, $headers);
            // We try to collect user information.
            $headers = ['Ocp-Apim-Subscription-Key' => $this->secret1];
            $this->request_post('v1_0/apiuser/' . $this->clientid, [], $headers, 'GET');
            // We collect a apikey.
            $result = $this->request_post('v1_0/apiuser/' . $this->clientid . '/apikey', [], $headers);
            $this->apikey = self::array_helper('apiKey', $result) ?? $this->apikey;
        }
        $this->country = self::array_helper('country', $config) ?? $country;
    }

    /**
     * Generate UUID version 4
     *
     * @return string
     */
    private static function gen_uuid4() {
        $magic = '%s%s-%s-%s-%s-%s%s%s';
        $strong = vsprintf($magic, str_split(bin2hex(random_bytes(16)), 4));
        $data = openssl_random_pseudo_bytes(16, $strong);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf($magic, str_split(bin2hex($data), 4));
    }

    /**
     * Which host should be used for callback.
     *
     * @return string
     */
    private function get_callback_host(): string {
        global $CFG;
        $http = $this->sandbox ? 'http://' : 'https://';
        $dom = str_ireplace('http://', '', $CFG->wwwroot);
        $dom = str_ireplace('https://', '', $dom);
        if (stripos($dom, 'example.com') !== false) {
            // Local domain is example domain while testing, so we have to get the info from config.
            $dom = str_ireplace('www.example.com', self::get_hostname(), $dom);
            $dom = str_ireplace('/moodle', '', $dom);
            $http = 'https://';
        }
        return $http . $dom;
    }

    /**
     * Which hostname are we running, get the info from config file.
     *
     * @return string
     */
    public static function get_hostname(): string {
        $lines = file('config.php');
        $needle = '$CFG->wwwroot';
        $arr = [$needle, '/moodle', ' ', ';', '=', '"', "'", 'http://', 'https://', PHP_EOL];
        $result = '127.0.0.1';
        foreach ($lines as $line) {
            if (stripos($line, $needle) !== false) {
                $result = strip_tags(str_ireplace($arr, '', $line));
                break;
            }
        }
        // Localhost callbacks are redirected.
        return str_ireplace(['localhost', '127.0.0.1'], 'test.medical-access.org', $result);
    }

    /**
     * Collect a token.
     *
     * @return string
     */
    private function get_token() {
        if ($this->token == '') {
            $result = $this->request_post('collection/token/', [], $this->get_basic_auth());
            $this->token = self::array_helper('access_token', $result) ?? '';
        }
        return $this->token;
    }

    /**
     * Add 'X-XSS-Protection'
     *
     * @param array $arr
     * @return array
     */
    private function add_xxx_protection(array $arr): array {
        return $this->sandbox ? array_merge($arr, ['X-XSS-Protection' => 0]) : $arr;
    }

    /**
     * Basic Authorization.
     *
     * @return array
     */
    private function get_basic_auth(): array {
        return $this->add_xxx_protection([
            'Ocp-Apim-Subscription-Key' => $this->secret1,
            'Authorization' => 'Basic ' . base64_encode($this->clientid . ':' . $this->apikey),
        ]);
    }

    /**
     * Advanced Authorization.
     *
     * @return array
     */
    private function get_advanced_auth(): array {
        $token = $this->get_token();
        return $this->add_xxx_protection([
            'Authorization' => 'Bearer ' . $token,
            // TODO: INVALID_CALLBACK_URL_HOST error generated.
            'X-Callback-Url' => $this->get_callback_host(),
            'X-Reference-Id' => $this->clientid,
            'X-Target-Environment' => $this->sandbox ? 'sandbox' : self::target_code($this->country),
            'Ocp-Apim-Subscription-Key' => $this->secret1,
        ]);
    }

    /**
     * Collection API: Payments - USSD Push.
     *
     * @param int $transactionid
     * @param string $reference
     * @param float $amount
     * @param string $currency
     * @param string $userphone
     * @param string $usercountry
     * @return array int API code - xref.
     */
    public function request_payment(
        int $transactionid,
        string $reference,
        float $amount,
        string $currency,
        string $userphone,
        string $usercountry
    ): array {

        $allcountries = \paygw_mtnafrica\gateway::get_countries();
        $usercountry = strtoupper($usercountry);
        $currency = $this->sandbox ? 'EUR' : $currency;
        if (in_array($usercountry, $allcountries)) {
            $location = $this->baseurl . 'collection/v1_0/requesttopay';
            $xref = self::gen_uuid4();
            $headers = [
                'Authorization' => 'Bearer ' . $this->get_token(),
                'X-Reference-Id' => $xref,
                'X-Target-Environment' => $this->sandbox ? 'sandbox' : self::target_code($this->country),
                'Ocp-Apim-Subscription-Key' => $this->secret1,
            ];
            $data = [
                'amount' => $amount,
                'currency' => $currency,
                'externalId' => $transactionid,
                'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $userphone],
                'payerMessage' => substr(get_string('thanks', 'paygw_mtnafrica'), 0, 30),
                'payeeNote' => $reference,
            ];
            $response = $this->guzzle->request('POST', $location, ['headers' => $headers, 'json' => $data]);
            $code = $response->getStatusCode();
            $other = array_merge(['verb' => 'Post', 'location' => $location, 'answer' => $code], $data);
            $eventargs = ['context' => \context_system::instance(), 'other' => $other];
            $event = \paygw_mtnafrica\event\request_log::create($eventargs);
            $event->trigger();
            return ['code' => $code, 'xreferenceid' => $xref, 'token' => $this->token];
        }
        throw new \moodle_exception(get_string('invalidcountrycode', 'core_error', $usercountry));
    }

    /**
     * Collection API: transaction enquiry
     *
     * @param string $xreferenceid
     * @param string $token
     * @return array Formatted API response.
     */
    public function transaction_enquiry(string $xreferenceid, string $token): array {

        $location = 'collection/v1_0/requesttopay/' . $xreferenceid;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'X-Target-Environment' => $this->sandbox ? 'sandbox' : self::target_code($this->country),
            'Ocp-Apim-Subscription-Key' => $this->secret1,
        ];
        return $this->request_post($location, [], $headers, 'GET');
    }

    /**
     * Collection API: user enquiry
     *
     * @param string $phone
     * @return bool True is valid user.
     */
    public function valid_user(string $phone): bool {
        $location = $this->baseurl . "collection/v1_0/accountholder/msisdn/$phone/basicuserinfo";
        $response = $this->guzzle->request('GET', $location, ['headers' => $this->get_advanced_auth()]);
        return $response->getStatusCode() == 200;
    }

    /**
     * Captures an authorized payment, by ID.
     *
     * @param string $location
     * @param array $data
     * @param array $headers
     * @param string $verb
     * @return array Decoded API response.
     */
    private function request_post(
        string $location,
        array $data,
        array $headers = [],
        string $verb = 'POST'
    ): ?array {

        $decoded = $result = $resultcode = '';
        $response = null;
        $location = $this->baseurl . $location;
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        try {
            $response = $this->guzzle->request($verb, $location, ['headers' => $headers, 'json' => $data]);
            $result = $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $result = $e->getMessage();
            mtrace_exception($e);
        } catch (\Exception $e) {
            $result = $e->getMessage();
            mtrace_exception($e);
        } finally {
            $decoded = json_decode($result, true);
            $other = ['verb' => $verb, 'location' => $location];
            $decolog = $decoded;
            if (is_array($decolog)) {
                if (array_key_exists('access_token', $decolog)) {
                    unset($decolog['access_token']);
                }
                $other['result'] = $decolog;
                // TODO: uncomment for tracing.
                // mtrace(json_encode($decolog));.
            } else {
                $resultcode = $response->getStatusCode();
                $resultreason = $response->getReasonPhrase();
                $other['result'] = $resultcode . ' ' . $resultreason;
                // TODO: uncomment for tracing.
                // mtrace($resultcode);.
            }
            $eventargs = ['context' => \context_system::instance(), 'other' => $other];
            // Trigger an event.
            \paygw_mtnafrica\event\request_log::create($eventargs)->trigger();
        }
        return $decoded ?? [];
    }

    /**
     * Enrol the user
     *
     * @param string $transactionid
     * @param int $itemid
     * @param string $component Name of the component that the itemid belongs to
     * @param string $area The payment area
     * @return string
     */
    public function enrol_user(string $transactionid, int $itemid, string $component, string $area): string {
        global $DB;
        // We assume the transaction pending.
        $status = 'PENDING';
        $cond = ['transactionid' => $transactionid, 'paymentid' => $itemid];
        if ($rec = $DB->get_record('paygw_mtnafrica', $cond)) {
            $userid = $rec->userid;
            if ($rec->timecompleted == 0) {
                $this->token = $rec->moneyid;
                $result = $this->transaction_enquiry($transactionid, $this->token);

                // Sample data:
                // [financialTransactionId] => 2026118745
                // [externalId] => 2362616710
                // [amount] => 100
                // [currency] => EUR
                // [payer] => {[partyIdType] => MSISDN, [partyId] => 1234567}
                // [payerMessage] => Thanks for your payment
                // [payeeNote] => enrol_fee-fee-13-4
                // [status] => SUCCESSFUL.

                $status = self::array_helper('status', $result);
                if ($status) {
                    if ($status == 'FAILED') {
                        $DB->delete_record('paygw_mtnafrica', $cond);
                    }
                    if ($status == 'SUCCESSFUL') {
                        $payable = helper::get_payable($component, $area, $itemid);
                        $payid = $payable->get_account_id();
                        $currency = $this->sandbox ? 'EUR' : $payable->get_currency();
                        $surcharge = helper::get_gateway_surcharge('mtnafrica');
                        $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);
                        $moneyid = self::array_helper('financialTransactionId', $result);
                        $ramount = self::array_helper('amount', $result);
                        $rcurrency = self::array_helper('currency', $result);
                        $payer = self::array_helper('payeeNote', $result);
                        if ($currency == $rcurrency && $amount == $ramount) {
                            // We have a succesfull transaction.
                            $payer = explode('-', $payer);
                            if ($payer[0] == $component && $payer[1] == $area && intval($payer[3]) == $userid) {
                                $saved = helper::save_payment(
                                    $payid,
                                    $component,
                                    $area,
                                    $itemid,
                                    $userid,
                                    $amount,
                                    $currency,
                                    'mtnafrica'
                                );
                                helper::deliver_order($component, $area, $itemid, $saved, $userid);
                                $DB->set_field('paygw_mtnafrica', 'timecompleted', time(), $cond);
                                $DB->set_field('paygw_mtnafrica', 'moneyid', $moneyid, $cond);
                            }
                        } else {
                            // Fraud?
                            $DB->set_field('paygw_mtnafrica', 'area', 'FRAUD');
                            $status = 'FAILED';
                        }
                    }
                }
            }
        }
        return $status;
    }

    /**
     * Transaction code
     * @param int $code
     * @return string
     */
    public static function ta_code(int $code): string {
        $returns = [
            202 => 'Accepted',
            400 => 'Bad Request',
            409 => 'Conflict, duplicated reference id',
            500 => 'Internal Server Error',
        ];
        return self::array_helper($code, $returns) ?? 'Unknown';
    }

    /**
     * Return target
     * @param string $code
     * @return string target
     */
    public static function target_code(string $code): string {
        $returns = [
            'UG' => 'mtnuganda',
            'GH' => 'mtnghana',
            'CI' => 'mtnivorycoast',
            'ZM' => 'mtnzambia',
            'CM' => 'mtncameroon',
            'BJ' => 'mtnbenin',
            'CD' => 'mtncongo',
            'SZ' => 'mtnswaziland',
            'GN' => 'mtnguineaconakry',
            'ZA' => 'mtnsouthafrica',
            'LR' => 'mtnliberia',
        ];
        return self::array_helper($code, $returns) ?? 'sandbox';
    }

    /**
     * Safe array helper.
     *
     * @param string $key
     * @param array $arr
     * @return array||null
     */
    public static function array_helper(string $key, array $arr) {
        // TODO: return ($arr && array_key_exists($key, $arr)) ? $arr[$key] : null;
        // Cleans key and array to avoid XSS and other issues.
        $safekey = clean_param($key, PARAM_TEXT);
        $safearr = clean_param_array($arr, PARAM_TEXT, true);
        if (is_array($safearr) && isset($safearr[$safekey]) && !empty($safearr[$safekey])) {
            return $safearr[$safekey];
        }
        return null;
    }

    /**
     * User data helper.
     *
     * @return array
     */
    public function current_user_data(): array {
        global $USER;
        $arr = [];
        $user = \core_user::get_user($USER->id, 'id, phone1, phone2, country');
        if ($user) {
            $phone = $user->phone2 == '' ? $user->phone1 : $user->phone2;
            $phone = preg_replace("/[^0-9]/", '', $phone);
            if (strlen($phone) > 5) {
                $arr = ['id' => $user->id, 'country' => strtoupper($user->country), 'phone' => $phone];
            }
        }
        return $arr;
    }
}
