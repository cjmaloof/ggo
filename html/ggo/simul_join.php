<!DOCTYPE html>
<html>
<head>
  <title>Game Group Organizer - Join group</title>
  <link rel="stylesheet" href="game_ranker.css">
</head>

<?php
require 'imports.php';
$mysqli = dblogin();
if (isset($_POST['session'])) {
    // Could add validation that no recent session exists with the same name
    
    $session = $mysqli->real_escape_string($_POST['session']);
    $playerCount = intval($mysqli->real_escape_string($_POST['playerCount']));
    $games = sanitizeArray($mysqli, getTextLines($_POST['games']));
    
    insertSession($mysqli, $session, 1, $playerCount);
    insertGames($mysqli, $games);
    
    $group_input_attrs = "value=\"$session\"";
    $player_input_attrs = "autofocus";
} else if (isset($_GET['session'])) {
    $session = $mysqli->real_escape_string($_GET['session']);
    $group_input_attrs = "value=\"$session\"";
    $player_input_attrs = "autofocus";
} else {
    $group_input_attrs = "autofocus";
    $player_input_attrs = "";
}
?>

<body>
<h2>Join a group</h2>
<form action="game_ranker.php" method="POST">
    <div>
        <label for="session">Group name:</label> <input id="session" name="session" type="text" size="12" <? echo $group_input_attrs; ?> />
    </div>
    <br />
    <div>
        <label for="player">Your name:</label> <input id="player" name="player" type="text" size="10" <? echo $player_input_attrs; ?> />
    </div>
    <br />
    <input type="submit">
</form>

<script>
// Add pre-submit validation that fields are filled
</script>

<?php endDocument(); ?>