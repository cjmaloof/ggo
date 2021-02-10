<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Setup</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="reqwest.min.js"></script>
  <script src="validation.js"></script>
  <meta charset="UTF-8">
</head>

<?php require 'imports.php'; ?>

<body>
<h1>Create a group</h1>
<form id="form" action="rank" method="POST">
    <div>
        <label for="players">Players (enter one per line):</label> <br />
        <textarea id="players" name="players" rows="10" cols="40" autofocus></textarea>
    </div>
    <div id="playerErrors" class="errors"></div>
    <br />
    <div>
        <label for="games">Games (enter one per line):</label> <br />
        <textarea id="games" name="games" rows="10" cols="40"></textarea>
    </div>
    <div id="gameErrors" class="errors"></div>
    <input type="hidden" id="ordinal" name="ordinal" value="0" />
    
    <button type="button" onclick="validateAndSubmit()">Submit</button>
</form>

<script>
function validateAndSubmit() {
    Promise.all([validatePlayers(), validateGames()])
    .then(function([playersOk, gamesOk]) {
        if (playersOk && gamesOk) {
            document.getElementById("form").submit();
        }
    });
}

function validatePlayers() {
    return ajaxValidate("validate_players", 
        { text : document.getElementById("players").value }, 
        document.getElementById("playerErrors"));
}

function validateGames() {
    return ajaxValidate("validate_games", 
        { text : document.getElementById("games").value }, 
        document.getElementById("gameErrors"));
}

</script>

<?php endDocument(); ?>