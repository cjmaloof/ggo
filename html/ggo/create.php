<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Setup</title>
  <link rel="stylesheet" href="game_ranker.css">
  <script src="reqwest.min.js"></script>
  <script src="validation.js"></script>
</head>

<?php require 'imports.php'; ?>

<body>
<h1>Create a group</h1>
<div>
    <a href="join">Click here to join an existing group</a>
</div>
<br />
<form id="form" action="" method="POST">
    <div>
        <label for="session">New group name:</label> <input id="session" name="session" type="text" size="12" />
    </div>
    <div id="sessionErrors" class="errors"></div>
    <br />
    <div>
        <label for="playerCount">Player count:</label>
        <select id="playerCount" name="playerCount" class="playerCount">
            <option value="" selected="selected" hidden="hidden"></option>
            <option value="4">4</option>
            <option value="5">5</option>
            <option value="6">6</option>
            <option value="7">7</option>
            <option value="8">8</option>
            <option value="9">9</option>
            <option value="10">10</option>
        </select>
    </div>
    <div id="countErrors" class="errors"></div>
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
    document.getElementById("form").action = "join?session=" + document.getElementById("session").value;
}

function validateAndSubmit() {
    Promise.all([validateSession(), validateGames()])
    .then(function([sessionOk, gamesOk]) {
        var countOk = validateCount();
        if (sessionOk && gamesOk && countOk) {
            document.getElementById("form").action = "join?session=" + document.getElementById("session").value;
            document.getElementById("form").submit();
        }
    });
}

function validateCount() {
    if (document.getElementById("playerCount").value == "") {
        document.getElementById("countErrors").innerHTML = "<p>Please select a player count.</p>";
        return false;
    } else {
        document.getElementById("countErrors").innerHTML = "";
        return true;
    }
}

function validateSession() {
    return ajaxValidate("validate_new_session", 
        { text : document.getElementById("session").value },
        document.getElementById("sessionErrors"));
}

function validateGames() {
    return ajaxValidate("validate_games", 
        { text : document.getElementById("games").value }, 
        document.getElementById("gameErrors"));
}
</script>

<?php endDocument(); ?>