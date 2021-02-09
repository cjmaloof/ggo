<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Results</title>
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

$expected_players = fetchPlayerCount($mysqli, $session_label);

echo "<div id=\"playerRanks\"></div>";
echo "<div id=\"results\"></div>";

?>

<script>

function allRanksReceived() {
    return document.getElementById('playersFetched').value == <? echo "$expected_players" ?>;
}

var interval = 2000;
var maxMinutes = 10;
var maxIterations = (maxMinutes * 60000) / interval;
var i = 1;
function updateResults() {
    reqwest({
        url: 'player_ranks',
        method: 'get',
        data: { session: '<? echo "$session_label"; ?>' }
    }).then(function(response) {
        document.getElementById('playerRanks').innerHTML = response;
        
        // Either write the final results to the page, or else keep polling until timeout is reached.
        if (allRanksReceived()) {
            reqwest({
                url: 'optimization',
                method: 'get',
                data: { session: '<? echo "$session_label"; ?>' }
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