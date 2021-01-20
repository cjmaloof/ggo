<?php
require 'utils_local.php';
$mysqli = dblogin();

$session_label = $mysqli->real_escape_string($_GET['session']);
$session_id = fetchSessionId($mysqli, $session_label);

$input = fetchRanksByPlayer($mysqli, $session_id);
$playersFetched = count($input);
echo "<input type=\"hidden\" id=\"playersFetched\" value=\"$playersFetched\" />";
foreach ($input as $player) {
    echo "<p><b>$player[0]</b>: ";
    foreach (array_slice($player, 1, max(0, count($player)-2)) as $games) {
        echo joinSameRankGames($games) . "; then ";
    }
    echo joinSameRankGames(end($player)) . ".";
}

function joinSameRankGames($array) {
    $cssArray = array_map(function($s) { return "<span class=\"gamename\">$s</span>"; }, $array);
    if (count($cssArray) < 3) {
        return join(" or ", $cssArray);
    } else {
        return join(", ", array_slice($cssArray, 0, count($cssArray)-1)) . ", or " . end($cssArray);
    }
}