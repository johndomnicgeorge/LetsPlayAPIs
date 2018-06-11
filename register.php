<?php
	require_once 'connection.php';

    $connection = new connection();
    $conn = $connection->connect();
    
    define('USER_CREATED', 0);
    define('USER_ALREADY_EXISTS', 1);
    define('USER_NOT_CREATED', 2);
    
	$response = array();
 
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!verifyRequiredParams(array('username', 'password', 'name', 'gender', 'email'))) {

			//getting values
			$username = $_POST['username'];
			$password = $_POST['password'];
			$email = $_POST['email'];
			$name = $_POST['name'];
			$gender = $_POST['gender'];
 
            //echo $username.' '.$password.' '.$email.' '.$name.' '.$gender; 
 
			//adding user to database
			$result = createUser($username, $password, $name, $gender, $email, $conn);
 
			//making the response accordingly
			if ($result == USER_CREATED) {
				$response['status'] = 's';
				$response['message'] = 'User created successfully';
			} elseif ($result == USER_ALREADY_EXISTS) {
				$response['status'] = 'e';
				$response['message'] = 'User already exists';
			} elseif ($result == USER_NOT_CREATED) {
				$response['status'] = 'e';
				$response['message'] = 'An error occurred. Please try again';
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
	
	function createUser($username, $password, $name, $gender, $email, $conn) {
        if (isUserExist($username, $email, $conn)) {
            return USER_ALREADY_EXISTS;
        } else {
            //$password = md5($pass);
            $stmt = $conn->prepare("INSERT INTO users (username, password, name, gender, email) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $password, $name, $gender, $email);
            if ($stmt->execute()) {
                close($stmt);
                return USER_CREATED;
            } else {
                close($stmt);
                //echo $stmt->error.' @@@ '.mysqli_error();
                return USER_NOT_CREATED;
            }
        }
    }
	
	function isUserExist($username, $email, $conn) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $ret = false;
        if ($stmt->execute()) {
            $rows = 0;
            $stmt->bind_result($id);
            while($stmt->fetch()) {
                $rows++;   
            }
            $ret = ($rows > 0);
        }
        close($stmt);
        return $ret;
    }
    
    function close($stmt) {
        $stmt->free_result();
        $stmt->close();
    }
    $conn->close();
	echo json_encode($response);
 
?>