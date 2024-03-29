<?php require 'imports.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Rank Games</title>
  <?php require 'common_head.php'; ?>
  <script src="js/DragDropTouch.js"></script>
</head>
<body>

<?php
$mysqli = dblogin();
$session_label = isset($_POST['session']) ? $_POST['session'] : uniqid();
$session_label_html = htmlspecialchars($session_label);
$simul = !isset($_POST['ordinal']);

if ($simul) {
    // Don't insert the player yet, in case they fail to complete the ranking
    $current_player = $_POST['player'];
    $ordinal = -1; // not used
    
    $games = fetchGames($mysqli, $session_label);
} else {
    // $ordinal is the ID of the current player, not the one who just submitted.
    $ordinal = intval($_POST['ordinal']);

    if ($ordinal === 0) {
        // Initial setup of session, players, and games
        $players = getTextLines($_POST['players']);
        $games = getTextLines($_POST['games']);
        $tableCount = intval($_POST['tableCount']);
        
        $current_player = $players[0];
        $next_player = $players[1]; // for testing
        
        insertSession($mysqli, $session_label, 0, count($players), $tableCount, 30);
        insertPlayers($mysqli, $players);
        insertGames($mysqli, $games);

    } else {
        // Handle submission of previous player
        $rank_string = $_POST['ranks'];
        $submitted_player = $ordinal - 1;
        insertRanks($mysqli, $session_label, $submitted_player, $rank_string);
        
        // Fetch current and next player
        $current_players = fetchCurrentAndNextPlayer($mysqli, $session_label, $ordinal);
        $current_player = $current_players[0];
        $next_player = count($current_players) == 1 ? NULL : $current_players[1];
        
        // Fetch games sorted by ordinal
        $games = fetchGames($mysqli, $session_label);
    }
}
$next_ordinal = $ordinal + 1;

$current_player_html = htmlspecialchars($current_player);
echo "<p><b>$current_player_html</b>, rank your games:</p>\n";
echo "<p class=\"rankhelp\">Drag to drop or swap. Empty rows are ignored.</p>\n";

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
<input id="draggingSpan" type="hidden"/>

<script>
function dragstart_handler(ev) {
    ev.dataTransfer.setData("text/plain", ev.target.id);
    document.getElementById("dragSource").value = ev.target.parentNode.id;
    document.getElementById("draggingSpan").value = ev.target.id;
}

function dragover_handler(ev) {
    ev.preventDefault();
    ev.dataTransfer.dropEffect = "move";
}

function span_dragenter_handler(ev) {
    if (document.getElementById("draggingSpan").value != ev.target.id) {
        styleTargetSpan(ev.target);
    }
}

function span_dragleave_handler(ev) {
    if (document.getElementById("draggingSpan").value != ev.target.id) {
        unstyleSpan(ev.target);
    }
}

function td_dragenter_handler(ev) {
    if (ev.target.classList.contains('rankcell') && document.getElementById("dragSource").value != ev.target.id) {
        styleTargetTd(ev.target);
    }
}

function td_dragleave_handler(ev) {
    if (ev.target.classList.contains('rankcell') && document.getElementById("dragSource").value != ev.target.id) {
        unstyleTd(ev.target);
    }
}

function td_drop_handler(ev) {
    ev.preventDefault();
    // Get the id of the target and add the moved element to the target's DOM
    const data = ev.dataTransfer.getData("text/plain");
    ev.currentTarget.appendChild(document.getElementById(data));
    unstyleTd(ev.currentTarget);
    document.getElementById("dragSource").value = "";
    document.getElementById("draggingSpan").value = "";
}

function span_drop_handler(ev) {
    ev.preventDefault();
    // Move the target to the source cell
    document.getElementById(document.getElementById("dragSource").value).appendChild(ev.currentTarget);
    unstyleSpan(ev.currentTarget);
    document.getElementById("dragSource").value = "";
    document.getElementById("draggingSpan").value = "";
}

function styleTargetSpan(span) {
    span.classList.add('targeted');
}

function unstyleSpan(span) {
    span.classList.remove('targeted');
}

function styleTargetTd(td) {
    td.classList.add('targeted');
}

function unstyleTd(td) {
    td.classList.remove('targeted');
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
    if (games == null || games.length != <?php echo count($games); ?>) {
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
        game.addEventListener("dragenter", span_dragenter_handler);
        game.addEventListener("dragleave", span_dragleave_handler);
    });
    document.querySelectorAll('.games td.rankcell').forEach(td => {
        td.addEventListener("dragover", dragover_handler);
        td.addEventListener("drop", td_drop_handler);
        td.addEventListener("dragenter", td_dragenter_handler);
        td.addEventListener("dragleave", td_dragleave_handler);
    });
});

</script>


<?php endDocument(); ?>