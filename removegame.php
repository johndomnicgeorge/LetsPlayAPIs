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
                    $response['status'] = 'e';
                    $response['message'] = 'Game doesn\'t exist or already deleted for the given nickname';
                } else {
                    if (deleteGame($game_id, $conn)) {
                        $response['status'] = 's';
			            $response['message'] = 'Game deleted successfully';
                    } else {
                        $response['status'] = 'e';
			            $response['message'] = 'Couldn\'t delete game. Please try again';
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
	
	function deleteGame($id, $conn) {
        $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->free_result();
        $stmt->close();
        return $result;
    }
	
	echo json_encode($response);
?>