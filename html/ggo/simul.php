<!DOCTYPE html>
<html>
<head>
  <title>Game Group Organizer - Setup</title>
  <link rel="stylesheet" href="game_ranker.css">
</head>

<?php require 'utils_local.php'; ?>

<body>
<h1>Create a group</h1>
<div>
    <a href="simul_join.php">Click here to join an existing session</a>
</div>
<br />
<form id="createSessionForm" action="" method="POST">
    <div>
        <label for="session">New session name:</label> <input id="session" name="session" type="text" size="12" />
    </div>
    <br />
    <div>
        <label for="playerCount">Player count:</label>
        <select id="playerCount" name="playerCount" class="playerCount">
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
            <option value="7">7</option>
            <option value="8">8</option>
            <option value="9">9</option>
            <option value="10">10</option>
        </select>
    </div>
    <br />
    <div>
        <label for="games">Games (enter one per line):</label> <br />
        <textarea id="games" name="games" rows="10" cols="40"></textarea>
    </div>
    <input id="submit" type="submit" onclick="appendFormAction()">
</form>

<script>
// Add pre-submit validation of counts and line lengths (255 max)
function appendFormAction() {
    document.getElementById("createSessionForm").action = "simul_join.php?session=" + document.getElementById("session").value;
}
</script>

<?php endDocument(); ?>