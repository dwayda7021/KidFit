<?php


/*

fetchFitBitData.php

INPUT -- username, acessToken


$email = $_GET['username'];
$acToken = $_GET['accessToken'];


OUTPUT -- $json_arr = array('login'=>"success",'username' => $user_Name, 'fitbitID' => $fitbitID,'goalCaloriesOut' => $goalCaloriesOut,'goalDistance' => $goalDistance, 'goalFloors' => $goalFloors, 'goalSteps' => $goalSteps,'activityCalories' => $activityCalories, 'caloriesBMR' => $caloriesBMR, 'caloriesOut' => $caloriesOut, 'trackerDistance' => $trackerDistance, 'loggedActivitiesDistance' => $loggedActivitiesDistance, 'totalDistance' => $totalDistance, 'veryActiveDistance' => $veryActiveDistance, 'moderatelyActiveDistance' => $moderatelyActiveDistance, 'lightlyActiveDistance' => $lightlyActiveDistance, 'sedentaryActiveDistance' => $sedentaryActiveDistance, 'treadmillDistance' => $treadmillDistance, 'elevation' => $elevation, 'fairlyActiveMinutes' => $fairlyActiveMinutes, 'floors' => $floors, 'lightlyActiveMinutes' => $lightlyActiveMinutes, 'marginalCalories' => $marginalCalories, 'sedentaryMinutes' => $sedentaryMinutes, 'steps' => $steps, 'veryActiveMinutes' => $veryActiveMinutes);

*/




// ***** CONNECT TO THE DATABASE ***********

include "appConstants.php";
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$searchQuery = "SELECT * FROM users WHERE email = '$email'";
$searchResult = $conn->query($searchQuery);

