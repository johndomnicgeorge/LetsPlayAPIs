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
            $user_uid = -1;
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
                $stmt_gid = $conn->prepare("SELECT id FROM games WHERE user_id = ? AND game = ? AND (nickname = ? OR nickname2 = ?)");
                $stmt_gid->bind_param("ssss", $user_id, $game, $nickname, $nickname);
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
        $ret_res['nickname2'] = '';
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
                } else {
                    $ret_res['nickname'] = $steamid;
                    $nickname2 = $stats->getSteamProfileName($steamid);
                    if ($game == CSGO) {
                        $add_stats = $stats->getCSGOStats($steamid);
                    } else {
                        $add_stats = $stats->getCSGOStats($steamid);
                    }
                }
	        } else {
                if ($game == CSGO) {
                    $add_stats = $stats->getCSGOStats($steamid);
                } else {
                    $add_stats = $stats->getCSGOStats($steamid);
                }
	        }
	    } else if ($game == FORTNITE) {
	        $add_stats = $stats->getFortniteStats($nickname, $platform);
	    } else if ($game == PUBG) {
	        
	    } else if ($game == CoC) {
	        
	    } else if ($game == CR) {
	        
	    } else if ($game == DOTA2) {
	        
	    }
	    if ($add_stats == null) {
            $ret_res['status'] = 'e';
            $ret_res['message'] = 'An error occurred. Please try again';
	    } else {
            if (addGameToDB($user_id, $game, $nickname, $nickname2, $platform, json_encode($add_stats, JSON_FORCE_OBJECT), $conn)) {
                $ret_res['stats'] = $add_stats;
                $ret_res['nickname2'] = $nickname2;
            } else {
                $ret_res['status'] = 'e';
                $ret_res['message'] = 'An error occurred. Please try again';
            }
	    }
	    return $ret_res;
	}
	
	function addGameToDB($user_id, $game, $nickname, $nickname2, $platform, $stats, $conn) {
        $stmt = $conn->prepare("INSERT INTO games (user_id, game, nickname, nickname2, platform, stats) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $user_id, $game, $nickname, $nickname2, $platform, $stats);
        $result = $stmt->execute();
        $stmt->free_result();
        $stmt->close();
        return $result;
    }
	
	echo json_encode($response, JSON_FORCE_OBJECT);
?>