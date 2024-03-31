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
 * Strings for component 'paygw_mtnafrica', language 'en'
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['amountmismatch'] = 'The amount you attempted to pay does not match the required fee. Your account has not been debited.';
$string['apikey'] = 'API key';
$string['apikey_help'] = 'API key, found on the local money portal.';
$string['authorising'] = 'Authorising the payment. Please wait...';
$string['brandname'] = 'Brand name';
$string['brandname_help'] = 'An optional label that overrides the business name for the your account on the MTN Africa site.';
$string['cannotfetchorderdatails'] = 'Could not fetch payment details from MTN Africa. Your account has not been debited.';
$string['check_details'] = 'MTN Africa needs port 80 for the sandbox';
$string['check_warning'] = 'HTTP Port should be open for sandbox testing';
$string['checkmtnafrica'] = 'MTN Africa';
$string['cleanuptask'] = 'Clean up not completed payment task.';
$string['clientid'] = 'API User';
$string['clientid_help'] = 'The API User ID that MTN Africa generated for your application.';
$string['country'] = 'Country';
$string['country_help'] = 'In which country is this client located';
$string['environment'] = 'Environment';
$string['environment_help'] = 'You can set this to Sandbox if you are using sandbox accounts (for testing purpose only).';
$string['failed'] = 'MTN Africa payment failed';
$string['gatewaydescription'] = 'MTN Africa is an authorised payment gateway provider for processing mobile money.';
$string['gatewayname'] = 'MTN Africa';
$string['internalerror'] = 'An internal error has occurred. Please contact us.';
$string['live'] = 'Live';
$string['mtnstart'] = 'We sent you a request for payment.</br>
Please complete the payment using your cell phone.</br>
You have 3 minutes to complete this transaction.</br>
The moment we receive a confirmation by MTN Africa, you will be able to access the course.';
$string['paymentnotcleared'] = 'payment not cleared by MTN Africa.';
$string['pluginname'] = 'MTN Africa';
$string['pluginname_desc'] = 'The MTN Africa plugin allows you to receive payments via MTN Africa.';
$string['privacy:metadata:paygw_mtnafrica'] = 'The MTN Africa payment gateway stores payment information.';
$string['privacy:metadata:paygw_mtnafrica:moneyid'] = 'The MTN Money id of the payment.';
$string['privacy:metadata:paygw_mtnafrica:paymentid'] = 'The payment id of the payment.';
$string['privacy:metadata:paygw_mtnafrica:timecompleted'] = 'The time the payment was completed.';
$string['privacy:metadata:paygw_mtnafrica:timecreated'] = 'The time the payment was created.';
$string['privacy:metadata:paygw_mtnafrica:transactionid'] = 'The transactionid of the payment.';
$string['privacy:metadata:paygw_mtnafrica:userid'] = 'The userid of the user.';
$string['repeatedorder'] = 'This order has already been processed earlier.';
$string['request_log'] = 'Gateway log';
$string['sandbox'] = 'Sandbox';
$string['secret'] = 'Primary key';
$string['secret1'] = 'Secondary Key';
$string['secret1_help'] = 'Secondary Key for MTN Africa <b>collections</b> subscription (Found on https://momodeveloper.mtn.com/developer).';
$string['secret_help'] = 'Primary key for MTN Africa <b>collections</b> subscription (Found on https://momodeveloper.mtn.com/developer).';
$string['start'] = 'Click on the MTN image to start your payment.';
$string['thanks'] = 'THX for your payment.';
$string['unable'] = 'Unable to connect to MTN';
$string['validcontinue'] = 'Please wait until we receive confirmation by Aitel, +-30 seconds before you continue.';
$string['validtransaction'] = 'We got a valid transactionid: {$a}';
$string['warning_phone'] = 'Please be sure that this is <strong>your</strong> Mobile phone number and country. You can change the number and country on your <a href="/user/edit.php" title="profile">profile page</a>.</br>
MTN Africa needs a number <b>with</b> the country code.
(Sample: 46733123451)';