if($searchResult->num_rows > 0)
{
// ****************** User found in Database **************

        $searchRow = $searchResult->fetch_assoc();
        $userID = $searchRow['userID'];
        if(!$searchRow['dataAccess'])
        {
                $json_arr = array('error'=>  "Fitbit Data Access Denied  ", 'errorLog' => "Fitbit Data Access Denied !Login Again",'access' => "0" );
                $json_data = json_encode($json_arr);
                echo $json_data;
                return;

        }
        if($searchRow['appAccessToken']==$acToken)
        {
                $fitbitID = $searchRow['fitbitID'];
		$act = $searchRow["accessToken"];
		$rft = $searchRow['refreshToken'];
		$expTime = $searchRow['tokenExpDate'];
		$user_Name = $searchRow['userName'];
		$fitQuery = "SELECT * FROM fitness_data WHERE userID = '$userID' ORDER BY pollTime DESC LIMIT 1";	
		$fitResult = $conn->query($fitQuery);
		if($fitResult->num_rows > 0)
		{
			$fitRow = $fitResult->fetch_assoc();
			$lastRecorded = $fitRow['pollTime'];
			$lastEntryTime = date("Y-m-d H:i:s",strtotime($lastRecorded));
			include "appConstants.php";
			$num_minutes = $timeForFitBitDataFetch;
			$timeToUpdate = date("Y-m-d H:i:s", strtotime("+".$num_minutes." minutes", strtotime($lastEntryTime)));
			if($timeToUpdate < $currDate)
			{
				// ****** LAST RECORD OLD*********
				if($currDate > $expTime)
				{
					// ************ Token Expired***********
				
					$data = array ('grant_type' => 'refresh_token','refresh_token' => $rft);
					$data = http_build_query($data);
					$opt_refresh = array(
                                                'http'=>array(
                                                'method'=>"POST",
                                                'header'=>"Authorization: Basic $encoding\r\n" .
                                                "Content-Type: application/x-www-form-urlencoded\r\n" .
                                                "Content-Length: " . strlen($data) . "\r\n" ,
                                                'content' => $data
                                                ));
					$context = stream_context_create($opt_refresh);
					$data_authentication = json_decode(file_get_contents($url, false, $context), true);
					$seconds_to_expire = $data_authentication['expires_in'];
					$minutes_to_expire = $seconds_to_expire/60;
					$duration = "+".$minutes_to_expire." minutes";
					$expiryTime = date("Y/m/d H:i:s", strtotime($duration));
					$act = $data_authentication['access_token'];
					$rft = $data_authentication['refresh_token'];
					$update = "UPDATE users SET accessToken = '$act', refreshToken = '$rft', tokenExpDate = '$expiryTime' WHERE fitbitID ='$fitbitID'";
					$conn->query($update);
				}

				
				$url2 = "https://api.fitbit.com/1/user/".$fitbitID."/profile.json";
				$opts2 = array(
                                        'http'=>array(
                                        'method'=>"GET",
                                        'header'=>"Authorization: Bearer ".$act."\r\n"
                                        ));
				$url3 = "https://api.fitbit.com/1/user/".$fitbitID."/activities/date/".$todaysDate.".json" ;
			 	$context = stream_context_create($opts2);
				$file_contents = file_get_contents($url2,false,$context);
				$data_user_profile = json_decode($file_contents, true);
				$data_user_user = $data_user_profile['user'];
				$data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
				$data_user_summary = $data_user_activity['summary'];
				$data_user_activities= $data_user_activity['activities'];
				$data_user_goals = $data_user_activity['goals'];
				$data_user_distances = $data_user_summary['distances'];
				$date_SQL = $conn->real_escape_string($currDate);
				$user_Name = $data_user_user['displayName'];
				$timeZone = $data_user_user['timezone'];
				date_default_timezone_set($timeZone);
				$timeZoneDate = date('Y-m-d H:i:s');
								
				$treadmill_distance = 0;
				$goalCaloriesOut=0;
				$goalDistance =0;
				$goalFloors =0;
				$goalSteps =0;
				$activityCalories =0;
				$caloriesBMR =0;
				$caloriesOut =0;
				$trackerDistance = 0;
				$loggedActivitiesDistance = 0;
				$totalDistance = 0;
				$veryActiveDistance = 0;
				$moderatelyActiveDistance=0;
				$lightlyActiveDistance=0;
				$sedentaryActiveDistance =0;
				$elevation=0;
				$fairlyActiveMinutes = 0;
				$floors=0;
				$lightlyActiveMinutes=0;
				$marginalCalories = 0;
				$sedentaryMinutes =0;
				$steps=0;
				$veryActiveMinutes = 0;


				if($data_user_distances[7]['activity'])
                                {
					$treadmill_distance = $data_user_distances[7]['distance'];
				}

				if($data_user_goals['caloriesOut'])
				{	
					$goalCaloriesOut=$data_user_goals['caloriesOut'];
				}

				if($data_user_goals['distance'])
				{
					$goalDistance = $data_user_goals['distance'];
				}
			
				if($data_user_goals['floors'])
				{
					$goalFloors = $data_user_goals['floors'];
				}
	
				if($data_user_goals['steps'])
				{
					$goalSteps = $data_user_goals['steps'];
				}
				
				if($data_user_summary['activityCalories'])
				{
					$activityCalories = $data_user_summary['activityCalories'];
				}
	
				if($data_user_summary['caloriesBMR'])
				{
					$caloriesBMR = $data_user_summary['caloriesBMR'];
				}
				
				if($data_user_summary['caloriesOut'])
				{
					$caloriesOut = $data_user_summary['caloriesOut'];
				}
		
				if($data_user_distances[1]['distance'])
				{
					$trackerDistance = $data_user_distances[1]['distance'];
				}
			
				if($data_user_distances[2]['distance'])
				{
					$loggedActivitiesDistance = $data_user_distances[2]['distance'];
				}

				if($data_user_distances[0]['distance'])
				{
					$totalDistance = $data_user_distances[0]['distance'];
				}

				if($data_user_distances[3]['distance'])
				{
					$veryActiveDistance = $data_user_distances[3]['distance'];
				}
			
				if($data_user_distances[4]['distance'])
				{
					$moderatelyActiveDistance = $data_user_distances[4]['distance'];
				}

				if($data_user_distances[5]['distance'])
				{
					$lightlyActiveDistance = $data_user_distances[5]['distance'];
				}

				if($data_user_distances[6]['distance'])
				{
					$sedentaryActiveDistance =$data_user_distances[6]['distance'];
				}
				
				if($data_user_summary['elevation'])
				{
					$elevation = $data_user_summary['elevation'];
				}

				if($data_user_summary['fairlyActiveMinutes'])
				{
					$fairlyActiveMinutes = $data_user_summary['fairlyActiveMinutes'];
				}

				if($data_user_summary['floors'])
				{
					$floors = $data_user_summary['floors'];
				}
			
				if($data_user_summary['lightlyActiveMinutes'])
				{
					$lightlyActiveMinutes = $data_user_summary['lightlyActiveMinutes'];
				}
				
				if($data_user_summary['marginalCalories'])
				{
					$marginalCalories = $data_user_summary['marginalCalories'];
				}
		
				if($data_user_summary['sedentaryMinutes'])
				{
					$sedentaryMinutes =$data_user_summary['sedentaryMinutes'];
				}
				
				if($data_user_summary['steps'])
				{
					$steps = $data_user_summary['steps'];
				}

				if($data_user_summary['veryActiveMinutes'])
				{
					$veryActiveMinutes = $data_user_summary['veryActiveMinutes'];
				}
		
				if($timeZoneDate > $timeToUpdate)
                                {
                                        $fitness_data = "INSERT INTO fitness_data (userID,fitbitID,pollTime,goalCaloriesOut, goalDistance, goalFloors, goalSteps,activityCalories, caloriesBMR, caloriesOut, trackerDistance, loggedActivitiesDistance, totalDistance, veryActiveDistance, moderatelyActiveDistance, lightlyActiveDistance, sedentaryActiveDistance, treadmillDistance, elevation, fairlyActiveMinutes, floors, lightlyActiveMinutes, marginalCalories, sedentaryMinutes, steps, veryActiveMinutes) VALUES ('$userID','$fitbitID','$timeZoneDate','$goalCaloriesOut','$goalDistance','$goalFloors','$goalSteps','$activityCalories','$caloriesBMR','$caloriesOut','$trackerDistance', '$loggedActivitiesDistance', '$totalDistance', '$veryActiveDistance', '$moderatelyActiveDistance', '$lightlyActiveDistance', '$sedentaryActiveDistance','$treadmill_distance', '$elevation', '$fairlyActiveMinutes', '$floors', '$lightlyActiveMinutes', '$marginalCalories', '$sedentaryMinutes', '$steps', '$veryActiveMinutes')";
                                        if($conn->query($fitness_data)===FALSE)
                                        {
                                                $json_arr = array('error'=> $userID);
                                                $json_data = json_encode($json_arr);
                                                echo $json_data;
                                                return;
                                        }
				}
				

			}
			else
			{
				$goalCaloriesOut = $fitRow['goalCaloriesOut'];
				$goalDistance = $fitRow['goalDistance'];
                                $goalFloors = $fitRow['goalFloors'];
				$goalSteps = $fitRow['goalSteps'];
                                $activityCalories = $fitRow['activityCalories'];
                                $caloriesBMR = $fitRow['caloriesBMR'];
                                $caloriesOut = $fitRow['caloriesOut'];
                                $trackerDistance = $fitRow['trackerDistance'];
                                $loggedActivitiesDistance = $fitRow['loggedActivitiesDistance'];
                                $totalDistance = $fitRow['totalDistance'];
                                $veryActiveDistance = $fitRow['veryActiveDistance'];
                                $moderatelyActiveDistance = $fitRow['moderatelyActiveDistance'];
                                $lightlyActiveDistance = $fitRow['lightlyActiveDistance'];
                                $sedentaryActiveDistance = $fitRow['sedentaryActiveDistance'];
                                $treadmillDistance = $fitRow['treadmillDistance'];
                                $elevation = $fitRow['elevation'];
                                $fairlyActiveMinutes = $fitRow['fairlyActiveMinutes'];
                                $floors = $fitRow['floors'];
                                $lightlyActiveMinutes = $fitRow['lightlyActiveMinutes'];
                                $marginalCalories = $fitRow['marginalCalories'];
                                $sedentaryMinutes = $fitRow['sedentaryMinutes'];
                                $steps = $fitRow['steps'];
                                $veryActiveMinutes = $fitRow['veryActiveMinutes'];
			}
			$json_arr = array('login'=>"success",'username' => $user_Name, 'fitbitID' => $fitbitID,'goalCaloriesOut' => $goalCaloriesOut,'goalDistance' => $goalDistance, 'goalFloors' => $goalFloors, 'goalSteps' => $goalSteps,'activityCalories' => $activityCalories, 'caloriesBMR' => $caloriesBMR, 'caloriesOut' => $caloriesOut, 'trackerDistance' => $trackerDistance, 'loggedActivitiesDistance' => $loggedActivitiesDistance, 'totalDistance' => $totalDistance, 'veryActiveDistance' => $veryActiveDistance, 'moderatelyActiveDistance' => $moderatelyActiveDistance, 'lightlyActiveDistance' => $lightlyActiveDistance, 'sedentaryActiveDistance' => $sedentaryActiveDistance, 'treadmillDistance' => $treadmillDistance, 'elevation' => $elevation, 'fairlyActiveMinutes' => $fairlyActiveMinutes, 'floors' => $floors, 'lightlyActiveMinutes' => $lightlyActiveMinutes, 'marginalCalories' => $marginalCalories, 'sedentaryMinutes' => $sedentaryMinutes, 'steps' => $steps, 'veryActiveMinutes' => $veryActiveMinutes);
                        $json_data = json_encode($json_arr);
		}

	}
}
			








?>
