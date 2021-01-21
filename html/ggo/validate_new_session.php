<?php
require 'imports.php';

$mysqli = dblogin();
$session = $mysqli->real_escape_string($_GET['text']);
if (fetchSessionId($mysqli, $session)) {
    echo "<p>A recent session called '" . $_GET['text'] . "' already exists.</p>";
}
?>