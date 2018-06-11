<?php
	require 'connection.php';

    $connection = new connection();
    $conn = $connection->connect();
    
	$response = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username', 'password'))) {

			//getting values
			$username = $_POST['username'];
			$password = $_POST['password'];
 
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND password = ?");
            //$pass = md5($password);
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $stmt->bind_result($id);
            $uid = -1;
            while ($stmt->fetch()) {
                // todo get data based on id
                $uid = $id;
                $response['status'] = 's';
				$response['message'] = strval($uid);
            }
            if ($uid == -1) {
                $response['status'] = 'e';
				$response['message'] = 'Invalid username or password';
            }
            $stmt->free_result();
            $stmt->close();
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