<?php
ob_start();
if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
 
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
 
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
 
    exit(0);
    }

$postdata = file_get_contents("php://input");
if (isset($postdata)) 
{
        $request = json_decode($postdata);
        $username = $request->username;
    	$password = $request->password;
	$type = $request->type;

	if ($username == ""|| $password == "") 
	{
	echo "Empty username or password parameter!";
	}
	if ($username != "" && $password !="")
	{
		$servername = "127.0.0.1";
                $username = "root";
                $password = "K1dzteam!";
                $dbname = "FitData";
                $conn = new mysqli($servername, $username, $password, $dbname);
                if ($conn->connect_error)
                {
                    die("Connection failed: " . $conn->connect_error);
                }
		if($type == "register")
		{
			echo $type;
			$query = "SELECT * from users WHERE email = '$username'";
                        $result = $conn->query($query);
			if($result->num_rows > 0)
                        {
				echo "User already registered! Login instead";
			}
			else
			{
				echo $result->num_rows;
				$password = md5($password);
				setcookie('email', $email, time() + (86400 * 30));
                                setcookie('password', $password, time() + (86400 * 30));
				echo "register: success";
                        }

			}
		}
		if($type == "login")
                {
			echo $type;
			$password = md5($password);
			$query = "SELECT * from users WHERE email = '$username' AND pswrd = '$password'";
			$result = $conn->query($query);
			echo $result->num_rows;
		}
	}
/*
       if ($username != "" && $password !="") 
	{
		echo "Server returns: " . $username;
		// ***** Connect to the database ********
		$servername = "127.0.0.1";
		$username = "root";
		$password = "K1dzteam!";
		$dbname = "FitData";
		$conn = new mysqli($servername, $username, $password, $dbname);
		if ($conn->connect_error)
		{
		    die("Connection failed: " . $conn->connect_error);
		}

		if($type =="login")
		{	
			$password = md5($password);
			$query = "SELECT * from users WHERE email = '$username' AND pswrd = '$password'";
			$result = $conn->query($query);
                	if($result->num_rows > 0)
                	{
				setcookie('email', $email, time() + (86400 * 30));
                        	setcookie('password', $password, time() + (86400 * 30));
				
       			header("Location: https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=228NH4&redirect_uri=http%3A%2F%2Fkidsteam.boisestate.edu%2Fkidfit%2Fhandle_redirect.php&scope=activity%20heartrate%20location%20nutrition%20profile%20settings%20sleep%20social%20weight");

			}
			else
			{
				echo "User not Registered!";
			}
		}
		if($type =="register")
		{
			echo "registering";
			$query = "SELECT * from users WHERE email = '$username'";
			$result = $conn->query($query);
                        if($result->num_rows > 0)
                        {
				echo "User already registered! Login instead";
                        }
			else
			{
				$password = md5($password_1);//encrypt the password before saving in the database
                		setcookie('email', $email, time() + (86400 * 30));
                		setcookie('password', $password, time() + (86400 * 30));
				header("Location: https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=228NH4&redirect_uri=http%3A%2F%2Fkidsteam.boisestate.edu%2Fkidfit%2Fhandle_redirect.php&scope=activity%20heartrate%20location%20nutrition%20profile%20settings%20sleep%20social%20weight&prompt=login consent");
			}
		}
	}//non empty user name & password
  */     
 }// isset post data
    else {
        echo "Not called properly with username parameter!";
    }
ob_end_flush();
?> 
