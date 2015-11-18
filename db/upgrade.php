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
 * Upgrades
 *
 * @package    local_cohort_automation
 * @copyright  2015 Brendan Heywood (brendan@catalst-au.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_local_cohort_automation_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    require_once($CFG->dirroot . '/local/cohort_automation/lib.php');
    require_once($CFG->dirroot . '/local/cohort_automation/locallib.php');

    $dbman = $DB->get_manager();

    if ($oldversion < 2015111802) {

        $table = new xmldb_table('local_cohort_automation');
        $field = new xmldb_field('fieldshortname', XMLDB_TYPE_CHAR, '255', null, null, null, null, '');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            // Map old id's to new shortnames.
            $oldfields = legacy_get_profile_fields(false);
            $records = $DB->get_records('local_cohort_automation');
            foreach ($records as $record) {
                $record->fieldshortname = $oldfields[$record->profilefieldid];
                $DB->update_record('local_cohort_automation', $record);
            }

            // Delete old index and field.
            $index = new xmldb_index('cohproreg');
            $index = new xmldb_index('cohortid-profilefieldid-regex', XMLDB_INDEX_UNIQUE, array('cohortid', 'profilefieldid', 'regex'));
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
            }

            $field = new xmldb_field('profilefieldid');
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }
    }

    return true;
}

