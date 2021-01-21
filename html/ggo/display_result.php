<!DOCTYPE html>
<html>
<head>
  <title>Game Group Organizer - Results</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="reqwest.min.js"></script>
</head>
<body>

<?php
require 'imports.php';
$mysqli = dblogin();

$ordinal = intval($mysqli->real_escape_string($_POST['ordinal']));
$session_label = $mysqli->real_escape_string($_POST['session']);
$rank_string = $mysqli->real_escape_string($_POST['ranks']);
$submitted_player = $ordinal - 1;
insertRanks($mysqli, $session_label, $submitted_player, $rank_string);

echo "<div id=\"playerRanks\"></div>";
echo "<div id=\"results\"></div>";
?>

<script>
reqwest({
    url: 'player_ranks.php',
    method: 'get',
    data: { session: '<? echo "$session_label"; ?>' }
}).then(function(response) {
    document.getElementById('playerRanks').innerHTML = response;
});

reqwest({
    url: 'calculated_result.php',
    method: 'get',
    data: { session: '<? echo "$session_label"; ?>' }
}).then(function (response) {
    document.getElementById('results').innerHTML = response;
});
</script>

<?php endDocument(); ?>