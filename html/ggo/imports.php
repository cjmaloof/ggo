<?php 
require 'utils.php'; 

function dblogin() {
    $server = "localhost";
    $db = "ggo";
    $user = "admin";
    $pass = "mysql";
    
    return new mysqli($server, $user, $pass, $db);
}

function endDocument() {
    echo '</body></html>';
}

function calculateRanks($session_id) {
    $server = "localhost";
    $db = "ggo";
    $user = "admin";
    $pass = "mysql";
    
    $command = "python C:\koldb\py\ggo.py $server $db $user $pass $session_id";
    return shell_exec($command);
}

?>