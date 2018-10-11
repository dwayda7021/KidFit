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
$client = '228NH4';
$secret = 'acd369c14cacd73f6985f84b24d4267d';
$encoding = base64_encode("$client:$secret");
$url = 'https://api.fitbit.com/oauth2/token';
$sqlDateTime = date_create('now');
$currDate = date_format($sqlDateTime, 'Y-m-d H:i:s');
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


//  ******  check if the user is already authenticated/ or access token exists ****** 
$cookie_name = 'fitbitID';
if(!isset($_COOKIE[$cookie_name])) {

// *********** Cookie Not Found, Authenticate user ***********

    echo "Checking if user already in database Cookie named is not set!";
    $data = array ('clientId' => '228NH4', 'grant_type' => 'authorization_code', 'redirect_uri' => 'http://kidsteam.boisestate.edu/kidfit/handle_redirect.php', 'code' => $return_code,'expires_in' => 3600);

    $data = http_build_query($data);
    $opts = array(
  	'http'=>array(
    	'method'=>"POST",
    	'header'=>"Authorization: Basic $encoding\r\n" .
              "Content-Type: application/x-www-form-urlencoded\r\n" .
                          "Content-Length: " . strlen($data) . "\r\n" ,
                'content' => $data
                )
        );
$context = stream_context_create($opts);
$data_authentication = json_decode(file_get_contents($url, false, $context), true);

//echo" <br/> opts";
//print_r($data_authentication);
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

} else {

// ***** Cookie Found, get details from Database ********


 echo"Inside else case <br/>";
    $searchQuery = "SELECT * FROM users WHERE fitbitID = '$_COOKIE[$cookie_name]'";
    $result = $conn->query($searchQuery);
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
       $act = $row["accessToken"];
       $rft = $row['refreshToken'];
       $id = $row['fitbitID'];
	$url2 = "https://api.fitbit.com/1/user/".$id."/profile.json";
	$opts2 = array(
 	 'http'=>array(
   	 'method'=>"GET",
   	 'header'=>"Authorization: Bearer ".$act."\r\n"
                )
        );
	$url3 = "https://api.fitbit.com/1/user/".$id."/activities/date/".$todaysDate.".json" ;
	$user_SQL = $id;
	$AC_token = $act;
        $RF_token = $rft;
}


}

// *********** Get User Data ****************


$context = stream_context_create($opts2);
 $ch = curl_init();
//curl_setopt($ch, CURLOPT_URL,$url2);
//curl_setopt($ch, CURLOPT_POST, 1);
//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
//curl_exec($ch);
//$file_contents = file_get_contents($url2,false,$context);
$file_contents = fopen($url2,false,$context);

echo"file conetnst";
print_r($file_contents);
echo "<br/>context";
echo $context;
if(!$file_contents)
{ echo "file contents are null";}
print_r($file_contents);
$data_user_profile = json_decode($file_contents, true);
echo"<br/> Data User Profile";
echo "<br/> <br/> <br/>";
if($data_user_profile['errors'] ){
	print_r($data_user_profile['errors']);
}

print_r($data_user_profile);
$data_user_user = $data_user_profile['user'];
$data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
$data_user_summary = $data_user_activity['summary'];
$data_user_activities= $data_user_activity['activities'];
$data_user_goals = $data_user_activity['goals'];
print_r($data_user_activity['goals']['steps']);
$data_user_distances = $data_user_summary['distances'];
$date_SQL = $conn->real_escape_string($currDate);
$user_Name = $data_user_user['displayName'];
echo"<br/> Data User Profile";
echo "<br/> <br/> <br/>";
print_r($data_user_activity);


// ****** Insert the user, if Cookie not Found *********

 if(!isset($_COOKIE[$cookie_name])) {
    $sql = "INSERT INTO users (fitbitID, userName, createDate, accessToken, refreshToken) VALUES ('$user_SQL','$user_Name','$currDate','$AC_token', '$RF_token')";
    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

}


// ************** Get User ID of the user from Database ********************
$search_query = "SELECT userID FROM users WHERE fitbitID = '$user_SQL'";
$result = $conn->query($search_query);
if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
   $userID = $row["userID"];
    print_r($userID);


print_r("Updating fitness Data");
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
print_r($fitness_data);  
 if ($conn->query($fitness_data) === TRUE) {
        echo "Fitness Table updates";
        }
    else{
        echo "Fitness Table not updated";
        }
    }
else
{
    echo "INSIDE ELSE CASE";
}
$cookie_value = $user_SQL;
setcookie($cookie_name, $cookie_value, time() + (86400 * 30));
if(!isset($_COOKIE[$cookie_name])) {
    echo "2Cookie named '" . $cookie_name . "' is not set!";
} else {
    echo "2Cookie '" . $cookie_name . "' is set!<br>";
    echo "2Value is: " . $_COOKIE[$cookie_name];
}


$conn->close();

// Part 5
// Now that we've gotten our data into the database, re-direct the user to a landing page with a centered ok button to flip them back to the Ionic App's main page:
ob_start();
//header("Location: http://kidsteam.boisestate.edu/kidfit/user_accept.php?user_id={$user_SQL}&username={$data_user_user['displayName']}&steps={$data_user_summary['steps']}&lightlyActiveMinutes={$data_user_summary['lightlyActiveMinutes']}&fairlyActiveMinutes={$data_user_summary['fairlyActiveMinutes']}&veryActiveMinutes={$data_user_summary['veryActiveMinutes']}"); 
ob_end_flush();
 ?> 
 


 </body>
</html>
