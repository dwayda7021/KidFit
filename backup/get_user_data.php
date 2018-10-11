<?php
//include 'CSS/indexStyle.css';
?>
 <?php
// ********************************* Fetch the current user fitness_data and return as JSON object for provided fitbitID & personal A/T **//
//   1. Verify that user & access token are a matched pair to the user located in users table
//      a. If their token and user fitbitID match, proceed.
//      b. else -- return 403 error in JSON packet payload.
//   2. Check if their most recent fitness data is within the last minute.
//      a. IF it is, then package their fitness_data into a JSON object and return that via POST.
//      b. IF it is not, then:
//         b.1 Call the "update_user_fitness_data function with the provided fitbitID and update the database.
//         b.2 Package new data into JSON object and return via POST


//$user_ID = $_POST['user_id'];
$user_ID = $_GET['user_id'];
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
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$expiryTime = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$searchQuery = "SELECT * FROM users WHERE fitbitID = '$user_ID'";
    $result = $conn->query($searchQuery);
    if($result->num_rows > 0)
 	{ //IF USER FOUND IN DATABASE
// ****************** User found in Database **************
       $row = $result->fetch_assoc();
       $act = $row["accessToken"];
       $rft = $row['refreshToken'];
       $expTime = $row['tokenExpDate'];
       $u_id = $row['userID'];
       $fitQuery = "SELECT * FROM fitness_data WHERE userID = '$u_id' ORDER BY pollTime DESC LIMIT 1";
	$result = $conn->query($fitQuery);
	if($result->num_rows > 0) {  
	// GOT FITNESS DATA OF USER
	  $row = $result->fetch_assoc();
	  $lastRecorded = $row['pollTime'];
	  $timeToUpdate = date("Y-m-d H:i:s", strtotime( "-5 minutes"))."\n";
	  //print_r($lastRecorded);
	  //echo "<br/>";
	  //print_r($timeToUpdate);
	  //echo "<br/>";
	  if($timeToUpdate > $lastRecorded)
	   { // IF TIME TO GET NEW DATA
		//echo"//fetch data using act";
// ***********  Last record more than5 minutes old, Get new data ********************
		
// ************** Check if token s expired ***************
	        if($currDate > $expTime)
        	{   //IF TOKEN EXPIRED
			//echo "<br/> Token Expired <br/>";	
			$data = array ('grant_type' => 'refresh_token','refresh_token' => $rft, 'expires_in' => 3700 );
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
			$expiryTime = date("Y/m/d H:i:s", strtotime($duration))."\n";
			$act = $data_authentication['access_token'];
			$rft = $data_authentication['refresh_token'];
			$update = "UPDATE users SET accessToken = '$act', refreshToken = '$rft', tokenExpDate = '$expiryTime' WHERE fitbitID ='$user_SQL'";
			$conn->query($update);
		}// IF TOKEN EXPIRED
		

		$url2 = "https://api.fitbit.com/1/user/".$user_ID."/profile.json";
	        $opts2 = array(
        	 'http'=>array(
        	 'method'=>"GET",
         	'header'=>"Authorization: Bearer ".$act."\r\n"
                	)
       		 );
        	$url3 = "https://api.fitbit.com/1/user/".$user_ID."/activities/date/".$todaysDate.".json" ;
        	$user_SQL = $user_ID;
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
	
		$fitness_data = "INSERT INTO fitness_data (userID,fitbitID,pollTime,goalCaloriesOut, goalDistance, goalFloors, goalSteps,activityCalories, caloriesBMR, caloriesOut, trackerDistance, loggedActivitiesDistance, totalDistance, veryActiveDistance, moderatelyActiveDistance, lightlyActiveDistance, sedentaryActiveDistance, treadmillDistance, elevation, fairlyActiveMinutes, floors, lightlyActiveMinutes, marginalCalories, sedentaryMinutes, steps, veryActiveMinutes) VALUES ('$userID','$user_SQL','$currDate','$goalCaloriesOut','$goalDistance','$goalFloors','$goalSteps','$activityCalories','$caloriesBMR','$caloriesOut','$trackerDistance', '$loggedActivitiesDistance', '$totalDistance', '$veryActiveDistance', '$moderatelyActiveDistance', '$lightlyActiveDistance', '$sedentaryActiveDistance','$treadmill_distance', '$elevation', '$fairlyActiveMinutes', '$floors', '$lightlyActiveMinutes', '$marginalCalories', '$sedentaryMinutes', '$steps', '$veryActiveMinutes')";

    	     }// IF TIME TO GET NEW DATA
	  else
	  {
		//echo "fetch data from database";
		$goalCaloriesOut = $row['goalCaloriesOut'];
		$goalDistance = $row['goalDistance'];
		$goalFloors = $row['goalFloors'];
		$goalSteps = $row['goalSteps'];
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
	        if($row['treadmillDistance']){/*echo "non zero treadmill";*/$treadmillDistance = $row['treadmillDistance'];}else{$treadmillDistance = 0;}
		$elevation = $row['elevation'];
		$fairlyActiveMinutes = $row['fairlyActiveMinutes'];
		$floors = $row['floors'];
		$lightlyActiveMinutes = $row['lightlyActiveMinutes'];
		$marginalCalories = $row['marginalCalories'];
		$sedentaryMinutes = $row['sedentaryMinutes'];
		$steps = $row['steps'];
		$veryActiveMinutes = $row['veryActiveMinutes'];

           }// ELSE TIME TO GET NEW DATA
	} // Else failed get fitness data of user
} //  IF USER FOUND IN DATABASE

$json_arr = array('fitbitID' => $user_ID,'goalCaloriesOut' => $goalCaloriesOut,'goalDistance' => $goalDistance, 'goalFloors' => $goalFloors, 'goalSteps' => $goalSteps,'activityCalories' => $activityCalories, 'caloriesBMR' => $caloriesBMR, 'caloriesOut' => $caloriesOut, 'trackerDistance' => $trackerDistance, 'loggedActivitiesDistance' => $loggedActivitiesDistance, 'totalDistance' => $totalDistance, 'veryActiveDistance' => $veryActiveDistance, 'moderatelyActiveDistance' => $moderatelyActiveDistance, 'lightlyActiveDistance' => $lightlyActiveDistance, 'sedentaryActiveDistance' => $sedentaryActiveDistance, 'treadmillDistance' => $treadmillDistance, 'elevation' => $elevation, 'fairlyActiveMinutes' => $fairlyActiveMinutes, 'floors' => $floors, 'lightlyActiveMinutes' => $lightlyActiveMinutes, 'marginalCalories' => $marginalCalories, 'sedentaryMinutes' => $sedentaryMinutes, 'steps' => $steps, 'veryActiveMinutes' => $veryActiveMinutes);


$test_json = json_encode($json_arr);

echo $test_json;

?>
