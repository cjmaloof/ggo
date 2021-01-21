<?php
require 'imports.php';

$MIN_GAMES = 2;
$MAX_GAMES = 12;

$lines = getTextLines($_GET['text']);
if (count($lines) < $MIN_GAMES) {
    echo "<p>Please enter at least $MIN_GAMES games.</p>";
}
if (count($lines) > $MAX_GAMES) {
    echo "<p>Please enter no more than $MAX_GAMES players.</p>";
}
if (count(array_unique($lines)) < count($lines)) {
    echo "<p>There are duplicate games.</p>";
}
?>