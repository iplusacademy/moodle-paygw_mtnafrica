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
 * @copyright  2023 Medical Access Uganda Limited
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../../config.php');  // phpcs:ignore
require_once($CFG->dirroot . '/course/lib.php');

require_login();

$paymentarea = optional_param('paymentarea', null, PARAM_ALPHA);
$itemid = optional_param('itemid', 0, PARAM_INT);
$url = new \moodle_url('/');
if ($courseid = $DB->get_field('enrol', 'courseid', ['enrol' => $paymentarea, 'id' => $itemid])) {
    $url = new \moodle_url('/course/view.php', ['id' => $courseid]);
}
redirect($url);
