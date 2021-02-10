<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Join group</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="reqwest.min.js"></script>
  <script src="validation.js"></script>
  <meta charset="UTF-8">
</head>

<?php
require 'imports.php';
$mysqli = dblogin();
if (isset($_POST['session']) && !fetchSessionId($mysqli, $_POST['session'])) {
    
    $playerCount = intval($_POST['playerCount']);
    $games = getTextLines($_POST['games']);
    
    insertSession($mysqli, $_POST['session'], 1, $playerCount);
    insertGames($mysqli, $games);
    
    $session_html = htmlspecialchars($_POST['session']);
    $group_input_attrs = "value=\"$session_html\"";
    $player_input_attrs = "autofocus";
} else if (isset($_GET['session'])) {
    $session_html = htmlspecialchars($_GET['session']);
    $group_input_attrs = "value=\"$session_html\"";
    $player_input_attrs = "autofocus";
} else {
    $group_input_attrs = "autofocus";
    $player_input_attrs = "";
}
?>

<body>
<h2>Join a group</h2>
<form id="form" action="rank" method="POST">
    <div>
        <label for="session">Group name:</label> <input id="session" name="session" type="text" size="12" <? echo $group_input_attrs; ?> />
    </div>
    <div id="sessionErrors" class="errors"></div>
    <br />
    <div>
        <label for="player">Your name:</label> <input id="player" name="player" type="text" size="10" <? echo $player_input_attrs; ?> />
    </div>
    <div id="playerErrors" class="errors"></div>
    <br />
    <button type="button" onclick="validateAndSubmit()">Submit</button>
</form>

<script>
function validateAndSubmit() {
    var playerOk = validatePlayer();
    validateSession().then(function(sessionOk) {
        if (sessionOk && playerOk) {
            document.getElementById("form").submit();
        }
    });
}

function validateSession() {
    return ajaxValidate("validate_existing_session", 
    { text : document.getElementById("session").value }, 
    document.getElementById("sessionErrors"));
}

function validatePlayer() {
    // Two players with the same name is OK, I guess.
    if (document.getElementById("player").value.trim() === "") {
        document.getElementById("playerErrors").innerHTML = "<p>Please enter your name.</p>";
        return false;
    } else {
        document.getElementById("playerErrors").innerHTML = "";
        return true;
    }
}
</script>

<?php endDocument(); ?>