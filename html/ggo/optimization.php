<?php
require 'imports.php';
$mysqli = dblogin();

$session_label = $_GET['session'];
$session_id = fetchSessionId($mysqli, $session_label);
$output = calculateRanks($session_id);
echo "<p>$output</p>";
?>