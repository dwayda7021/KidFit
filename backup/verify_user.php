<?php
session_start();
$email    = "";
$errors = array(); 
$servername = "127.0.0.1";
$username = "root";
$password = "K1dzteam!";
$dbname = "FitData";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) 
{
    die("Connection failed: " . $conn->connect_error);
}







if (isset($_POST['reg_user'])) 
{
  	$email = $_POST['email'];//mysqli_real_escape_string($db, $_POST['email']);
  	$password_1 = $_POST['password_1'];//mysqli_real_escape_string($db, $_POST['password_1']);
  	$password_2 = $_POST['password_2'];//mysqli_real_escape_string($db, $_POST['password_2']);
  
	if (empty($email)) 
	{ array_push($errors, "Email is required"); }
  	
	if (empty($password_1)) 
	{ array_push($errors, "Password is required"); }
  	
	if ($password_1 != $password_2) 
	{ array_push($errors, "The two passwords do not match");}
  

	// first check the database to make sure 
  	// a user does not already exist with the same username and/or email
  	$user_check_query = "SELECT * FROM users WHERE email='$email' LIMIT 1";
  	$result = $conn->query($user_check_query);
  	$user = $result->fetch_assoc();
 
  	if ($user) 
  	{ // if user exists
  	 	if ($user['email'] === $email) 
       		{
          		array_push($errors, "email already exists");
       		}
   	}
	$data_arr = $email."***".$password;


   	if (count($errors) == 0) 
   	{
  		$password = md5($password_1);//encrypt the password before saving in the database
		setcookie('email', $email, time() + (86400 * 30));
		setcookie('password', $password, time() + (86400 * 30));

		header("Location: https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=228NH4&redirect_uri=http%3A%2F%2Fkidsteam.boisestate.edu%2Fkidfit%2Fhandle_redirect.php&scope=activity%20heartrate%20location%20nutrition%20profile%20settings%20sleep%20social%20weight&prompt=login consent&state=$data_arr");



    	}
}


if (isset($_POST['login_user'])) 
{
  	$email =  $_POST['email'];
  	$password = $_POST['password'];

	$data_arr = $email."***".$password;
  	if (empty($email)) {
  		array_push($errors, "Username is required");
    	}
  	if (empty($password)) {
  		array_push($errors, "Password is required");
    	}

  	if (count($errors) == 0) 
  	{
  		$password = md5($password);
		$query = "SELECT * from users WHERE email = '$email'";
		$result = $conn->query($query);
		if($result->num_rows > 0)
        	{
			setcookie('email', $email, time() + (86400 * 30));
                        setcookie('password', $password, time() + (86400 * 30));
                        header("Location: https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=228NH4&redirect_uri=http%3A%2F%2Fkidsteam.boisestate.edu%2Fkidfit%2Fhandle_redirect.php&scope=activity%20heartrate%20location%20nutrition%20profile%20settings%20sleep%20social%20weight&state=$data_arr");
		}
		else
		{
			array_push($errors,"User Not Registered");
		}
	}
}

if (isset($_POST['google']))
{




}
?>
