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
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_mtnafrica;

use curl;
use stdClass;

/**
 * Contains helper class to work with MTN Africa REST API.
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mtn_helper {

    /**
     * @var string The base API URL
     */
    public $baseurl;

    /**
     * @var string The callback URL
     */
    public $callbackurl;

    /**
     * @var bool Are we working in sandbox
     */
    private $sandbox;

    /**
     * @var string Client ID
     */
    public $clientid;

    /**
     * @var string MTN Africa App Ocp-Apim
     */
    private $ocpapim;

    /**
     * @var string MTN Africa Apikey
     */
    private $apikey;

    /**
     * @var string MTN Africa Apikey
     */
    private $secret;

    /**
     * @var string The country where MTN Africa client is located
     */
    private $country;

    /**
     * @var string The oath bearer token
     */
    public $token;

    /**
     * @var boolean testing
     */
    public $testing;

    /**
     * @var \GuzzleHttp\Client
     */
    private $guzzle;


    /**
     * helper constructor.
     *
     * @param string $clientid The client id.
     * @param string $ocpapim MTN Africa ocpapim.
     * @param string $secret MTN Africa secundary key.
     * @param string $country MTN Africa location.
     * @param string $sandbox Whether we are working with the sandbox environment or not.
     * @param string $token If we alrady have a token, no need to generate a new one.
     */
    public function __construct(string $clientid, string $ocpapim, string $secret,
                                string $country = 'UG', string $sandbox = 'sandbox', string $token = '') {

        $this->guzzle = new \GuzzleHttp\Client();
        $this->sandbox = (bool)($sandbox == 'sandbox');
        $this->token = $token;
        $this->clientid = $clientid;
        $this->ocpapim = $ocpapim;
        $this->secret = $secret;
        $this->baseurl = self::get_base_url($sandbox);
        $this->callbackurl = self::get_callback_url($sandbox);
        $this->country = $country;
        $this->apikey = '';
        $this->testing = ((defined('PHPUNIT_TEST') && PHPUNIT_TEST) || defined('BEHAT_SITE_RUNNING'));
        if ($sandbox || $this->testing) {
            // We need to create a user in this sandbox environment.
            // We invent a clientid.
            $this->clientid = self::gen_uuid4();

            if ($clientid != 'fakelogin') {
                // We create a new user.
                $headers = ['X-Reference-Id' => $this->clientid, 'Ocp-Apim-Subscription-Key' => $this->ocpapim];
                $data = ['providerCallbackHost' => $this->callbackurl];
                $this->request_post('v1_0/apiuser', $data, $headers);

                // We try to collect user information.
                $headers = ['Ocp-Apim-Subscription-Key' => $this->ocpapim];
                $this->request_post('v1_0/apiuser/' . $this->clientid, [], $headers, 'GET');

                // We collect a apikey.
                $result = $this->request_post('v1_0/apiuser/' . $this->clientid . '/apikey', [], $headers);
                $this->apikey = array_key_exists('apiKey', $result) ? $result['apiKey'] : '';
            }
        }
    }

    /**
     * Generate UUID version 4
     *
     * @return string
     */
    public static function gen_uuid4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    }

    /**
     * Which url should be used.
     *
     * @param string $sandbox
     * @return string
     */
    public static function get_base_url(string $sandbox = 'sandbox'): string {
        return $sandbox == 'sandbox' ? 'https://sandbox.momodeveloper.mtn.com/' : 'https://api.mtn.com/';
    }

    /**
     * Which url should be used.
     *
     * @param string $sandbox
     * @return string
     */
    public static function get_callback_url(string $sandbox = 'sandbox'): string {
        return $sandbox == 'sandbox' ? 'http://test.ewallah.net/payment/gateway/mtnafrica/callback.php' : '';
    }

    /**
     * Collect a token.
     *
     * @return string
     */
    private function get_token() {
        if ($this->token == '') {
            $result = $this->request_post('collection/token/', [], $this->get_basic_auth());
            $this->token = array_key_exists('access_token', $result) ? (string) $result['access_token'] : '';
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
                'Ocp-Apim-Subscription-Key' => $this->ocpapim,
                'Authorization' => 'Basic ' . base64_encode($this->clientid . ':' . $this->apikey)]);
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
            // TODO: INVALID_CALLBACK_URL_HOST error generated 'X-Callback-Url' => $this->callbackurl.
            'X-Reference-Id' => $this->clientid,
            'X-Target-Environment' => $this->sandbox ? 'sandbox' : self::target_code($this->country),
            'Ocp-Apim-Subscription-Key' => $this->ocpapim]);
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
        int $transactionid, string $reference, float $amount, string $currency, string $userphone, string $usercountry): array {
        $allcountries = \paygw_mtnafrica\gateway::get_countries();
        $usercountry = strtoupper($usercountry);
        if (in_array($usercountry, $allcountries)) {
            $location = 'collection/v1_0/requesttopay';
            $xref = self::gen_uuid4();
            $headers = [
                'Authorization' => 'Bearer ' . $this->get_token(),
                'X-Reference-Id' => $xref,
                'X-Target-Environment' => $this->sandbox ? 'sandbox' : self::target_code($this->country),
                'Ocp-Apim-Subscription-Key' => $this->ocpapim];
            $data = [
                'amount' => $amount,
                'currency' => $currency,
                'externalId' => $transactionid,
                'payer' => ['partyIdType' => 'MSISDN', 'partyId' => $userphone],
                'payerMessage' => $reference,
                'payeeNote' => $reference];
            $response = $this->guzzle->request('POST', $this->baseurl . $location, ['headers' => $headers, 'json' => $data]);
            return ['code' => $response->getStatusCode(), 'xreferenceid' => $xref, 'token' => $this->token];
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
            'Ocp-Apim-Subscription-Key' => $this->ocpapim];
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
        string $location, array $data, array $headers = [], string $verb = 'POST'): ?array {
        $decoded = $result = '';
        $response = null;
        $location = $this->baseurl . $location;
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        try {
            $response = $this->guzzle->request($verb, $location, ['headers' => $headers, 'json' => $data]);
            $result = $response->getBody()->getContents();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $result = $e->getMessage();
        } finally {
            $decoded = json_decode($result, true);
            // Trigger an event.
            $eventargs = ['context' => \context_system::instance(),
                          'other' => ['verb' => $verb, 'location' => $location, 'result' => $decoded]];
            $event = \paygw_mtnafrica\event\request_log::create($eventargs);
            $event->trigger();
        }
        return $decoded;
    }

    /**
     * Transaction code
     * @param int $code
     * @return string
     */
    public function ta_code(int $code) {
        $returns = [
            202 => 'Accepted',
            400 => 'Bad Request',
            409 => 'Conflict, duplicated reference id',
            500 => 'Internal Server Error'];
        return array_key_exists($code, $returns) ? $returns[$code] : 'Unknown';
    }

    /**
     * Return target
     * @param string $code
     * @return string tartet
     */
    public function target_code(string $code) {
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
            'LR' => 'mtnliberia'];
        return array_key_exists($code, $returns) ? $returns[$code] : 'sandbox';
    }
}
