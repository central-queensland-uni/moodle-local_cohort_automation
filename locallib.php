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
 * Internal library of functions for module local_cohort_automation
 *
 * @package    local_cohort_automation
 * @copyright  2014 NetSpot Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * retrieve a list of cohort mappings
 *
 * @return array of cohort mappings
 */
function get_cohort_mappings() {
    global $DB;

    return $DB->get_records_sql("
         SELECT a.id,
                a.cohortid,
                c.name,
                a.regex,
                a.fieldshortname
           FROM {local_cohort_automation} a
           JOIN {cohort} c ON c.id =  a.cohortid
       ORDER BY c.name"
    );
}

/**
 * retrieve a list of users not in the specified cohort
 * with user names matching the supplied regex
 *
 * @param $cohortid the id number of the cohort
 * @param $profilefieldid the id number of the profile field
 * @param $regex the regex to apply to the username
 * @param $test If false shows a list of user to be removed
 *
 * @return recordset of users not in the cohort
 */
function get_users_not_in_cohort($cohortid, $profilefield, $regex, $test = true) {
    global $DB;

    $not = $test ? 'NOT' : '';

    $fields = local_cohort_automation_get_profile_fields();

    $columns = array('username', 'idnumber', 'institution', 'department');

    if (!in_array($profilefield, $columns)) {
        $sql = "SELECT u.id
                  FROM {user} u
                  JOIN {user_info_field} f ON f.shortname = '$profilefield'
                  JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = f.id
                 WHERE u.id $not IN (SELECT cm.userid
                                       FROM {cohort_members} cm
                                      WHERE cohortid = ?)
                   AND d.data " . $DB->sql_regex($test) . " ?
                   AND u.deleted <> 1
                   AND u.suspended <> 1";
    } else {
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE u.id $not IN (SELECT cm.userid
                                       FROM {cohort_members} cm
                                      WHERE cohortid = ?)
                   AND u.$profilefield " . $DB->sql_regex($test) . " ?
                   AND u.deleted <> 1
                   AND u.suspended <> 1";
    }

    try {
        return $DB->get_recordset_sql($sql, array($cohortid, $regex));
    } catch (Exception $e) {
        mtrace('Exception thrown while trying to find users not in a cohort: ' . $e->getMessage());
        return array();
    }
}

/**
 * retrieve a list of users who are in the specified cohort
 * with user names matching the supplied regex
 *
 * @param $cohortid the id number of cohorot
 * @param $profilefieldid the id number of the profile field
 * @param $regex the regex to apply to the username
 *
 * @return recordset of users in the cohort
 */
function get_users_in_cohort($cohortid, $profilefield, $regex) {
    global $DB;

    $columns = array('username', 'idnumber', 'institution', 'department');

    if (!in_array($profilefield, $columns)) {
        $sql = 'SELECT u.id
                  FROM {user} u
                  JOIN {user_info_field} f ON f.shortname = \'' . $profilefield . '\'
                  JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = f.id
                 WHERE u.id IN (SELECT cm.userid
                                  FROM {cohort_members} cm
                                 WHERE cohortid = ?)
                  AND d.data ' . $DB->sql_regex(true) . ' ?';
    } else {
        $sql = 'SELECT u.id
                  FROM {user} u
                 WHERE u.id IN (SELECT cm.userid
                                  FROM {cohort_members} cm
                                 WHERE cohortid = ?)
                   AND u.' . $profilefield . ' ' . $DB->sql_regex(true) . ' ?';
    }

    try {
        return $DB->get_recordset_sql($sql, array($cohortid, $regex));
    } catch (Exception $e) {
        mtrace('Exception thrown while trying to find users in a cohort: ' . $e->getMessage());
        return array();
    }
}

/**
 * retrieve a list of profile fields that can be matched against
 *
 * THIS FUNCTION IS DEPRECATED. It is only still present to allow
 * clean migration in the upgrade script.
 *
 * @param $fordisplay generate the list of fields for display
 *
 * @return array of possible profile fields
 */
function legacy_get_profile_fields($fordisplay=true) {

    global $DB;

    // Define master array.
    /*
     * index:   a unique non repeating index number
     * display: the name of the field for display
     * field:   tha name of the field for queries
     */
    $master = array(
        array(
            'index' => '1',
            'display' => 'Username',
            'field' => 'username'
           ),
    );

    $c = 2;
    if ($columns = $DB->get_columns('user')) {
        sort($columns);
        $whitelist = array('idnumber', 'institution', 'department');
        foreach ($columns as $column) {
            if (in_array($column->name, $whitelist)) {
                $master[] = array(
                    'index'   => $c++,
                    'display' => get_string($column->name),
                    'field'   => $column->name,
                );
            }
        }
    }

    $c = 100;

    if ($fields = $DB->get_records('user_info_field')) {
        foreach ($fields as $field) {
                $master[] = array(
                    'index'   => $c++,
                    'display' => $field->name,
                    'field'   => $field->shortname,
                );
        }
    }

    // Output required array based on master array.
    if ($fordisplay) {
        $tmp = array();

        foreach ($master as $item) {
            $tmp[$item['index']] = $item['display'];
        }
    } else {
        $tmp = array();

        foreach ($master as $item) {
            $tmp[$item['index']] = $item['field'];
        }
    }

    return $tmp;
}
