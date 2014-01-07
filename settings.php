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
 * Defines the settings page link for local_cohort_automation
 *
 * @package    local_cohort_automation
 * @copyright  2014 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Build the url to the settings page.
$externalpage = new admin_externalpage('cohort_automation', get_string('pluginname', 'local_cohort_automation'),
    new moodle_url('/local/cohort_automation/mappings.php'));

// Add the admin page to the menu tree..
if (!$ADMIN->locate('localplugins')) {
    $ADMIN->add('root', new admin_category('localplugins', 'Local Plugins'));
}
$ADMIN->add('localplugins', $externalpage);
