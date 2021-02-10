<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Results</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="reqwest.min.js"></script>
  <meta charset="UTF-8">
</head>
<body>

<?php
require 'imports.php';
$mysqli = dblogin();

$ordinal = intval($_POST['ordinal']);
$session_label = $_POST['session'];
$rank_string = $_POST['ranks'];
$submitted_player = $ordinal - 1;
insertRanks($mysqli, $session_label, $submitted_player, $rank_string);

$session_label_html = htmlspecialchars($session_label);
echo "<input id=\"session\" type=\"hidden\" value=\"$session_label_html\" />";

echo "<div id=\"playerRanks\"></div>";
echo "<div id=\"results\"></div>";
?>

<script>
reqwest({
    url: 'player_ranks',
    method: 'get',
    data: { session: document.getElementById('session').value }
}).then(function(response) {
    document.getElementById('playerRanks').innerHTML = response;
});

reqwest({
    url: 'optimization',
    method: 'get',
    data: { session: document.getElementById('session').value }
}).then(function (response) {
    document.getElementById('results').innerHTML = response;
});
</script>

<?php endDocument(); ?>