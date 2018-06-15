<?php
	require 'connection.php';
	require 'stats.php';

    $connection = new connection();
    $conn = $connection->connect();
    
	$response = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username', 'game'))) {

			//getting values
			$username = $_POST['username'];
			$game = $_POST['game'];
 
            $stmt_uid = $conn->prepare("SELECT id, name, gender FROM users WHERE username = ?");
            $stmt_uid->bind_param("s", $username);
            $stmt_uid->execute();
            $stmt_uid->bind_result($id, $n, $gen);
            $user_id = -1;
            $gender = '';
            $u_name = '';
            while ($stmt_uid->fetch()) {
				$user_id = $id;
				$u_name = $n;
				$gender = $gen;
            }
            $stmt_uid->free_result();
            $stmt_uid->close();

            // Check if user exists
            if ($user_id == -1) {
                $response['status'] = 'e';
				$response['message'] = 'An error occurred. Please try again';
            } else {
                $results = array();
                $stmt_f1 = $conn->prepare("SELECT users.name, users.email FROM users WHERE users.id IN (SELECT friends.user_id FROM friends WHERE friends.friend_id = ? AND game = ?)");
                $stmt_f1->bind_param("is", $user_id, $game);
                $stmt_f1->execute();
                $stmt_f1->bind_result($f_name, $email);
                
                while ($stmt_f1->fetch()) {
                    array_push($results, array('u_name' => $u_name, 'f_name' => $f_name, 'email' => $email, 'gender' => $gender, 'game' => $game));
				    //sendMail($u_name, $f_name, $email, $gender, $game);
                }
                
                $stmt_f1->free_result();
                $stmt_f1->close();
                
                $stmt_f2 = $conn->prepare("SELECT users.name, users.email FROM users WHERE users.id IN (SELECT friends.friend_id FROM friends WHERE friends.user_id = ? AND game = ?)");
                $stmt_f2->bind_param("is", $user_id, $game);
                $stmt_f2->execute();
                $stmt_f2->bind_result($f_name, $email);
                
                while ($stmt_f2->fetch()) {
                    array_push($results, array('u_name' => $u_name, 'f_name' => $f_name, 'email' => $email, 'gender' => $gender, 'game' => $game));
				    //sendMail($u_name, $f_name, $email, $gender, $game);
                }
                
                $stmt_f2->free_result();
                $stmt_f2->close();
                
                $results = unique_multidim_array($results, 'email');
                foreach($results as $row) {
                    sendMail($row['u_name'], $row['f_name'], $row['email'], $row['gender'], $row['game']);
                }
                
                $response['status'] = 's';
                $response['message'] = 'Invitation sent';
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
	
	function unique_multidim_array($array, $key) {
        $temp_array = array();
        $i = 0;
        $key_array = array();
   
        foreach($array as $val) {
            if (!in_array($val[$key], $key_array)) {
                $key_array[$i] = $val[$key];
                $temp_array[$i] = $val;
            }
            $i++;
        }
    return $temp_array;
} 
	
	function getGameName($game) {
	    if ($game == 'csgo') {
	        return 'CS:GO';
	    } else if ($game == 'fort') {
	        return 'Fortnite';
	    } else if ($game == 'pubg') {
	        return 'PUBG';
	    } else if ($game == 'coc') {
	        return 'Clash of Clans';
	    } else if ($game == 'cr') {
	        return 'Clash Royale';
	    } else if ($game == 'dota2') {
	        return 'Dota 2';
	    } else if ($game == 'lol') {
	        return 'League of Legends';
	    }
	}
	
	function sendMail($u_name, $f_name, $email, $gender, $game) {
	    $to = $email;
        $subject = 'Let\'s Play - Game Notification';
                
        $body = file_get_contents("ios_notification_template.html");
        $body = str_replace("{{NAME}}", $u_name, $body);
        $body = str_replace("{{FRIEND}}", $f_name, $body);
        $body = str_replace("{{GENDER}}", ($gender == 'Male' ? 'him' : 'her'), $body);
        $body = str_replace("{{GAME}}", getGameName($game), $body);
        
        $headers = "From: no-reply@letsplay.com"."\r\n";
        $headers .= "Reply-To: no-reply@letsplay.com"."\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to, $subject, $body, $headers); 
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