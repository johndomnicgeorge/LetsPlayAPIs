<?php
	require 'connection.php';

    $connection = new connection();
    $conn = $connection->connect();
    
	$response = array();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username'))) {

			//getting values
			$username = $_POST['username'];
 
            $stmt = $conn->prepare("SELECT email, password, name FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($email, $password, $name);
            $status = 's';
            $message = 'Password has been sent to the registered email. Please check your spam folder if you haven\'t receviced it';
            while ($stmt->fetch()) {
                $to = $email;
                $subject = 'Let\'s Play - Forgot Password';
                
                $body = file_get_contents("forgot_password_template.html");
                $body = str_replace("{{NAME}}", $name, $body);
                $body = str_replace("{{PASSWORD}}", $password, $body);
                
                $headers = "From: no-reply@letsplay.com"."\r\n";
                $headers .= "Reply-To: no-reply@letsplay.com"."\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
 
                if(!mail($to, $subject, $body, $headers)) {
                    $status = 'e';
                    $message = 'An error occurred. Please try again';
                }
            }
            $stmt->free_result();
            $stmt->close();
            $response['status'] = $status;
			$response['message'] = $message;
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