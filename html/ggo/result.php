<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Results</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="js/reqwest.min.js"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<?php
require 'header.php';
require 'imports.php';
$mysqli = dblogin();

if ($_POST) {
    $current_player = $_POST['current_player'];
    $session_label = $_POST['session'];
    $rank_string = $_POST['ranks'];
    
    // Insert player and rankings
    $session_id = fetchSessionId($mysqli, $session_label);
    $ordinal = insertPlayer($mysqli, $session_id, $current_player);
    insertRanks($mysqli, $session_label, $ordinal, $rank_string);
    
    // Redirect so the user can refresh the page without creating a new player
    header( "Location: {$_SERVER['REQUEST_URI']}?session=$session_label", true, 303 );
} else {
    $session_label = $_GET['session'];
    $session_label_html = htmlspecialchars($session_label);
    
    if (!fetchSessionId($mysqli, $session_label)) {
        echo "<p>There is no recent group called '$session_label_html'.</p>";
    } else {
        echo "<input id=\"session\" type=\"hidden\" value=\"$session_label_html\" />";
        $expected_players = fetchPlayerCount($mysqli, $session_label);
        echo "<input id=\"expectedPlayers\" type=\"hidden\" value=\"$expected_players\" />";

        echo "<div id=\"playerRanks\"></div>";
        echo "<div id=\"results\"></div>";
    }
}

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
            document.getElementById('results').innerHTML = "<div class=\"errors\"><p>Timed out waiting for players. You can refresh the page if you're still waiting.</p></div>";
        }
    })
}

if (document.getElementById('session')) {
    updateResults();
}
</script>

<?php endDocument(); ?>