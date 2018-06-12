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
                $stmt_gid = $conn->prepare("SELECT id, in_game_name, stats FROM games WHERE user_id = ? AND game = ? AND nickname = ? AND platform = ?");
                $stmt_gid->bind_param("ssss", $user_id, $game, $nickname, $platform);
                $stmt_gid->execute();
                $stmt_gid->bind_result($id, $ign, $st);
                $gid = -1;
                $in_game_name = '';
                $stats = '';
                while ($stmt_gid->fetch()) {
                    $gid = $id;
				    $in_game_name = $ign;
                    $stats = json_decode($st);
                }
                $stmt_gid->free_result();
                $stmt_gid->close();

                // Check if game is already added for given username and nickname
                if ($gid == -1) {
                    $response['status'] = 'e';
                    $response['message'] = 'Game doesn\'t exists for the given nickname or user has removed the game';
                } else {
                    $response = getGameStats($id, $user_id, $game, $nickname, $platform, $in_game_name, $stats, $conn);
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
	
	function getGameStats($gid, $user_id, $game, $nickname, $platform, $ign, $old_stats, $conn) {
        $stats = new Stats();
        $ret_res = array();
        $ret_res['status'] = 's';
        $ret_res['message'] = '';
        $ret_res['game'] = $game;
        $ret_res['platform'] = $platform;
        $ret_res['nickname'] = $nickname;
        $ret_res['nickname2'] = $nickname;
        $ret_res['in_game_name'] = $ign;
        $ret_res['stats'] = $old_stats;
        
        $in_game_name = $ign;
        $add_stats = array();
	    
        if ($game == CSGO || $game == DOTA2) {
            $in_game_name = $stats->getSteamProfileName($nickname, $ign);
            $ret_res['in_game_name'] = $in_game_name;
            if ($game == CSGO) {
                $add_stats = $stats->getCSGOStats($nickname);
            } else {
                $add_stats = $stats->getCSGOStats($nickname);
            }
	    } else if ($game == FORTNITE) {
	        $add_stats = $stats->getFortniteStats($nickname, $platform);
	    } else if ($game == PUBG) {
	        
	    } else if ($game == CoC) {
	        $add_stats = $stats->getCoCStats($nickname);
	        if ($add_stats !== null) {
	            $in_game_name = $add_stats['name'];
	            $ret_res['in_game_name'] = $in_game_name;
	            unset($add_stats['name']);
	        }
	    } else if ($game == CR) {
	        $add_stats = $stats->getCRStats($nickname);
	        if ($add_stats !== null) {
	            $in_game_name = $add_stats['name'];
	            $ret_res['in_game_name'] = $in_game_name;
	            unset($add_stats['name']);
	        }
	    }
	    if ($add_stats !== null) {
            if (updateGameStats($gid, $in_game_name, json_encode($add_stats), $conn)) {
                $ret_res['stats'] = $add_stats;
            } else {
                $ret_res['status'] = 'e';
                $ret_res['message'] = 'An error occurred. Please try again';
            }
	    }
	    return $ret_res;
	}
	
	function updateGameStats($gid, $in_game_name, $stats, $conn) {
        $stmt = $conn->prepare("UPDATE games SET in_game_name = ?, stats = ? WHERE id = ?");
        $stmt->bind_param("sss", $in_game_name, $stats, $gid);
        $result = $stmt->execute();
        $stmt->free_result();
        $stmt->close();
        return $result;
    }
	
	echo json_encode($response);
?>