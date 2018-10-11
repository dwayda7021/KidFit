
<?php
session_start();
ob_start();
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

$email = $_GET['username'];
$password = $_GET['password'];
$loginType = $_GET['loginType'];
if($loginType == "register")
{
        if( empty($email) || empty($password)){
                if (empty($email) && empty($password)) {
                        $json_arr = array('error'=> "Email & Password is required" );
                        $json_data = json_encode($json_arr);
                }

                if (empty($email)) {
                        $json_arr = array('error'=> "Email is required" );
                        $json_data = json_encode($json_arr);

                }
                if (empty($password)) {
                        $json_arr = array('error'=> "Password is required");
                        $json_data = json_encode($json_arr);
                }
        }
        else
        {
		if (!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$json_arr = array('error'=> "Invalid Email Format");
                        $json_data = json_encode($json_arr);
		}
		else
		{
                	$query = "SELECT * from users WHERE email = '$email'";
                	$result = $conn->query($query);
                	if($result->num_rows > 0)
                	{
                	        $json_arr = array('error'=> "Email already Registered! Please Login");
                	}
               		else
                	{      
				$password = md5($password);
				$salt =bin2hex(openssl_random_pseudo_bytes(2));
				$token = $password.$salt;
				$token = crypt_data($token,'e');
				$currTime = date("Y-m-d H:i:s", strtotime("now"));
                                $appTokenExpDate = date("Y-m-d H:i:s", strtotime("+ 43200 minutes"));
                                $sql = "INSERT INTO users (fitbitID,email, pswrd, userName, createDate, accessToken, refreshToken, tokenExpDate,appAccessToken, appSalt, appTokenExpDate, dataAccess) VALUES ('null','$email','$password','null','$currTime','null', 'null','$currTime','$token','$salt','$appTokenExpDate',false)";
				if ($conn->query($sql) == TRUE)
        			{
					$json_arr = array('register'=> "success",'username' => $email,'access_token' => $token);
                		}
				else
				{
					$json_arr = array('error'=> "Insertion Failed");

				}
			}	
                	$json_data = json_encode($json_arr);
        	}
	}
}

if($loginType == "login")
{
        if( empty($email) || empty($password)){
                if (empty($email) && empty($password)) {
                        $json_arr = array('error'=> "Email & Password is required" );
                        $json_data = json_encode($json_arr);
                }

                if (empty($email)) {
                        $json_arr = array('error'=> "Email is required" );
                        $json_data = json_encode($json_arr);

                }
                if (empty($password)) {
                        $json_arr = array('error'=> "Password is required");
                        $json_data = json_encode($json_arr);

                }
        }
	
        else
        {
               if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                {
                        $json_arr = array('error'=> "Invalid Email Format");
                        $json_data = json_encode($json_arr);
                }
		else
		{
			$password = md5($password);
                	$query = "SELECT * from users WHERE email = '$email'";
                	$result = $conn->query($query);
                	if($result->num_rows > 0)
                	{
				$row = $result->fetch_assoc();
				$salt = $row['appSalt'];
                                $act = $row['appAccessToken'];
                                $expTime = $row['appTokenExpDate'];
                                $currTime = date("Y-m-d H:i:s", strtotime("now"));
                                $token = $row['appAccessToken'];
				if(!$row['dataAccess'])
				{
					$json_arr = array('error'=> "Fitbit Data Access Denied",'access' => "0", 'access_token' => $token, 'username'=>$email );
		                        $json_data = json_encode($json_arr);
					echo $json_data;
					return;


				} 
				if($row['pswrd']==$password && crypt_data($password.$salt,'e')==$act)
				{
					if($currTime > $expTime)
					{
						$salt =bin2hex(openssl_random_pseudo_bytes(2));
						$token = $password.$salt;
                                		$token = crypt_data($token,'e');
						$appTokenExpDate = date("Y/m/d H:i:s", strtotime("+ 43200 minutes"));
						$sql = "UPDATE users SET appAccessToken ='$token', appSalt= '$salt', appTokenExpDate = '$appTokenExpDate' where email ='$email'";					
						if ($conn->query($sql) === TRUE)
                                	 	{
                        				$json_arr = array('login'=> "success",'username' => $email, 'access_token' => $token);
                				}
					}
					 $json_arr = array('login'=> "success",'username' => $email, 'access_token' => $token);
		
				}
				else
				{
					$json_arr = array('error'=> "Password does not match! Please Login Again");
				}
			}
                	else
                	{
                        	$json_arr = array('error'=> "User not Registered! Please Register");
                	}
		}
        }
        $json_data = json_encode($json_arr);
}

echo $json_data;

function crypt_data($string, $action ='e')
{
	$secret_key = 'my_simple_secret_key';
	$secret_iv = 'my_simple_secret_iv';
	$encrypt_method = "AES-256-CBC";
	$key = hash( 'sha256', $secret_key );
	$iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );
	if( $action == 'e' ) 
	{
        	$output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
    	}
	else if( $action == 'd' )
	{
        	$output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
    	}
	else
	{
		$output=false;
	}
	return $output;

}





ob_end_flush();
?>

