 <?php
/*
INPUT -- username, acessToken

OUTPUT -- $json_arr = array('login'=>"success",'username' => $user_Name, 'fitbitID' => $fitbitID,'goalCaloriesOut' => $goalCaloriesOut,'goalDistance' => $goalDistance, 'goalFloors' => $goalFloors, 'goalSteps' => $goalSteps,'activityCalories' => $activityCalories, 'caloriesBMR' => $caloriesBMR, 'caloriesOut' => $caloriesOut, 'trackerDistance' => $trackerDistance, 'loggedActivitiesDistance' => $loggedActivitiesDistance, 'totalDistance' => $totalDistance, 'veryActiveDistance' => $veryActiveDistance, 'moderatelyActiveDistance' => $moderatelyActiveDistance, 'lightlyActiveDistance' => $lightlyActiveDistance, 'sedentaryActiveDistance' => $sedentaryActiveDistance, 'treadmillDistance' => $treadmillDistance, 'elevation' => $elevation, 'fairlyActiveMinutes' => $fairlyActiveMinutes, 'floors' => $floors, 'lightlyActiveMinutes' => $lightlyActiveMinutes, 'marginalCalories' => $marginalCalories, 'sedentaryMinutes' => $sedentaryMinutes, 'steps' => $steps, 'veryActiveMinutes' => $veryActiveMinutes);


*/
$email = $_GET['username'];
$acToken = $_GET['accessToken'];
// ***** CONNECT TO THE DATABASE ***********

