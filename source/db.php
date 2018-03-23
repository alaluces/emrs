<?php

$host   = 'emrs-db';
$dbname = 'emrs';
$user   = 'emrs';
$pass   = '1234';

$DBH    = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass); 
?>
