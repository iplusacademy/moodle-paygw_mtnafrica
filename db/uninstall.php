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
 * paygw_mtnafrica uninstaller script.
 *
 * @package    paygw_mtnafrica
 * @copyright  Medical Access Uganda Limited (e-learning.medical-access.org)
 * @author     Renaat Debleu <info@eWallah.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Uninstaller script.
 *
 */
function xmldb_paygw_mtnafrica_uninstall(): void {
    global $CFG;
    $order = empty($CFG->paygw_plugins_sortorder) ? [] : explode(',', (string) $CFG->paygw_plugins_sortorder);
    set_config('paygw_plugins_sortorder', implode(',', array_diff($order, ['mtnafrica'])));
}
