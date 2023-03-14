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
 * Handles callback received from MTN Africa
 *
 * @package    paygw_mtnafrica
 * @copyright  2023 Medical Access Uganda
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../../config.php');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
} else {
    if ($response = json_decode(file_get_contents('php://input'), true)) {
        if (isset($response['status'])) {
            $status = $response['status'];
            $eventargs = ['context' => \context_system::instance(), 'other' => $response];
            $event = \paygw_mtnafrica\event\request_log::create($eventargs);
            $event->trigger();
        }
    }
}
