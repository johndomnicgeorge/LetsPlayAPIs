<?php
	require 'connection.php';

    $connection = new connection();
    $conn = $connection->connect();
    
	$response = array();
	$response['games'] = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username'))) {

			//getting values
			$username = $_POST['username'];
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
                $stmt_gid = $conn->prepare("SELECT game, nickname, nickname2, platform FROM games WHERE user_id = ?");
                $stmt_gid->bind_param("s", $user_id);
                $stmt_gid->execute();
                $stmt_gid->bind_result($game, $nickname, $nickname2, $platform);
                
                while ($stmt_gid->fetch()) {
				    array_push($response['games'], array('game'=>$game, 'nickname'=>$nickname, 'nickname2'=>$nickname2, 'platform'=>$platform));
                }
                
                $stmt_gid->free_result();
                $stmt_gid->close();
                
                // Check if there are games
                if (count($response['games']) == 0) {
                    $response['status'] = 'e';
				    $response['message'] = 'Games list empty. Please add some games';
                } else {
                    $response['status'] = 's';
                    $response['message'] = '';
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
	
	echo json_encode($response);
?>