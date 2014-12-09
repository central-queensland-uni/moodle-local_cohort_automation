<?php

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once('lib.php');
require_once('locallib.php');

local_cohort_automation_cron ();

