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
 * Defines the settings page of local_cohort_automation
 *
 * @package    local_cohort_automation
 * @copyright  2014 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

require_once('mappings_form.php');
require_once('locallib.php');

// Be mindful of security.
require_login();
$context = context_system::instance();
require_capability('moodle/cohort:manage', $context);

// Setup the admin page.
admin_externalpage_setup('cohort_automation');

// Determine what action, if any, to take.
$action = optional_param('action', null, PARAM_ALPHANUMEXT);
$error = null;

if ($action == 'add') {
    // Add a new mapping.

    require_sesskey();

    $record = new stdClass();
    $record->id = optional_param('id', null, PARAM_INT);
    $record->cohortid   = required_param('cohortid', PARAM_INT);
    $record->fieldshortname = required_param('fieldshortname', PARAM_TEXT);
    $record->regex = required_param('regex', PARAM_TEXT);

    // Stop whitespace inadvertantly making regex fail.
    $record->regex = trim($record->regex);


    try {
        if (empty($record->id)) {
            $DB->insert_record('local_cohort_automation', $record);
        } else {
            $DB->update_record('local_cohort_automation', $record);
        }

        redirect(new moodle_url('/local/cohort_automation/mappings.php'));
    } catch (Exception $e) {
        $error = get_string('recordexists', 'local_cohort_automation');
    }

}

if ($action == 'delete') {

    // Delete a mapping.
    require_sesskey();
    $id = required_param('id', PARAM_INT);

    $mapping = $DB->get_record('local_cohort_automation', array('id' => $id));

    // Use the mapping if it was found.
    if ($mapping) {
        try {

            $users = get_users_in_cohort($mapping->cohortid, $mapping->fieldshortname, $mapping->regex);

            // Remove the users from the cohort..
            require_once("$CFG->dirroot/cohort/lib.php");

            foreach ($users as $user) {
                cohort_remove_member($mapping->cohortid, $user->id);
            }

            // Delete the mapping record itself.
            $DB->delete_records('local_cohort_automation', array('id' => $id));

            redirect(new moodle_url('/local/cohort_automation/mappings.php'));
        } catch (Exception $e) {
            $error = get_string('errorremovemembers', 'local_cohort_automation');
        }
    }
}

$cohort = null;

if ($action == 'edit') {
    require_sesskey();
    $id   = required_param('id', PARAM_INT);
    $cohort = (array)$DB->get_record('local_cohort_automation', array('id' => $id));
}

// Output the page header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_cohort_automation'), 1, '', '');
echo $OUTPUT->heading(get_string('newmappingheader', 'local_cohort_automation'), 2, '', '');

// Output the new mapping form.
$settingsform = new mappings_settings_form(null, array('error' => $error));

$settingsform->set_data($cohort);
$settingsform->display();

// List all existing mappings.
echo $OUTPUT->heading(get_string('existingmappingheader', 'local_cohort_automation'), 2, '', '');

$table = new html_table();
$table->head = array(
    get_string('cohorttable', 'local_cohort_automation'),
    get_string('membercounttable', 'local_cohort_automation'),
    get_string('fieldname', 'local_cohort_automation'),
    get_string('fieldlabel', 'local_cohort_automation'),
    get_string('regextable', 'local_cohort_automation'),
    get_string('actions', 'local_cohort_automation')
);

$records = get_cohort_mappings();
$profilefields = local_cohort_automation_get_profile_fields();

if (count($records) > 0) {

    $mappings = array();

    foreach ($records as $record) {

        $mappings[] = array(
            $record->name,
            $OUTPUT->action_link(
                new moodle_url( '/cohort/assign.php', array(
                    'id' => $record->cohortid,
                )),
                $DB->count_records('cohort_members', array('cohortid' => $record->cohortid))
            ),
            $record->fieldshortname,
            $profilefields[$record->fieldshortname],
            $record->regex,
            $OUTPUT->action_link(
                new moodle_url( '/local/cohort_automation/mappings.php', array(
                    'action' => 'edit',
                    'id' => $record->id,
                    'sesskey' => sesskey(),
                )),
                get_string('editlink', 'local_cohort_automation')
            )
            . ' | ' .
            $OUTPUT->action_link(
                new moodle_url( '/local/cohort_automation/mappings.php', array(
                    'action' => 'delete',
                    'id' => $record->id,
                    'sesskey' => sesskey(),
                )),
                get_string('deletelink', 'local_cohort_automation')
            )
        );

    }

    $table->data = $mappings;
}

echo html_writer::table($table);

// Output the page footer.
echo $OUTPUT->footer();
