<?php
require 'utils_local.php';
$mysqli = dblogin();

$session_label = $mysqli->real_escape_string($_GET['session']);
$session_id = fetchSessionId($mysqli, $session_label);
$output = calculateRanks($session_id);
echo "<p>$output</p>";
?>