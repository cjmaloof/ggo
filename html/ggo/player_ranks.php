<?php
require 'imports.php';
$mysqli = dblogin();

$session_label = $_GET['session'];
$session_id = fetchSessionId($mysqli, $session_label);

$input = fetchRanksByPlayer($mysqli, $session_id);
$playersFetched = count($input);
echo "<input type=\"hidden\" id=\"playersFetched\" value=\"$playersFetched\" />";
foreach ($input as $player) {
    $player_html = htmlspecialchars($player[0]);
    echo "<p><b>$player_html</b>: ";
    foreach (array_slice($player, 1, max(0, count($player)-2)) as $games) {
        echo joinSameRankGames($games) . "; then ";
    }
    echo joinSameRankGames(end($player)) . ".";
}

function joinSameRankGames($array) {
    $cssArray = array_map(function($s) { $g = htmlspecialchars($s); return "<span class=\"gamename\">$g</span>"; }, $array);
    if (count($cssArray) < 3) {
        return join(" or ", $cssArray);
    } else {
        return join(", ", array_slice($cssArray, 0, count($cssArray)-1)) . ", or " . end($cssArray);
    }
}