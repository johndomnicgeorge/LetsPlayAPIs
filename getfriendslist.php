<?php
	require 'connection.php';

    $connection = new connection();
    $conn = $connection->connect();
    
	$response = array();
	$response['friends'] = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username'))) {

			//getting values
			$username = $_POST['username'];
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
            $friends = array();
            
            // Check if user exists
            if ($user_id == -1) {
                $response['status'] = 'e';
				$response['message'] = 'An error occurred. Please try again';
            } else {
                $stmt_f1 = $conn->prepare("SELECT users.id, users.name, users.username, friends.game FROM users JOIN friends ON friends.user_id = users.id WHERE users.id IN (SELECT friends.user_id FROM friends WHERE friends.friend_id = ?)");
                $stmt_f1->bind_param("s", $user_id);
                $stmt_f1->execute();
                $stmt_f1->bind_result($id, $name, $uname, $game);
                
                while ($stmt_f1->fetch()) {
				    array_push($friends, array('id' => $id, 'game'=>$game, 'name'=>$name, 'username'=>$uname));
                }
                
                $stmt_f1->free_result();
                $stmt_f1->close();
                
                
                $stmt_f2 = $conn->prepare("SELECT users.id, users.name, users.username, friends.game FROM users JOIN friends ON friends.friend_id = users.id WHERE users.id IN (SELECT friends.friend_id FROM friends WHERE friends.user_id = ?)");
                $stmt_f2->bind_param("s", $user_id);
                $stmt_f2->execute();
                $stmt_f2->bind_result($id, $name, $uname, $game);
                
                while ($stmt_f2->fetch()) {
				    array_push($friends, array('id' => $id, 'game'=>$game, 'name'=>$name, 'username'=>$uname));
                }
                
                $stmt_f2->free_result();
                $stmt_f2->close();
                
                
                // Check if there are friends
                if (count($friends) == 0) {
                    $response['status'] = 'e';
				    $response['message'] = 'You have no friends :(';
                } else {
                    $response['status'] = 's';
                    $response['message'] = '';
                    for ($i = 0; $i < count($friends); $i++) {
                        $row = $friends[$i];
                        $game = $row['game'];
                        $id = $row['id'];
                        $row['platform'] = '';
            			$row['nickname'] = '';
            			$stmt_g = $conn->prepare("SELECT platform, nickname FROM games WHERE user_id = ? AND game = ?");
                        $stmt_g->bind_param("is", $id, $game);
                        $stmt_g->execute();
                        $stmt_g->bind_result($plat, $nick);
                        while ($stmt_g->fetch()) {
            				$row['platform'] = $plat;
            				$row['nickname'] = $nick;
                        }
                        unset($row['id']);
                        $friends[$i] = $row;
                        $stmt_g->free_result();
                        $stmt_g->close();
                    }
                    $response['friends'] = $friends;
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