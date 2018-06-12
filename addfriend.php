<?php
	require 'connection.php';
	require 'stats.php';

    $connection = new connection();
    $conn = $connection->connect();
    
	$response = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username', 'game', 'f_username'))) {

			//getting values
			$username = $_POST['username'];
			$game = $_POST['game'];
			$f_username = $_POST['f_username'];
 
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
                $stmt_fid = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt_fid->bind_param("s", $f_username);
                $stmt_fid->execute();
                $stmt_fid->bind_result($id);
                $friend_id = -1;
                while ($stmt_fid->fetch()) {
    				$friend_id = $id;
                }
                $stmt_fid->free_result();
                $stmt_fid->close();
                
                if ($friend_id == -1) {
                    $response['status'] = 'e';
    				$response['message'] = 'Invalid friend username';
                } else if (checkFriend($user_id, $friend_id, $game, $conn)) {
                    $response['status'] = 'e';
    				$response['message'] = 'Friend already added';
                } else {
                    if (addFriend($user_id, $friend_id, $game, $conn)) {
                        $response['status'] = 's';
			            $response['message'] = 'Friend added successfully';
                    } else {
                        $response['status'] = 'e';
		                $response['message'] = 'Couldn\'t add friend. Please try again';
                    }
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
	
	function checkFriend($user_id, $friend_id, $game, $conn) {
        $stmt = $conn->prepare("SELECT id FROM friends WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)) AND game = ?");
        $stmt->bind_param("iiiis", $user_id, $friend_id, $friend_id, $user_id, $game);
        $stmt->execute();
        $stmt->bind_result($id);
        $fid = -1;
        while ($stmt->fetch()) {
    		$fid = $id;
        }
        $stmt->free_result();
        $stmt->close();
        return ($fid > -1);
    }
	
	function addFriend($user_id, $friend_id, $game, $conn) {
        $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, game) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $friend_id, $game);
        $result = $stmt->execute();
        $stmt->free_result();
        $stmt->close();
        return $result;
    }
	
	echo json_encode($response);
?>