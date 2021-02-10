<?php
require 'imports.php';

$mysqli = dblogin();
$session = $_GET['text'];
if (strlen(trim($session)) == 0) {
    echo "<p>Please enter a group name.</p>";
} else if (fetchSessionId($mysqli, $session)) {
    echo "<p>A recent session called '" . htmlspecialchars($session) . "' already exists.</p>";
}
?>