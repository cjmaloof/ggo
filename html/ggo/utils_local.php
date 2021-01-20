<?
    $MAX_RANK = 100;

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
    
    function sanitizeArray($mysqli, $array) {
        $result = array();
        foreach ($array as $element) {
            $result[] = $mysqli->real_escape_string($element);
        }
        return $result;
    }
    
    function insertSession($mysqli, $session_label, $simul, $players) {
        $insert_session = $mysqli->prepare("INSERT INTO session(label, simul, players, created) VALUES (?, ?, ?, NOW())");
        $insert_session->bind_param("sii", $session_label, $simul, $players);
        $insert_session->execute();
        $insert_session->close();
    }
    
    function insertPlayers($mysqli, $players) {
        $i = 0;
        $player = "";
        $insert_player = $mysqli->prepare("INSERT INTO player(session_id, name, ordinal) VALUES (LAST_INSERT_ID(), ?, ?)");
        $insert_player->bind_param("si", $player, $i);
        for ($i = 0; $i < sizeof($players); $i++) {
            $player = substr($players[$i], 0, 250);
            $insert_player->execute();
        }
        $insert_player->close();
    }
    
    // Inserts a player and returns the ordinal of that player
    function insertPlayer($mysqli, $session_id, $player) {
        $insert_player = $mysqli->prepare("INSERT INTO player(session_id, name, ordinal) SELECT ?, ?, ifnull(max(ordinal)+1, 0) FROM player WHERE session_id=?");
        $player_short = substr($player, 0, 250);
        $insert_player->bind_param("sss", $session_id, $player_short, $session_id);
        $insert_player->execute();
        $insert_player->close();
        
        $query_ordinal = $mysqli->prepare("SELECT max(ordinal) FROM player WHERE session_id=? AND name=?");
        $query_ordinal->bind_param("ss", $session_id, $player_short);
        $query_ordinal->execute();
        $query_ordinal->bind_result($ordinal);
        $query_ordinal->fetch();
        $query_ordinal->close();
        return $ordinal;
    }
    
    function insertGames($mysqli, $games) {
        $game = "";
        $insert_game = $mysqli->prepare("INSERT INTO game(session_id, name, ordinal) VALUES (LAST_INSERT_ID(), ?, ?)");
        $insert_game->bind_param("si", $game, $i);
        for ($i = 0; $i < sizeof($games); $i++) {
            $game = substr($games[$i], 0, 250);
            $insert_game->execute();
        }
        $insert_game->close();
    }
    
    // Returns the expected player count for the session
    function fetchPlayerCount($mysqli, $session_label) {
        $session_id = fetchSessionId($mysqli, $session_label);
        
        $query_session = $mysqli->prepare("SELECT players FROM session WHERE id=?");
        $query_session->bind_param("i", $session_id);
        $query_session->execute();
        $query_session->bind_result($player_count);
        $query_session->fetch();
        $query_session->close();
        return $player_count;
    }
    
    // Returns an array of the selected player name and (if any) the next player
    function fetchCurrentAndNextPlayer($mysqli, $session_label, $ordinal) {
        $session_id = fetchSessionId($mysqli, $session_label);
        
        $result = array();
        $query_players = $mysqli->prepare("SELECT name FROM player WHERE session_id = ? AND ordinal IN (?, ?+1) ORDER BY ordinal");
        $query_players->bind_param("iii", $session_id, $ordinal, $ordinal);
        $query_players->execute();
        $query_players->bind_result($player_name);
        $query_players->fetch();
        $result[] = $player_name;
        if ($query_players->fetch()) {
            $result[] = $player_name;
        }
        $query_players->close();
        return $result;
    }
    
    // Returns an array of game names ordered by ordinal
    function fetchGames($mysqli, $session_label) {
        $session_id = fetchSessionId($mysqli, $session_label);
        
        $query_games = $mysqli->prepare("SELECT name FROM game WHERE session_id = ? ORDER BY ordinal");
        $query_games->bind_param("i", $session_id);
        $query_games->execute();
        $query_games->bind_result($game_name);
        $games = array();
        while ($query_games->fetch()) {
            $games[] = $game_name;
        }
        $query_games->close();
        return $games;
    }
    
    function fetchSessionId($mysqli, $session_label) {
        $query_session = $mysqli->prepare("SELECT id FROM session WHERE label=? ORDER BY created DESC LIMIT 1");
        $query_session->bind_param("s", $session_label);
        $query_session->execute();
        $query_session->bind_result($session_id);
        $query_session->fetch();
        $query_session->close();
        return $session_id;
    }
    
    // Returns an array of arrays of (player, [top_choices], [next_choices], ...)
    // Skips trash choices
    function fetchRanksByPlayer($mysqli, $session_id) {
        $query = $mysqli->prepare("SELECT p.name, g.name, r.rank " .
                                  "FROM player p " .
                                  "INNER JOIN game g ON p.session_id=g.session_id " .
                                  "INNER JOIN rank r ON p.session_id=r.session_id AND r.player=p.ordinal AND r.game=g.ordinal " .
                                  "WHERE p.session_id = ? " .
                                  "AND r.rank <> ? " .
                                  "ORDER BY p.ordinal, r.rank, g.ordinal");
        $query->bind_param("ii", $session_id, $GLOBALS['MAX_RANK']);
        $query->execute();
        $query->bind_result($player, $game, $rank);
        
        $result = array();
        $current_player_result = array();
        $current_rank_result = array();
        $last_player = "";
        $last_rank = -1;
        while ($query->fetch()) {
            if ($rank != $last_rank || $player != $last_player) {
                // Write the previous rank if any
                if (count($current_rank_result)) {
                    $current_player_result[] = $current_rank_result;
                    // Reset state to new rank
                    $current_rank_result = array();
                }
                $last_rank = $rank;
            }
            $current_rank_result[] = $game;
            
            if ($player != $last_player) {
                // Write the previous player if any
                if (count($current_player_result)) {
                    $result[] = $current_player_result;
                    // Reset state to new player
                    $current_player_result = array();
                }
                $current_player_result[] = $player;
                $last_player = $player;
            }
        }
        $query->close();
        
        // Write final rank and player
        $current_player_result[] = $current_rank_result;
        $result[] = $current_player_result;
        return $result;
    }
    
    // Inserts ranks into DB
    // Rows with multiple games are considered to be tied at the most desirable level
    // The last row is considered to be rank $MAX_RANK
    // For instance, in [[1][2,3],[]] game 1 is ranked 1 while games 2 and 3 are both at rank 2
    function insertRanks($mysqli, $session_label, $player, $rank_string) {
        $session_id = fetchSessionId($mysqli, $session_label);
        
        $game_rows = parseRanks($rank_string);
        $trash_game_row = array_pop($game_rows);
        
        $insert_rank = $mysqli->prepare("INSERT INTO rank(session_id, player, game, rank) VALUES (?, ?, ?, ?)");
        $insert_rank->bind_param("iiii", $session_id, $player, $game, $rank);
        $rank = 0;
        foreach ($game_rows as $game_row) {
            foreach ($game_row as $game) {
                $insert_rank->execute();
            }
            $rank += count($game_row);
        }
        $rank = $GLOBALS['MAX_RANK'];
        foreach ($trash_game_row as $game) {
            $insert_rank->execute();
        }
        $insert_rank->close();
    }
    
    // $rank_string is like "3,0;1,2;;4;"
    // Returns an array of arrays of game ordinals like [[3,0],[1,2],[4],[]]
    // Empty rows (e.g., consecutive semicolons) are ignored EXCEPT the last row is always included
    function parseRanks($rank_string) {
        $result = array();
        $rows = explode(";", $rank_string);
        $trash_row = array_pop($rows);
        foreach ($rows as $row) {
            if ($row !== "") {
                $result[] = explode(",", $row);
            }
        }
        $result[] = explode(",", $trash_row);
        return $result;
    }
?>
