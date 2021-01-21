<!DOCTYPE html>
<html>
<head>
  <title>Game Group Organizer - Setup</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="reqwest.min.js"></script>
  <script src="validation.js"></script>
</head>

<?php require 'imports.php'; ?>

<body>
<h1>Create a group</h1>
<div>
    <a href="simul_join.php">Click here to join an existing session</a>
</div>
<br />
<form id="form" action="" method="POST">
    <div>
        <label for="session">New session name:</label> <input id="session" name="session" type="text" size="12" />
    </div>
    <div id="sessionErrors" class="errors"></div>
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
    <div id="gameErrors" class="errors"></div>
    
    <button type="button" onclick="validateAndSubmit()">Submit</button>
</form>

<script>
function appendFormAction() {
    document.getElementById("form").action = "simul_join.php?session=" + document.getElementById("session").value;
}

function validateAndSubmit() {
    Promise.all([validateSession(), validateGames()])
    .then(function([sessionOk, gamesOk]) {
        if (sessionOk && gamesOk) {
            document.getElementById("form").action = "simul_join.php?session=" + document.getElementById("session").value;
            document.getElementById('form').submit();
        }
    });
}

function validateSession() {
    return ajaxValidate('validate_new_session.php', document.getElementById('session').value, document.getElementById('sessionErrors'));
}

function validateGames() {
    return ajaxValidate('validate_games.php', document.getElementById('games').value, document.getElementById('gameErrors'));
}
</script>

<?php endDocument(); ?>