<?php
require 'imports.php';

$MIN_PLAYERS = 4;
$MAX_PLAYERS = 15;

$lines = getTextLines($_GET['text']);
if (count($lines) < $MIN_PLAYERS) {
    echo "<p>Please enter at least $MIN_PLAYERS players.</p>";
}
if (count($lines) > $MAX_PLAYERS) {
    echo "<p>Please enter no more than $MAX_PLAYERS players.</p>";
}
if (count(array_unique($lines)) < count($lines)) {
    echo "<p>There are duplicate player names.</p>";
}
?>