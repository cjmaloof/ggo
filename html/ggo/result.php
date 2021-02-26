<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Results</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="js/reqwest.min.js"></script>
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
$expected_players = fetchPlayerCount($mysqli, $session_label);
echo "<input id=\"expectedPlayers\" type=\"hidden\" value=\"$expected_players\" />";

echo "<div id=\"playerRanks\"></div>";
echo "<div id=\"results\"></div>";

?>

<script>

function allRanksReceived() {
    return document.getElementById('playersFetched').value >= document.getElementById('expectedPlayers').value;
}

var interval = 2000;
var maxMinutes = 10;
var maxIterations = (maxMinutes * 60000) / interval;
var i = 1;
function updateResults() {
    reqwest({
        url: 'player_ranks',
        method: 'get',
        data: { session: document.getElementById('session').value }
    }).then(function(response) {
        document.getElementById('playerRanks').innerHTML = response;
        
        // Either write the final results to the page, or else keep polling until timeout is reached.
        if (allRanksReceived()) {
            reqwest({
                url: 'optimization',
                method: 'get',
                data: { session: document.getElementById('session').value }
            }).then(function (response) {
                document.getElementById('results').innerHTML = response;
            });
        } else if (i < maxIterations) {
            i++;
            setTimeout(updateResults, interval);
        } else {
            document.getElementById('results').innerHTML = '<p>Timed out waiting for players.</p>';
        }
    })
}

updateResults();

</script>

<?php endDocument(); ?>