<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Rank Games</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="js/DragDropTouch.js"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<?php
require 'imports.php';
$mysqli = dblogin();
$session_label = isset($_POST['session']) ? $_POST['session'] : uniqid();
$session_label_html = htmlspecialchars($session_label);
$simul = !isset($_POST['ordinal']);

if ($simul) {
    // Don't insert the player yet, in case they fail to complete the ranking
    $current_player = $_POST['player'];
    $ordinal = -1; // not used
    
    // Fetch games
    $games = fetchGames($mysqli, $session_label);
} else {
    $ordinal = intval($_POST['ordinal']);

    if ($ordinal === 0) {
        # Initial setup of session, players, and games
        $players = getTextLines($_POST['players']);
        $games = getTextLines($_POST['games']);
        
        $current_player = $players[0];
        $next_player = $players[1]; // for testing
        
        insertSession($mysqli, $session_label, 0, count($players));
        insertPlayers($mysqli, $players);
        insertGames($mysqli, $games);

    } else {
        # Handle submission of previous player
        $rank_string = $_POST['ranks'];
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

$current_player_html = htmlspecialchars($current_player);
echo "<p><b>$current_player_html</b>, rank your games:</p>\n";

echo "<table id=\"gameTable\" class=\"games\">\n";
for ($i = 0; $i < count($games); $i++) {
  $game_html = htmlspecialchars($games[$i]);
  $row_label = $i + 1;
  echo "<tr><td class=\"numlabel\">$row_label</td><td class=\"rankcell\" id=\"rank$i\"><span id=\"game$i\" class=\"gamespan\" draggable=\"true\">$game_html</span></td></tr>\n";
}
echo "<tr><td class=\"numlabel\"><img src=\"images/trash.png\" width=\"20\" /></td><td class=\"rankcell\" id=\"rank$i\"></td></tr>\n";
echo "</table>\n";
echo "<div id=\"rankErrors\" class=\"errors\"></div>";

if ($simul) {
    $action = "result";
    $button_label_html = "Submit";
} else if (is_null($next_player)) {
    $action = "result1";
    $button_label_html = "Optimize!";
} else {
    $action = "rank";
    $next_player_html = htmlspecialchars($next_player);
    $button_label_html = "Continue to $next_player_html";
}

echo <<<EOT
<br />
<form id="form" action="$action" method="POST">
    <input type="hidden" id="ranks" name="ranks" value="" />
    <input type="hidden" id="session" name="session" value="$session_label_html" />
    <input type="hidden" id="current_player" name="current_player" value="$current_player_html" />
    <input type="hidden" id="ordinal" name="ordinal" value="$next_ordinal" />
    <input type="button" onclick="validateAndSubmit()" value="$button_label_html" />
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

// Returns ranks separated by semicolons, with games in the same rank separated by commas
function scrapeOutput() {
    var gamesAtEachRank = [];
    document.getElementById("gameTable").querySelectorAll("td.rankcell").forEach(td => {
        var gamesAtThisRank = [];
        td.querySelectorAll(".gamespan").forEach(span => {
            gamesAtThisRank.push(span.id.substring(4));
        });
        gamesAtEachRank.push(gamesAtThisRank.join(','));
    });
    return gamesAtEachRank.join(";");
}

function validateAndSubmit() {
    var ranks = scrapeOutput();
    if (validateRanks(ranks)) {
        document.getElementById("ranks").value = ranks;
        document.getElementById("form").submit();
    }
}

function validateRanks(ranks) {
    var games = ranks.match(/[0-9]+/g);
    if (games == null || games.length != <? echo count($games); ?>) {
        document.getElementById("rankErrors").innerHTML = "<p>Something went wrong! Please refresh the page and try again.</p>";
        return false;
    } else {
        var trashed = ranks.substr(ranks.lastIndexOf(";")).match(/[0-9]+/g);
        if (trashed != null && games.length == trashed.length) {
            document.getElementById("rankErrors").innerHTML = "<p>Please rank at least one game.</p>";
            return false;
        }
    }
    document.getElementById("rankErrors").innerHTML = "";
    return true;
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