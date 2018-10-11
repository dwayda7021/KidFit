<html>
 <head>
  <title>Registration page</title>
 </head>
 <body>
 <?php
 $return_code = $_GET['code'];
$headers = apache_request_headers();




// ****** CONNECT TO THE DATABASE ***********

$servername = "127.0.0.1";
$username = "root";
$password = "K1dzteam!";
$dbname = "FitData";
$client = '22D8TS';
$secret = '5cc349811897de9def400cbf8452c69a';
$encoding = base64_encode("$client:$secret");
$url = 'https://api.fitbit.com/oauth2/token';
$sqlDateTime = date_create('now');
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$expiryTime = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$email = $_GET['state'];
echo "email",$email,"<br/>";
if($_GET['error'])
{
	$update = "UPDATE users SET dataAccess = false where email= '$email'";
	$conn->query($update);
	echo "Access Denied!";
	return;
}
else
{
	$update = "UPDATE users SET dataAccess = true where email= '$email'";
        $conn->query($update);
	

}

$searchQuery = "SELECT * FROM users WHERE email='$email' LIMIT 1";
$result = $conn->query($searchQuery);

if($result->num_rows < 1)
{

	// ****************** User not Found in Database **************
	$json_arr = array('error' => "User Not Found");
	$json_data = json_encode($json_arr);
	echo $json_data;
	// REDIRECT TO LOGIN PAGE
}
else
{
	
	$row = $result->fetch_assoc();
	$user_SQL = $row['fitbitID'];
	if($user_SQL=="null")
        {

		// ************* User in Registration ***********
		$reg = true;
		$data = array ('clientId' => '22D8TS', 'grant_type' => 'authorization_code', 'redirect_uri' => 'https://kidsteam.boisestate.edu/kidfit/handle_redirect.php', 'code' => $return_code,'expires_in' => 3600);
		$data = http_build_query($data);
		$opts = array(
                'http'=>array(
                'method'=>"POST",
                'header'=>"Authorization: Basic $encoding\r\n" .
                "Content-Type: application/x-www-form-urlencoded\r\n" .
                          "Content-Length: " . strlen($data) . "\r\n" ,
                'content' => $data
                ));
		$context = stream_context_create($opts);
		$data_authentication = json_decode(file_get_contents($url, false, $context), true);
	        $seconds_to_expire = $data_authentication['expires_in'];
        	$minutes_to_expire = $seconds_to_expire/60;
        	$duration = "+".$minutes_to_expire." minutes";
        	$expiryTime = date("Y/m/d H:i:s", strtotime($duration));
        	$url2 = "https://api.fitbit.com/1/user/".$data_authentication['user_id']."/profile.json";
		$opts2 = array(
                'http'=>array(
                        'method'=>"GET",
                        'header'=>"Authorization: Bearer ".$data_authentication['access_token']."\r\n"
                        )
                );
		$url3 = "https://api.fitbit.com/1/user/".$data_authentication['user_id']."/activities/date/".$todaysDate.".json" ;
	        $user_SQL = $conn->real_escape_string($data_authentication['user_id']);
	        $AC_token = $conn->real_escape_string($data_authentication['access_token']);
	        $RF_token = $conn->real_escape_string($data_authentication['refresh_token']);
	}

	else
	{
	//******* User Loggin In ***************
		$reg = false;
		$AC_token = $row["accessToken"]; 
		$RF_token = $row['refreshToken'];
		$expTime = $row['tokenExpDate'];
		if($currDate>$expTime)
	        {
        	        // ************* If Tokens EXPIRED ***********************";
			$data = array ('grant_type' => 'refresh_token','refresh_token' => $RF_token, 'expires_in' => 3700 );
			$data = http_build_query($data);
			$opt_refresh = array(
                        'http'=>array(
                        'method'=>"POST",
                        'header'=>"Authorization: Basic $encoding\r\n" .
                              "Content-Type: application/x-www-form-urlencoded\r\n" .
                                  "Content-Length: " . strlen($data) . "\r\n" ,
                                'content' => $data
                                )
                        );
			$context = stream_context_create($opt_refresh);
			$data_authentication = json_decode(file_get_contents($url, false, $context), true);
			$seconds_to_expire = $data_authentication['expires_in'];
			$minutes_to_expire = $seconds_to_expire/60;
			$duration = "+".$minutes_to_expire." minutes";
			$expiryTime = date("Y/m/d H:i:s", strtotime($duration));
			$AC_token = $data_authentication['access_token']; 
			$RF_token = $data_authentication['refresh_token'];
			$update = "UPDATE users SET accessToken = '$AC_token', refreshToken = '$RF_token', tokenExpDate = '$expiryTime' where email= '$email'";
			$conn->query($update);
		}

		$url2 = "https://api.fitbit.com/1/user/".$user_SQL."/profile.json";
	        $opts2 = array(
        	         'http'=>array(
                	 'method'=>"GET",
                	 'header'=>"Authorization: Bearer ".$AC_token."\r\n"
                	));
        	$url3 = "https://api.fitbit.com/1/user/".$user_SQL."/activities/date/".$todaysDate.".json";




	}
 	echo $data_authentication; 
	$context = stream_context_create($opts2);
	$file_contents = file_get_contents($url2,false,$context);
	$data_user_profile = json_decode($file_contents, true);	$data_user_user = $data_user_profile['user'];
	$data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
	$data_user_summary = $data_user_activity['summary'];
	$data_user_activities= $data_user_activity['activities'];
	$data_user_goals = $data_user_activity['goals'];
	$data_user_distances = $data_user_summary['distances'];
	$user_Name = $data_user_user['displayName'];
	$timeZone = $data_user_user['timezone'];
	date_default_timezone_set($timeZone);
	$timeZoneDate = date('Y-m-d H:i:s') ;

	// ******** HEART RATE DATA *********
         $hearturl = "https://api.fitbit.com/1/user/".$user_SQL."/activities/heart/date/".$todaysDate."/1d.json" ;
        $data_user_heart = json_decode(file_get_contents($hearturl, false, $context), true);
        $data_user_heart_data = $data_user_heart['activities-heart'][0]['value']['heartRateZones'];
        echo "reg",$reg;


	if($reg==true)
	{
        	$sql = "UPDATE users SET fitbitID ='$user_SQL', userName = '$user_Name', accessToken='$AC_token', refreshToken='$RF_token', tokenExpDate='$expiryTime' where email= '$email'";
        	if ($conn->query($sql) === TRUE)
        	{
        	        $s= "New record created successfully";
		}
        	else
        	{
                	$s= "Error: " . $sql . "<br>" . $conn->error;
        	}

	}

	// ****** Update fitness Table *************
	$search_query = "SELECT userID FROM users WHERE fitbitID = '$user_SQL'";
	$result = $conn->query($search_query);
	if($result->num_rows > 0)
	{
        	$row = $result->fetch_assoc();
        	$userID = $row["userID"];
		
		if($data_user_heart_data[0]['name']=='Out of Range')
                {

                        if($data_user_heart_data[0]['caloriesOut'])
                        {
                                $heartRateZoneOORCalories = $data_user_heart_data[0]['caloriesOut'];
                        }
                        else
                        {
                                $heartRateZoneOORCalories = 0;
                        }
                        if($data_user_heart_data[0]['max'])
                        {
                                $heartRateZoneOORMax = $data_user_heart_data[0]['max'];
                        }
                        else
                        {
                                $heartRateZoneOORMax = 0;
                        }
                        if($data_user_heart_data[0]['min'])
                        {
                                $heartRateZoneOORMin = $data_user_heart_data[0]['min'];
                        }
                        else
                        {
                                $heartRateZoneOORMin =0;
                        }
                        if($data_user_heart_data[0]['minutes'])
                        {
                                $heartRateZoneOORMinutes = $data_user_heart_data[0]['minutes'];
                        }
                        else
                        {
                                $heartRateZoneOORMinutes =0;
                        }

                }
                else
                {
                        $heartRateZoneOORCalories = 0;
                        $heartRateZoneOORMax = 0;
                        $heartRateZoneOORMin = 0;
                        $heartRateZoneOORMinutes = 0;
                }

		if($data_user_heart_data[1]['name']=='Fat Burn')
                {
                    if($data_user_heart_data[1]['caloriesOut'])
                    {
                            $heartRateZoneFatBurnCalories = $data_user_heart_data[1]['caloriesOut'];
                    }
                    else
                    {
                            $heartRateZoneFatBurnCalories = 0;
                    }
                    if($data_user_heart_data[1]['max'])
                    {
                            $heartRateZoneFatBurnMax = $data_user_heart_data[1]['max'];
                    }
                    else
                    {
                            $heartRateZoneFatBurnMax = 0;
                    }
                    if($data_user_heart_data[1]['min'])
                    {
                            $heartRateZoneFatBurnMin = $data_user_heart_data[1]['min'];
                    }
                    else
                    {
                            $heartRateZoneFatBurnMin = 0;
                    }
                    if($data_user_heart_data[1]['minutes'])
                    {
                            $heartRateZoneFatBurnMinutes = $data_user_heart_data[1]['minutes'];

                    }
                    else
                    {
                            $heartRateZoneFatBurnMinutes=0;
                    }
                }
                else
                {
                    $heartRateZoneFatBurnCalories = 0;
                    $heartRateZoneFatBurnMax = 0;
                    $heartRateZoneFatBurnMin = 0;
                    $heartRateZoneFatBurnMinutes = 0;
                }

		if($data_user_heart_data[2]['name']=='Cardio')
                {
                        if($data_user_heart_data[2]['caloriesOut'])
                        {
                                $heartRateZoneCardioCalories = $data_user_heart_data[2]['caloriesOut'];
                        }
                        else
                        {
                                $heartRateZoneCardioCalories = 0;
                        }
                        if($data_user_heart_data[2]['max'])
                        {
                                $heartRateZoneCardioMax = $data_user_heart_data[2]['max'];
                        }
                        else
                        {
                                $heartRateZoneCardioMax=0;
                        }
                        if($data_user_heart_data[2]['min'])
                        {
                                $heartRateZoneCardioMin = $data_user_heart_data[2]['min'];
                        }
                        else
                        {
                                $heartRateZoneCardioMin = 0;
                        }
                        if($data_user_heart_data[2]['minutes'])
                        {
                                $heartRateZoneCardioMinutes = $data_user_heart_data[2]['minutes'];
                        }
                        else
                        {
                                $heartRateZoneCardioMinutes = 0;
                        }
                }
                else
                {
                        $heartRateZoneCardioCalories = 0;
                        $heartRateZoneCardioMax = 0;
                        $heartRateZoneCardioMin = 0;
                        $heartRateZoneCardioMinutes = 0;

                }

		if($data_user_heart_data[3]['name']=='Peak')
                {
                        if($data_user_heart_data[3]['caloriesOut'])
                        {
                                $heartRateZonePeakCalories = $data_user_heart_data[3]['caloriesOut'];
                        }
                        else
                        {
                                $heartRateZonePeakCalories = 0;
                        }
                        if($data_user_heart_data[3]['max'])
                        {
                                $heartRateZonePeakMax = $data_user_heart_data[3]['max'];
                        }
                        else
                        {
                                $heartRateZonePeakMax = 0;
                        }
                        if($data_user_heart_data[3]['min'])
                        {
                                $heartRateZonePeakMin = $data_user_heart_data[3]['min'];
                        }
                        else
                        {
                                $heartRateZonePeakMin = 0;
                        }
                        if($data_user_heart_data[3]['minutes'])
                        {
                                $heartRateZonePeakMinutes = $data_user_heart_data[3]['minutes'];
                        }
                        else
                        {
                                $heartRateZonePeakMinutes = 0;
                        }

                }

                else
                {
                        $heartRateZonePeakCalories = 0;
                        $heartRateZonePeakMax = 0;
                        $heartRateZonePeakMin = 0;
                        $heartRateZonePeakMinutes = 0;
                }

		if($data_user_summary['restingHeartRate'])
                { $restingHeartRate = $data_user_summary['restingHeartRate'];}
                else { $restingHeartRate = 0;}

		

        	if($data_user_distances[7]['activity'])
        	{$treadmill_distance = $data_user_distances[7]['distance'];}
        	else{$treadmill_distance = 0; }

        	if($data_user_goals['caloriesOut'])
              	{$goalCaloriesOut=$data_user_goals['caloriesOut'];}
                else{$goalCaloriesOut=0;}

                if($data_user_goals['distance']){
                $goalDistance = $data_user_goals['distance'];}
                else{$goalDistance =0;}

                if($data_user_goals['floors']){
                        $goalFloors = $data_user_goals['floors'];}
                else{$goalFloors =0;}

                if($data_user_goals['steps']){
                        $goalSteps = $data_user_goals['steps'];}
                else{$goalSteps =0;}
		
		if($data_user_goals['activeMinutes']){
                $goalActiveMinutes = $data_user_goals['activeMinutes'];}
                else{$goalActiveMinutes = 0;}

                if($data_user_summary['activityCalories']){
                        $activityCalories = $data_user_summary['activityCalories'];}
                else{$activityCalories =0;}

                if($data_user_summary['caloriesBMR']){
                        $caloriesBMR = $data_user_summary['caloriesBMR'];}
                else{$caloriesBMR =0;}

                if($data_user_summary['caloriesOut']){
                        $caloriesOut = $data_user_summary['caloriesOut'];}
                else{$caloriesOut =0;}

                if($data_user_distances[1]['distance']){
                        $trackerDistance = $data_user_distances[1]['distance'];}
                else{$trackerDistance = 0;}

                if($data_user_distances[2]['distance']){
                        $loggedActivitiesDistance = $data_user_distances[2]['distance'];}
                else{$loggedActivitiesDistance = 0;}

		if($data_user_distances[0]['distance']){
                        $totalDistance = $data_user_distances[0]['distance'];}
                else{$totalDistance = 0;}

                if($data_user_distances[3]['distance']){
                        $veryActiveDistance = $data_user_distances[3]['distance'];}
                else{$veryActiveDistance = 0;}

                if($data_user_distances[4]['distance']){
                        $moderatelyActiveDistance = $data_user_distances[4]['distance'];}
                else{$moderatelyActiveDistance=0;}

                if($data_user_distances[5]['distance']){
                        $lightlyActiveDistance = $data_user_distances[5]['distance'];}
                else{$lightlyActiveDistance=0;}

                if($data_user_distances[6]['distance']){
                        $sedentaryActiveDistance =$data_user_distances[6]['distance'];}
                else{$sedentaryActiveDistance =0;}

                if($data_user_summary['elevation']){
                        $elevation = $data_user_summary['elevation'];}
                else{$elevation=0;}

                if($data_user_summary['fairlyActiveMinutes']){
                        $fairlyActiveMinutes = $data_user_summary['fairlyActiveMinutes'];}
                else{$fairlyActiveMinutes = 0;}

		if($data_user_summary['floors']){
                        $floors = $data_user_summary['floors'];}
                else{$floors=0;}

                 if($data_user_summary['lightlyActiveMinutes']){
                        $lightlyActiveMinutes = $data_user_summary['lightlyActiveMinutes'];}
                else{$lightlyActiveMinutes=0;}

                if($data_user_summary['marginalCalories']){
                        $marginalCalories = $data_user_summary['marginalCalories'];}
                else{$marginalCalories = 0;}

                if($data_user_summary['sedentaryMinutes']){
                        $sedentaryMinutes =$data_user_summary['sedentaryMinutes'];}
                else{$sedentaryMinutes =0;}

                if($data_user_summary['steps']){
                        $steps = $data_user_summary['steps'];}
                else{$steps=0;}

                if($data_user_summary['veryActiveMinutes']){
                        $veryActiveMinutes = $data_user_summary['veryActiveMinutes'];}
                else{$veryActiveMinutes = 0;}


		$fitness_data = "INSERT INTO fitness_data (userID,fitbitID,pollTime,goalCaloriesOut, goalDistance, goalFloors, goalSteps,activityCalories, caloriesBMR, caloriesOut, trackerDistance, loggedActivitiesDistance, totalDistance, veryActiveDistance, moderatelyActiveDistance, lightlyActiveDistance, sedentaryActiveDistance, treadmillDistance, elevation, fairlyActiveMinutes, floors, lightlyActiveMinutes, marginalCalories, sedentaryMinutes, steps, veryActiveMinutes,goalActiveMinutes, heartRateZoneOORCalories, heartRateZoneOORMax, heartRateZoneOORMin, heartRateZoneOORMinutes, heartRateZoneFatBurnCalories, heartRateZoneFatBurnMax, heartRateZoneFatBurnMin, heartRateZoneFatBurnMinutes, heartRateZoneCardioCalories, heartRateZoneCardioMax, heartRateZoneCardioMin, heartRateZoneCardioMinutes, heartRateZonePeakCalories, heartRateZonePeakMax, heartRateZonePeakMin, heartRateZonePeakMinutes, restingHeartRate) VALUES ('$userID','$user_SQL','$timeZoneDate','$goalCaloriesOut','$goalDistance','$goalFloors','$goalSteps','$activityCalories','$caloriesBMR','$caloriesOut','$trackerDistance', '$loggedActivitiesDistance', '$totalDistance', '$veryActiveDistance', '$moderatelyActiveDistance', '$lightlyActiveDistance', '$sedentaryActiveDistance','$treadmill_distance', '$elevation', '$fairlyActiveMinutes', '$floors', '$lightlyActiveMinutes', '$marginalCalories', '$sedentaryMinutes', '$steps', '$veryActiveMinutes','$goalActiveMinutes', '$heartRateZoneOORCalories', '$heartRateZoneOORMax', '$heartRateZoneOORMin' , '$heartRateZoneOORMinutes' , '$heartRateZoneFatBurnCalories' , '$heartRateZoneFatBurnMax' , '$heartRateZoneFatBurnMin','$heartRateZoneFatBurnMinutes','$heartRateZoneCardioCalories','$heartRateZoneCardioMax','$heartRateZoneCardioMin','$heartRateZoneCardioMinutes','$heartRateZonePeakCalories','$heartRateZonePeakMax','$heartRateZonePeakMin','$heartRateZonePeakMinutes','$restingHeartRate')";

        	if ($conn->query($fitness_data) === TRUE)
        	{
                	$json_arr= array('data_fetch'=>"success");
                	$json_data = json_encode(json_arr);
                	if($reg){echo "Successful Registration! Please close the browser";}
			else {echo "Login Successful! Please close the browser";}
			return;
                }
                else
		{
                        
			$s=  "Fitness Table not updated";
                }
	}


}
?>

 </body>
</html>
