<?php

session_start();

require 'model_sec.php';
require 'model_appointment.php';
require 'model_treatment.php';
require 'model_person.php';
require 'model_laboratory.php';
require 'model_presc.php';
require 'model_meds.php';
require 'model_billing.php';

$sec         = new lib_sec($DBH, $app);
$person      = new lib_person($DBH);
$appointment = new lib_appointment($DBH);
$treatment   = new lib_treatment($DBH);
$lab         = new lib_laboratory($DBH);
$presc       = new lib_prescriptions($DBH);
$meds        = new lib_medications($DBH);
$billing     = new lib_billing($DBH);
$misc        = new lib_misc();


require 'route_sec.php';
require 'route_treatments.php';
require 'route_appointments.php';
require 'route_patients.php';
require 'route_reports.php';
require 'route_tools.php';
require 'route_lab_results.php';
require 'route_presc.php';
require 'route_meds.php';
require 'route_billing.php';   

?>