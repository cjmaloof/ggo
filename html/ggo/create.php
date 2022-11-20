<?php require 'imports.php'; ?>
<!DOCTYPE html>
<html>
<head>
  <title>What Do We Play? - Setup</title>
  <?php require 'common_head.php'; ?>
  <script src="js/reqwest.min.js"></script>
  <script src="js/validation.js"></script>
</head>

<?php
require 'header.php';
$minPlayers = 3;
$maxPlayers = 15;
?>

<body>
<h1>Create a group</h1>
<div>
    <a href="join">Click here to join an existing group instead</a>
</div>
<br />
<form id="form" action="" method="POST">
    <div>
        <label for="session">Name your group:</label> <input id="session" name="session" type="text" size="12" />
    </div>
    <div id="sessionErrors" class="errors"></div>
    <br />
    <div>
        <label for="playerCount">How many players?</label>
        <select id="playerCount" name="playerCount" class="playerCount">
            <option value="" selected="selected" hidden="hidden"></option>
<?php
            for ($i = $minPlayers; $i <= $maxPlayers; $i++) {
                echo str_repeat(" ", 12) . "<option value=\"$i\">$i</option>\n";
            }
?>
        </select>
    </div>
    <div id="countErrors" class="errors"></div>
    <br />
    <div>
        <label for="tableCount">How many tables?</label>
        <input type="radio" name="tableCount" id="tc1" value="1"/><label for="tc1">1</label>
        <input type="radio" name="tableCount" id="tc2" value="2" checked="checked"/><label for="tc2">2</label>
        <input type="radio" name="tableCount" id="tc3" value="3"/><label for="tc3">3</label>
    </div>
    <br />
    <div>
        <label for="groupMinutes">Remove the group after:</label>
        <select id="groupMinutes" name="groupMinutes" class="groupMinutes">
            <option value="30">30 minutes</option>
            <option value="1440" selected="selected">1 day</option>
            <option value="7200">5 days</option>
            <option value="14400">10 days</option>
        </select>
        <span class="hoverhelp">
          <img src="images/help.png" valign="bottom" />
          <span class="onhoverhelp">Choose a longer time for advance planning, or a shorter time to allow the group name to be reused.</span>
        </span>
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