include "appConstants.php";
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$expiryTime = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$heartData=false;
$searchQuery = "SELECT * FROM users WHERE email = '$email'";
$result = $conn->query($searchQuery);
if($result->num_rows > 0)
{
// ****************** User found in Database **************

	$row = $result->fetch_assoc();
	$userID = $row['userID'];
	if(!$row['dataAccess'])
	{
		$json_arr = array('error'=>  "Fitbit Data Access Denied  ", 'errorLog' => "Fitbit Data Access Denied !Login Again",'access' => "0" );
                $json_data = json_encode($json_arr);
                echo $json_data;
		return;

	}
	if($row['appAccessToken']==$acToken)
	{     
		$fitbitID = $row['fitbitID'];
	// *******************Access Token matches with the database Check for expiry***************
		if($currDate > $row['appTokenExpDate'])
		{
		// ***************** Token Expired, Create New Token *************************
			$json_arr = array('error'=> "Token Expired!Login Again", 'errorLog' => "Token Expired!Login Again" );
               	 	$json_data = json_encode($json_arr);
            	    	echo $json_data;
		}
		else
		{
		// ******************* Fetch Data **********************
			$act = $row["accessToken"];
			$rft = $row['refreshToken'];
			if($act == null)
			{
				$json_arr = array('error'=> "Fitbit Access Token Null", 'errorLog' => "Fitbit Access Token Null" );
                        	$json_data = json_encode($json_arr);
                        	echo $json_data;		
				return;
			}
			$expTime = $row['tokenExpDate'];
			$u_id = $row['userID'];
			$user_Name = $row['userName'];
			$fitQuery = "SELECT * FROM fitness_data WHERE userID = '$u_id' ORDER BY pollTime DESC LIMIT 1";
			$result = $conn->query($fitQuery);
			if($result->num_rows > 0) 
			{
        		// ***************** GOT FITNESS DATA OF USER
				$row = $result->fetch_assoc();
				$lastRecorded = $row['pollTime'];
				$lastEntryTime = date("Y-m-d H:i:s",strtotime($lastRecorded));
				$updateTime = date("H:i:s",strtotime("-1 minutes"));
				$timeToUpdate = date("Y-m-d H:i:s", strtotime($lastEntryTime, "+1 minutes"));
				if($timeToUpdate < $currDate)
				{
				// ***********  Last record more than5 minutes old, Get new data ********************
				// ************** Check if token s expired ***************
					 if($currDate > $expTime)
					 { 
					 // **** Token Expired
						$data = array ('grant_type' => 'refresh_token','refresh_token' => $rft, 'expires_in' => 3700 );
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
						$expiryTime = date("Y/m/d H:i:s", strtotime($duration))."\n";
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
					$user_SQL = $fitbitID;
					$AC_token = $act;
					$RF_token = $rft;
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


					// ******** HEART RATE DATA *********
					$hearturl = "https://api.fitbit.com/1/user/".$fitbitID."/activities/heart/date/".$todaysDate."/1d.json" ;
					$data_user_heart = json_decode(file_get_contents($hearturl, false, $context), true);
        				if($data_user_heart['activities-heart'][0]['value']['heartRateZones'])
					{ $heartData=true;
					}
					$data_user_heart_data = $data_user_heart['activities-heart'][0]['value']['heartRateZones'];
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
				   if($timeZoneDate > $timeToUpdate)
				   {	
					$fitness_data = "INSERT INTO fitness_data (userID,fitbitID,pollTime,goalCaloriesOut, goalDistance, goalFloors, goalSteps,activityCalories, caloriesBMR, caloriesOut, trackerDistance, loggedActivitiesDistance, totalDistance, veryActiveDistance, moderatelyActiveDistance, lightlyActiveDistance, sedentaryActiveDistance, treadmillDistance, elevation, fairlyActiveMinutes, floors, lightlyActiveMinutes, marginalCalories, sedentaryMinutes, steps, veryActiveMinutes,goalActiveMinutes, heartRateZoneOORCalories, heartRateZoneOORMax, heartRateZoneOORMin, heartRateZoneOORMinutes, heartRateZoneFatBurnCalories, heartRateZoneFatBurnMax, heartRateZoneFatBurnMin, heartRateZoneFatBurnMinutes, heartRateZoneCardioCalories, heartRateZoneCardioMax, heartRateZoneCardioMin, heartRateZoneCardioMinutes, heartRateZonePeakCalories, heartRateZonePeakMax, heartRateZonePeakMin, heartRateZonePeakMinutes, restingHeartRate) VALUES ('$userID','$user_SQL','$timeZoneDate','$goalCaloriesOut','$goalDistance','$goalFloors','$goalSteps','$activityCalories','$caloriesBMR','$caloriesOut','$trackerDistance', '$loggedActivitiesDistance', '$totalDistance', '$veryActiveDistance', '$moderatelyActiveDistance', '$lightlyActiveDistance', '$sedentaryActiveDistance','$treadmill_distance', '$elevation', '$fairlyActiveMinutes', '$floors', '$lightlyActiveMinutes', '$marginalCalories', '$sedentaryMinutes', '$steps', '$veryActiveMinutes','$goalActiveMinutes', '$heartRateZoneOORCalories', '$heartRateZoneOORMax', '$heartRateZoneOORMin' , '$heartRateZoneOORMinutes' , '$heartRateZoneFatBurnCalories' , '$heartRateZoneFatBurnMax' , '$heartRateZoneFatBurnMin','$heartRateZoneFatBurnMinutes','$heartRateZoneCardioCalories','$heartRateZoneCardioMax','$heartRateZoneCardioMin','$heartRateZoneCardioMinutes','$heartRateZonePeakCalories','$heartRateZonePeakMax','$heartRateZonePeakMin','$heartRateZonePeakMinutes','$restingHeartRate')";
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
					$goalCaloriesOut = $row['goalCaloriesOut'];
                			$goalDistance = $row['goalDistance'];
                			$goalFloors = $row['goalFloors'];
                			$goalSteps = $row['goalSteps'];
					$goalActiveMinutes = $row['goalActiveMinutes'];
                			$activityCalories = $row['activityCalories'];
                			$caloriesBMR = $row['caloriesBMR'];
                			$caloriesOut = $row['caloriesOut'];
                			$trackerDistance = $row['trackerDistance'];
                			$loggedActivitiesDistance = $row['loggedActivitiesDistance'];
                			$totalDistance = $row['totalDistance'];
                			$veryActiveDistance = $row['veryActiveDistance'];
                			$moderatelyActiveDistance = $row['moderatelyActiveDistance'];
                			$lightlyActiveDistance = $row['lightlyActiveDistance'];
                			$sedentaryActiveDistance = $row['sedentaryActiveDistance'];
                			if($row['treadmillDistance']){$treadmillDistance = $row['treadmillDistance'];}else{$treadmillDistance = 0;}
                			$elevation = $row['elevation'];
                			$fairlyActiveMinutes = $row['fairlyActiveMinutes'];
                			$floors = $row['floors'];
					$heartRateZoneOORCalories =$row['heartRateZoneOORCalories'];
					$heartRateZoneOORMax =$row['heartRateZoneOORMax'];
					$heartRateZoneOORMin = $row['heartRateZoneOORMin'];
					$heartRateZoneOORMinutes = $row['heartRateZoneOORMinutes'];
					$heartRateZoneFatBurnCalories = $row['heartRateZoneFatBurnCalories'];
					$heartRateZoneFatBurnMax = $row['heartRateZoneFatBurnMax'];
					$heartRateZoneFatBurnMin = $row['heartRateZoneFatBurnMin'];
					$heartRateZoneFatBurnMinutes = $row['heartRateZoneFatBurnMinutes'];
					$heartRateZoneCardioCalories = $row['heartRateZoneCardioCalories'];
					$heartRateZoneCardioMax = $row['heartRateZoneCardioMax'];
					$heartRateZoneCardioMin = $row['heartRateZoneCardioMin'];
					$heartRateZoneCardioMinutes = $row['heartRateZoneCardioMinutes'];
					$heartRateZonePeakCalories = $row['heartRateZonePeakCalories'];
					$heartRateZonePeakMax = $row['heartRateZonePeakMax'];
					$heartRateZonePeakMin = $row['heartRateZonePeakMin'];
					$heartRateZonePeakMinutes =$row[' heartRateZonePeakMinutes'];
					$restingHeartRate =$row['restingHeartRate'];
                			$lightlyActiveMinutes = $row['lightlyActiveMinutes'];
                			$marginalCalories = $row['marginalCalories'];
               	 			$sedentaryMinutes = $row['sedentaryMinutes'];
                			$steps = $row['steps'];
                			$veryActiveMinutes = $row['veryActiveMinutes'];




				}
				$json_arr = array('login'=>"success",'username' => $user_Name, 'fitbitID' => $fitbitID,'goalCaloriesOut' => $goalCaloriesOut,'goalDistance' => $goalDistance, 'goalFloors' => $goalFloors, 'goalSteps' => $goalSteps,'activityCalories' => $activityCalories, 'caloriesBMR' => $caloriesBMR, 'caloriesOut' => $caloriesOut, 'trackerDistance' => $trackerDistance, 'loggedActivitiesDistance' => $loggedActivitiesDistance, 'totalDistance' => $totalDistance, 'veryActiveDistance' => $veryActiveDistance, 'moderatelyActiveDistance' => $moderatelyActiveDistance, 'lightlyActiveDistance' => $lightlyActiveDistance, 'sedentaryActiveDistance' => $sedentaryActiveDistance, 'treadmillDistance' => $treadmillDistance, 'elevation' => $elevation, 'fairlyActiveMinutes' => $fairlyActiveMinutes, 'floors' => $floors, 'lightlyActiveMinutes' => $lightlyActiveMinutes, 'marginalCalories' => $marginalCalories, 'sedentaryMinutes' => $sedentaryMinutes, 'steps' => $steps, 'veryActiveMinutes' => $veryActiveMinutes, 'goalActiveMinutes'=>$goalActiveMinutes,'heartRateZoneOORCalories'=>$heartRateZoneOORCalories,'heartRateZoneOORMax'=>$heartRateZoneOORMax, 'heartRateZoneOORMin'=>$heartRateZoneOORMin,'heartRateZoneOORMinutes'=>$heartRateZoneOORMinutes, 'heartRateZoneFatBurnCalories'=>$heartRateZoneFatBurnCalories,'heartRateZoneFatBurnMax'=>$heartRateZoneFatBurnMax,'heartRateZoneFatBurnMin'=>$heartRateZoneFatBurnMin,'heartRateZoneFatBurnMinutes'=>$heartRateZoneFatBurnMinutes,'heartRateZoneCardioCalories'=>$heartRateZoneCardioCalories,'heartRateZoneCardioMax'=>$heartRateZoneCardioMax,'heartRateZoneCardioMin'=>$heartRateZoneCardioMin,'heartRateZoneCardioMinutes'=>$heartRateZoneCardioMinutes,'heartRateZonePeakCalories'=>$heartRateZonePeakCalories,'heartRateZonePeakMax'=>$heartRateZonePeakMax,'heartRateZonePeakMin'=>$heartRateZonePeakMin,'heartRateZonePeakMinutes'=>$heartRateZonePeakMinutes,'restingHeartRate'=>$restingHeartRate,'heartData'=>$heartData);
				$json_data = json_encode($json_arr);
                                echo $json_data;

			
				

			}
			else
			{
			// ************ Fitness Data not found, Please Login
				$json_arr = array('error'=> "Fitness Data not found",'errorLog'=>"Fitness Data not found" );
                		$json_data = json_encode($json_arr);
                		echo $json_data;
			}
		}

	}
	else
	{
	// ******************** Access Token Does Not match with Databse, Redirect to login Page
		$json_arr = array('error'=> "Token Does not match", 'errorLog'=>"Token Does not match");
                $json_data = json_encode($json_arr);
		echo $json_data;	
	}

}
else
{
	$json_arr = array('error'=> "User Not found", 'errorLog' => "User Not Found" );
	$json_arr = array('error'=> "User Not found", 'errorLog' => "User Not Found" , 'email' => $email);
        $json_data = json_encode($json_arr);
        echo $json_data;
}

?>		
