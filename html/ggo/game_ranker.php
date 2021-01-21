<!DOCTYPE html>
<html>
<head>
  <title>Game Group Organizer - Rank Games</title>
  <link rel="stylesheet" href="game_ranker.css">
</head>
<body>

<?php
require 'imports.php';
$mysqli = dblogin();
$session_label = isset($_POST['session']) ? $mysqli->real_escape_string($_POST['session']) : uniqid();
$simul = !isset($_POST['ordinal']);

if ($simul) {
    $current_player = $mysqli->real_escape_string($_POST['player']);
    
    // Fetch games
    $games = fetchGames($mysqli, $session_label);
    
    // Insert player
    $session_id = fetchSessionId($mysqli, $session_label);
    $ordinal = insertPlayer($mysqli, $session_id, $current_player);
} else {
    $ordinal = intval($mysqli->real_escape_string($_POST['ordinal']));

    if ($ordinal === 0) {
        # Initial setup of session, players, and games
        $players = sanitizeArray($mysqli, preg_split("/\r\n|\n|\r/", $_POST['players']));
        $games = sanitizeArray($mysqli, preg_split("/\r\n|\n|\r/", $_POST['games']));
        
        $current_player = $players[0];
        $next_player = $players[1]; // for testing
        
        insertSession($mysqli, $session_label, 0, count($players));
        insertPlayers($mysqli, $players);
        insertGames($mysqli, $games);

    } else {
        # Handle submission of previous player
        $rank_string = $mysqli->real_escape_string($_POST['ranks']);
        $submitted_player = $ordinal - 1;
        insertRanks($mysqli, $session_label, $submitted_player, $rank_string);
        
        # Fetch current and next player
        $current_players = fetchCurrentAndNextPlayer($mysqli, $session_label, $ordinal);
        $current_player = $current_players[0];
        $next_player = count($current_players) == 1 ? NULL : $current_players[1];
        
        # Fetch games sorted by ordinal
        $games = fetchGames($mysqli, $session_label);
    }
}
$next_ordinal = $ordinal + 1;

echo "<p><b>$current_player</b>, rank your games:</p>\n";

echo "<table id=\"gameTable\" class=\"games\">\n";
for ($i = 0; $i < count($games); $i++) {
  $game = $games[$i];
  $row_label = $i + 1;
  echo "<tr><td class=\"numlabel\">$row_label</td><td class=\"rankcell\" id=\"rank$i\"><span id=\"game$i\" class=\"gamespan\" draggable=\"true\">$game </span></td></tr>\n";
}
echo "<tr><td class=\"numlabel\"><img src=\"images/trash.png\" width=\"20\" /></td><td class=\"rankcell\" id=\"rank$i\"></td></tr>\n";
echo "</table>\n";

if ($simul) {
    $action = "simul_result.php";
    $button_label = "Submit";
} else if (is_null($next_player)) {
    $action = "display_result.php";
    $button_label = "Optimize!";
} else {
    $action = "game_ranker.php";
    $button_label = "Continue to $next_player";
}

echo <<<EOT
<br />
<form action="$action" method="POST">
    <input type="hidden" id="ranks" name="ranks" value="" />
    <input type="hidden" id="session" name="session" value="$session_label" />
    <input type="hidden" id="ordinal" name="ordinal" value="$next_ordinal" />
    <input type="submit" onclick="scrapeOutput()" value="$button_label" />
</form>
EOT;
?>

<input id="dragSource" type="hidden"/>

<script>
function dragstart_handler(ev) {
    ev.dataTransfer.setData("text/plain", ev.target.id);
    document.getElementById("dragSource").value = ev.target.parentNode.id;
    ev.dataTransfer.dropEffect = "move";
}

function dragover_handler(ev) {
    ev.preventDefault();
    ev.dataTransfer.dropEffect = "move";
}

function drop_handler(ev) {
    ev.preventDefault();
    // Get the id of the target and add the moved element to the target's DOM
    const data = ev.dataTransfer.getData("text/plain");
    ev.currentTarget.appendChild(document.getElementById(data));
}

function span_drop_handler(ev) {
    ev.preventDefault();
    // Move the target to the source cell
    document.getElementById(document.getElementById("dragSource").value).appendChild(ev.currentTarget);
}

function scrapeOutput() {
    var gamesAtEachRank = [];
    document.getElementById("gameTable").querySelectorAll("td.rankcell").forEach(td => {
        var gamesAtThisRank = [];
        td.querySelectorAll(".gamespan").forEach(span => {
            gamesAtThisRank.push(span.id.substring(4));
        });
        gamesAtEachRank.push(gamesAtThisRank.join(','));
    });
    document.getElementById("ranks").value = gamesAtEachRank.join(";");
}

window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.gamespan').forEach(game => {
        game.addEventListener("dragstart", dragstart_handler);
        game.addEventListener("drop", span_drop_handler);
    });
    document.querySelectorAll('.games td').forEach(td => {
        td.addEventListener("dragover", dragover_handler);
        td.addEventListener("drop", drop_handler);
    });
});

</script>


<?php endDocument(); ?>