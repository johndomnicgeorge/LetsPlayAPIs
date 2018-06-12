<?php
	require 'connection.php';

    $connection = new connection();
    $conn = $connection->connect();
    
	$response = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username', 'search', 'game'))) {

			//getting values
			$search = $_POST['search'];
			$game = $_POST['game'];
			$username = $_POST['username'];
			
		    $likeparam = $search."%";
		    $user_id = getUserID($username, $conn);

			$stmt_uid = $conn->prepare("SELECT users.id, users.name, users.username, games.game FROM games JOIN users ON games.user_id = users.id WHERE games.game LIKE ? AND games.user_id IN (SELECT users.id FROM users WHERE users.username LIKE ? OR users.name LIKE ?)");
            $stmt_uid->bind_param("sss", $game, $likeparam, $likeparam);
            $stmt_uid->execute();
            $stmt_uid->bind_result($uid, $name, $uname, $g);
            $result = array();
            while ($stmt_uid->fetch()) {
                if ($username != $uname) {
                    $row = array();
                    $row['id'] = $uid;
                    $row['name'] = $name;
                    $row['username'] = $uname;
                    $row['game'] = $g;
                    array_push($result, $row);
                }
            }
            if (count($result) == 0) {
                $response['status'] = 'e';
				$response['message'] = 'No results found';
            } else {
                $response['status'] = 's';
				$response['message'] = '';
				for ($i = 0; $i < count($result); $i++) {
				    $row = $result[$i];
                    $row['is_friend'] = checkFriend($user_id, $row['id'], $row['game'], $conn);
                    unset($row['id']);
                    $result[$i] = $row;
				}
				$response['results'] = $result;
            }
            $stmt_uid->free_result();
            $stmt_uid->close();
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
    
    function getUserID($username, $conn) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id);
        $uid = -1;
        while ($stmt->fetch()) {
    		$uid = $id;
        }
        $stmt->free_result();
        $stmt->close();
        return $uid;
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
	
	echo json_encode($response);
?>