<?php

namespace local_cohort_automation\task;

require_once("$CFG->dirroot/local/cohort_automation/lib.php");

class sync_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('pluginname', 'local_cohort_automation');
    }

    public function execute() {

        local_cohort_automation_task();

    }
}

