<!DOCTYPE html>
<html>
<head>
  <title>Game Group Organizer - Setup</title>
  <link rel="stylesheet" href="game_ranker.css">
</head>

<?php require 'utils_local.php'; ?>

<body>
<h1>Game Group Organizer</h1>
<form action="game_ranker.php" method="POST">
    <div>
        <label for="players">Players (enter one per line):</label> <br />
        <textarea id="players" name="players" rows="10" cols="40"></textarea>
    </div>
    <br />
    <div>
        <label for="games">Games (enter one per line):</label> <br />
        <textarea id="games" name="games" rows="10" cols="40"></textarea>
    </div>
    <input type="hidden" id="ordinal" name="ordinal" value="0" />
    <input type="submit">
</form>

<script>
// Add pre-submit validation of counts and line lengths (255 max)
</script>

<?php endDocument(); ?>