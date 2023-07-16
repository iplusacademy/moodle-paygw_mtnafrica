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
 * Handles continue
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_DEBUG_DISPLAY', true);

// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../../config.php');  // phpcs:ignore

use paygw_mtnafrica\mtn_helper;
use core_payment\helper;

global $CFG, $DB;
require_once($CFG->dirroot . '/course/lib.php');

// Keep out casual intruders.
if (empty($_POST) || !empty($_GET)) {
    http_response_code(400);
    throw new moodle_exception('invalidrequest', 'core_error');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // This is a local check if everything went well.
    require_login();
    if (!confirm_sesskey()) {
        redirect($CFG->wwwroot);
    }
    $data = new stdClass();
    foreach ($_POST as $key => $value) {
        if ($key !== clean_param($key, PARAM_ALPHANUMEXT)) {
            throw new moodle_exception('invalidrequest', 'core_error', '', null, $key);
        }
        if (is_array($value)) {
            throw new moodle_exception('invalidrequest', 'core_error', '', null, 'Unexpected array param: '.$key);
        }
        $data->$key = fix_utf8($value);
    }
    $url = new \moodle_url('/');
    if (property_exists($data, 'itemid') && property_exists($data, 'paymentarea') &&
        $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => $data->paymentarea, 'id' => $data->itemid])) {
        $url = new \moodle_url('/course/view.php', ['id' => $courseid]);
    }
    redirect($url);
} else {
    die();
}
