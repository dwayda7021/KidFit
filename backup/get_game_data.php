 <?php

$email = $_GET['username'];
$acToken = $_GET['accessToken'];
$gameID = $_GET['gameID'];
// ***** CONNECT TO THE DATABASE ***********

$servername = "127.0.0.1";
$username = "root";
$password = "K1dzteam!";
$dbname = "FitData";
$client = '228NH4';
$secret = 'acd369c14cacd73f6985f84b24d4267d';
$encoding = base64_encode("$client:$secret");
$url = 'https://api.fitbit.com/oauth2/token';
$sqlDateTime = date_create('now');
$currDate = date("Y-m-d", strtotime("now"));
$currDateTime = date("Y-m-d H:i:s", strtotime("now"));
$expiryTime = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userQuery = "SELECT * FROM users WHERE email ='$email'";
$result = $conn->query($userQuery);
$row=$result->fetch_assoc();
$userID = $row['userID'];

 // ********** Match Access Token ******************

if($row['appAccessToken']==$acToken)
{
	$fitQuery = "SELECT * FROM fitness_data WHERE userID = '$userID' ORDER BY pollTime DESC LIMIT 1";
	$result = $conn->query($fitQuery);
	$prevFitnessData = $result->fetch_assoc();

	if($result->num_rows < 1)
	{
		$json_arr = array('error'=> "No Fitness Data", 'errorLog' => "No Fitness Data" ,'email'=>$email);
		$json_data = json_encode($json_arr);
		echo $json_data;
	}
	else
	{
		$AC_token = $row["accessToken"];
		$RF_token = $row['refreshToken'];
		$expTime = $row['tokenExpDate'];
		$user_SQL = $row['fitbitID'];
		if($currDateTime > $expTime)
		{
			// ************* If Tokens EXPIRED ***********************";

			$data = array ('grant_type' => 'refresh_token','refresh_token' => $RF_token );
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
		
		$context = stream_context_create($opts2);
        	$file_contents = file_get_contents($url2,false,$context);
        	$data_user_profile = json_decode($file_contents, true); $data_user_user = $data_user_profile['user'];
        	$data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
        	$data_user_summary = $data_user_activity['summary'];
        	$data_user_activities= $data_user_activity['activities'];
        	$data_user_goals = $data_user_activity['goals'];
        	$data_user_distances = $data_user_summary['distances'];
       		$user_Name = $data_user_user['displayName'];
		$timeZone = $data_user_user['timezone'];
		date_default_timezone_set($timeZone);
		$timeZoneDate = date('Y-m-d H:i:s') ;		
		
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

		$fitness_data = "INSERT INTO fitness_data (userID,fitbitID,pollTime,goalCaloriesOut, goalDistance, goalFloors, goalSteps,activityCalories, caloriesBMR, caloriesOut, trackerDistance, loggedActivitiesDistance, totalDistance, veryActiveDistance, moderatelyActiveDistance, lightlyActiveDistance, sedentaryActiveDistance, treadmillDistance, elevation, fairlyActiveMinutes, floors, lightlyActiveMinutes, marginalCalories, sedentaryMinutes, steps, veryActiveMinutes) VALUES ('$userID','$user_SQL','$timeZoneDate','$goalCaloriesOut','$goalDistance','$goalFloors','$goalSteps','$activityCalories','$caloriesBMR','$caloriesOut','$trackerDistance', '$loggedActivitiesDistance', '$totalDistance', '$veryActiveDistance', '$moderatelyActiveDistance', '$lightlyActiveDistance', '$sedentaryActiveDistance','$treadmill_distance', '$elevation', '$fairlyActiveMinutes', '$floors', '$lightlyActiveMinutes', '$marginalCalories', '$sedentaryMinutes', '$steps', '$veryActiveMinutes')";


		if ($conn->query($fitness_data) === TRUE)
                {
			$fitQuery = "SELECT * FROM fitness_data WHERE userID = '$userID' ORDER BY pollTime DESC LIMIT 1";
        		$result = $conn->query($fitQuery);
        		$newFitnessData = $result->fetch_assoc();
		}
		else
		{
			$json_arr= array('error' =>" New Data Fetch Failed");
			$json_data = json_encode($json_arr);
			echo $json_data;
			return;
		}	
		
		$lastEntry = $prevFitnessData['pollTime'];
		$newEntry = $newFitnessData['pollTime'];
		$lastEntryDate = date("Y-m-d",strtotime($lastEntry));
		$newEntryDate = date("Y-m-d",strtotime($newEntry));
		if($lastEntryDate < $newEntryDate)
		{
			$url3 = "https://api.fitbit.com/1/user/".$user_SQL."/activities/date/".$lastEntryDate.".json";
			$data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
			$data_user_summary = $data_user_activity['summary'];
			$data_user_activities= $data_user_activity['activities'];
			$data_user_goals = $data_user_activity['goals'];
			$data_user_distances = $data_user_summary['distances'];

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
		
			$diffDatasteps = $steps-$prevFitnessData['steps']+$newFitnessData['steps'];
			
			$diffDataactivityDistance = $veryActiveDistance+$moderatelyActiveDistance-$prevFitnessData['veryActiveDistance']-$prevFitnessData['moderatelyActiveDistance']+$newFitnessData['veryActiveDistance']+$newFitnessData['moderatelyActiveDistance'];

			$diffDatatotalDistance = $totalDistance- $prevFitnessData['totalDistance']+$newFitnessData['totalDistance'];
			
			$diffDataactivityTime = $fairlyActiveMinutes+$veryActiveMinutes + $newFitnessData['fairlyActiveMinutes']+$newFitnessData['veryActiveMinutes']-$prevFitnessData['fairlyActiveMinutes']-$prevFitnessData['veryActiveMinutes'];
			
			$userGameUpdate = "INSERT INTO user_games (userID, gameID, createDate, stepsCollected, activeTimeCollected) VALUES ('$userID','$gameID','$currDateTime','$diffDatasteps','$diffDataactivityTime')";
			$conn->query($userGameUpdate);

			$json_arr = array('steps' => $diffDatasteps, 'activityDistance' => $diffDataactivityDistance, 'totalDistance' => $diffDatatotalDistance, 'activityTime' => $diffDataactivityTime,'username' => $user_Name,);
                        $json_data = json_encode($json_arr);
                        echo $json_data;
		}
		if($lastEntryDate == $newEntryDate)
		{

			

			$diffDatasteps = $newFitnessData['steps']-$prevFitnessData['steps'];
			$diffDataactivityDistance = $newFitnessData['veryActiveDistance']+$newFitnessData['moderatelyActiveDistance']-$prevFitnessData['veryActiveDistance']-$prevFitnessData['moderatelyActiveDistance'];
			$diffDatatotalDistance = $newFitnessData['totalDistance']- $prevFitnessData['totalDistance'];
			$diffDataactivityTime = $newFitnessData['fairlyActiveMinutes']+$newFitnessData['veryActiveMinutes']-$prevFitnessData['fairlyActiveMinutes']-$prevFitnessData['veryActiveMinutes'];
			$userGameUpdate = "INSERT INTO user_games (userID, gameID, createDate, stepsCollected, activeTimeCollected) VALUES ('$userID','$gameID','$currDateTime','$diffDatasteps','$diffDataactivityTime')";
			$conn->query($userGameUpdate);
			$json_arr = array('steps' => $diffDatasteps, 'activityDistance' => $diffDataactivityDistance, 'totalDistance' => $diffDatatotalDistance, 'activityTime' => $diffDataactivityTime,'username' => $user_Name,);
			$json_data = json_encode($json_arr);
			echo $json_data;

		}
	}
}
else
{
$json_arr = array('error' =>"acces token didnt match");
$json_data = json_encode($json_arr);
echo $json_data;


}
?>
