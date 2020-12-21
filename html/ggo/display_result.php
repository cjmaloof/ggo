<!DOCTYPE html>
<html>
<head>
  <title>Game Group Organizer - Results</title>
  <link rel="stylesheet" href="game_ranker.css">
</head>
<body>

<?php
require 'utils_local.php';
$mysqli = dblogin();

$ordinal = intval($mysqli->real_escape_string($_POST['ordinal']));
$session_label = $mysqli->real_escape_string($_POST['session']);
$rank_string = $mysqli->real_escape_string($_POST['ranks']);
$submitted_player = $ordinal - 1;
insertRanks($mysqli, $session_label, $submitted_player, $rank_string);

$session_id = fetchSessionId($mysqli, $session_label);

$input = fetchRanksByPlayer($mysqli, $session_id);
foreach ($input as $player) {
    echo "<p><b>$player[0]</b>: ";
    foreach (array_slice($player, 1, max(0, count($player)-2)) as $games) {
        echo joinSameRankGames($games) . "; then ";
    }
    echo joinSameRankGames(end($player)) . ".";
}

$output = calculateRanks($session_id);
echo "<p>$output</p>";

function joinSameRankGames($array) {
    $cssArray = array_map(function($s) { return "<span class=\"gamename\">$s</span>"; }, $array);
    if (count($cssArray) < 3) {
        return join(" or ", $cssArray);
    } else {
        return join(", ", array_slice($cssArray, 0, count($cssArray)-1)) . ", or " . end($cssArray);
    }
}
?>

<?php endDocument(); ?>