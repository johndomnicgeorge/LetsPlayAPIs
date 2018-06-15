<?php
	require 'connection.php';
	require 'stats.php';

    $connection = new connection();
    $conn = $connection->connect();
    
	$response = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username', 'game', 'nickname', 'platform'))) {

			//getting values
			$username = $_POST['username'];
			$game = $_POST['game'];
			$nickname = $_POST['nickname'];
			$platform = $_POST['platform'];
 
            $stmt_uid = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt_uid->bind_param("s", $username);
            $stmt_uid->execute();
            $stmt_uid->bind_result($id);
            $user_id = -1;
            while ($stmt_uid->fetch()) {
				$user_id = $id;
            }
            $stmt_uid->free_result();
            $stmt_uid->close();

            // Check if user exists
            if ($user_id == -1) {
                $response['status'] = 'e';
				$response['message'] = 'An error occurred. Please try again';
            } else {
                $stmt_gid = $conn->prepare("SELECT id FROM games WHERE user_id = ? AND game = ? AND (nickname = ? OR nickname2 = ?) AND platform = ?");
                $stmt_gid->bind_param("sssss", $user_id, $game, $nickname, $nickname, $platform);
                $stmt_gid->execute();
                $stmt_gid->bind_result($id);
                $game_id = -1;
                while ($stmt_gid->fetch()) {
				    $game_id = $id;
                }
                $stmt_gid->free_result();
                $stmt_gid->close();

                // Check if game is already added for given username and nickname
                if ($game_id == -1) {
                    $response = getGameStats($user_id, $game, $nickname, $platform, $conn);
                } else {
                    $response['status'] = 'e';
                    $response['message'] = 'Game already exists for the given nickname';
                }
            }
		} else {
			// Required parameters are missing
			$response['status'] = 'e';
			$response['message'] = 'An error occurred. Please try again';
		}
	} else {
        // Invalid request
		$response['status'] = 'e';
		$response['message'] = 'An error occurred. Please try again';
	}

	//function to validate the required parameter in request
	function verifyRequiredParams($required_fields) {

		//Getting the request parameters
		$request_params = $_REQUEST;
		
		//Looping through all the parameters
		foreach ($required_fields as $field) {
			//if any requred parameter is missing
			if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {

				//returning true;
				return true;
			}
		}
		return false;
	}
	
	function getGameStats($user_id, $game, $nickname, $platform, $conn) {
        $stats = new Stats();
        $ret_res = array();
        $ret_res['status'] = 's';
        $ret_res['message'] = 'Game added successfully';
        $ret_res['stats'] = array();
        $ret_res['game'] = $game;
        $ret_res['platform'] = $platform;
        $ret_res['nickname'] = $nickname;
        $ret_res['nickname2'] = $nickname;
        $ret_res['in_game_name'] = $nickname;
        
        $in_game_name = $nickname;
        $add_stats = array();
        $nickname2 = $nickname;
	    
        if ($game == CSGO || $game == DOTA2) {
            $steamid = $nickname;
            if ($platform == 'custom') {
                $steamid = $stats->getSteam64ID($nickname);
                if ($steamid == null) {
                    $ret_res['status'] = 'e';
                    $ret_res['message'] = 'An error occurred. Please try again';
                } else if ($steamid == '') {
                    $ret_res['status'] = 'e';
                    $ret_res['message'] = 'Invalid Custom URL. Please try again';
                } else if (checkSteamID($user_id, $game, $steamid, $conn)) {
                    $ret_res['status'] = 'e';
                    $ret_res['message'] = 'Game already exists for the given nickname';
                } else {
                    $ret_res['nickname'] = $steamid;
                    $nickname = $steamid;
                    $in_game_name = $stats->getSteamProfileName($steamid, null);
                    $ret_res['in_game_name'] = $in_game_name;
                    if ($game == CSGO) {
                        $add_stats = $stats->getCSGOStats($steamid);
                    } else {
                        $add_stats = $stats->getCSGOStats($steamid);
                    }
                }
	        } else {
	            $in_game_name = $stats->getSteamProfileName($steamid, null);
	            $ret_res['in_game_name'] = $in_game_name;
                if ($game == CSGO) {
                    $add_stats = $stats->getCSGOStats($steamid);
                } else {
                    $add_stats = $stats->getCSGOStats($steamid);
                }
	        }
	    } else if ($game == FORTNITE) {
	        $add_stats = $stats->getFortniteStats($nickname, $platform);
	    } else if ($game == PUBG) {
	        //$add_stats = $stats->getPUBGStats($platform);
	    } else if ($game == CoC) {
	        if (substr($nickname, 0, 1) !== "#") {
	            $nickname = '#'.$nickname;
	            $ret_res['nickname'] = $nickname;
                $ret_res['nickname2'] = $nickname;
                $ret_res['in_game_name'] = $nickname;
                $in_game_name = $nickname;
                $nickname2 = $nickname;
	        }
	        $add_stats = $stats->getCoCStats($nickname);
	        if ($add_stats !== null) {
	            $in_game_name = $add_stats['name'];
	            $ret_res['in_game_name'] = $in_game_name;
	            unset($add_stats['name']);
	        }
	    } else if ($game == CR) {
	        if (substr($nickname, 0, 1) === "#") {
	            $nickname = substr($nickname, 1, strlen($nickname));
	            $ret_res['nickname'] = $nickname;
                $ret_res['nickname2'] = $nickname;
                $ret_res['in_game_name'] = $nickname;
                $in_game_name = $nickname;
                $nickname2 = $nickname;
	        }
	        $add_stats = $stats->getCRStats($nickname);
	        if ($add_stats !== null) {
	            $in_game_name = $add_stats['name'];
	            $ret_res['in_game_name'] = $in_game_name;
	            unset($add_stats['name']);
	        }
	    }
	    if ($add_stats === null) {
            $ret_res['status'] = 'e';
            $ret_res['message'] = 'An error occurred. Please try again';
	    } else if ($add_stats != null) {
            if (addGameToDB($user_id, $game, $nickname, $nickname2, $in_game_name, $platform, json_encode($add_stats), $conn)) {
                $ret_res['stats'] = $add_stats;
                $ret_res['nickname2'] = $nickname2;
            } else {
                $ret_res['status'] = 'e';
                $ret_res['message'] = getErrorMessage($game);
            }
	    }
	    return $ret_res;
	}
	
	function getErrorMessage($game) {
	    return ($game == CSGO || $game == DOTA2) ? 'Error - Your profile may be private. Please make it public and try again' : 'An error occurred. Please try again';
	}
	
	function addGameToDB($user_id, $game, $nickname, $nickname2, $in_game_name, $platform, $stats, $conn) {
        $stmt = $conn->prepare("INSERT INTO games (user_id, game, nickname, nickname2, in_game_name, platform, stats) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $user_id, $game, $nickname, $nickname2, $in_game_name, $platform, $stats);
        $result = $stmt->execute();
        $stmt->free_result();
        $stmt->close();
        return $result;
    }
    
    function checkSteamID($user_id, $game, $steamid, $conn) {
        $stmt_gid = $conn->prepare("SELECT id FROM games WHERE user_id = ? AND game = ? AND (nickname = ? OR nickname2 = ?)");
        $stmt_gid->bind_param("ssss", $user_id, $game, $steamid, $steamid);
        $stmt_gid->execute();
        $stmt_gid->bind_result($id);
        $game_id = -1;
        while ($stmt_gid->fetch()) {
		    $game_id = $id;
        }
        $stmt_gid->free_result();
        $stmt_gid->close();
        return $game_id > -1;
    }
	
	echo json_encode($response);
?>