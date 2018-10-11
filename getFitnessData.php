<?php
/*  getFitnessData.php
 INPUT -- EMAIL, ACCESS TOKEN , GAMESINSTANCE ID
OUTPUT -- STEPS AND ACTIVE MINUTES */



function sendNotification($to, $data, $message)
{
        $serverKey = 'AAAAXqJwzGY:APA91bEE--VjeC0tLQr21uoHnMjDIx8Cr6nASML1t89r9BIB5RAjanlmXVb8Waglk7IqkiU3s0e7b_GC_eVoBuK9jsLegCegdMaIphzzfP_wazBs0tCaKDyEDYjPwTjtH-BkQotQ1uScfIu1PnM_hZzVGdLD5jptwg';
        define( 'API_ACCESS_KEY', $serverKey );

        $msg = array
        (
                'message'       => $message,
                'title'         => '',
                'subtitle'      => '',
                'tickerText'    => '',
                'vibrate'       => 1,
                'sound'         => 1,
                'largeIcon'     => 'large_icon',
                'smallIcon'     => 'small_icon',
                'gameInstanceID' => 1,
        );
        $fields = array
        (
                'to'    => $to,
                'notification'                  => $msg,
                'data' =>$data
        );

        $headers = array
        (
                'Authorization: key=' . API_ACCESS_KEY,
                'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
}

$self= $_GET['self'];
$accessToken = $_GET['accessToken'];
$gameInstanceID = $_GET['gameInstanceID'];

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
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$expiryTime = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
//$gameInstanceID=1;


if ($conn->connect_error) {

   die("Connection failed: " . $conn->connect_error);
}


// **** LOGIN VERIFICATION ********
$loginQuery = "SELECT * from users where email = '$self'";
$loginData = $conn->query($loginQuery);
$loginrow = $loginData->fetch_assoc();
$act = $loginrow['appAccessToken'];
$selfID=$loginrow['userID'];
$selfName = $loginrow['userName'];
if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

// **** GET REMAINING TIME *************
$timeRemainingQuery = "SELECT * FROM gameInstance where gameInstanceID='$gameInstanceID'";
$timeRemainingData = $conn->query($timeRemainingQuery);
$timeRemainingrow = $timeRemainingData->fetch_assoc();
$groupID = $timeRemainingrow['groupID'];
$endDate = strtotime($timeRemainingrow['endDate']);
$startDateTime = strtotime("now");
$timeRemaining = $endDate-$startDateTime;
$gameID = $timeRemainingrow['gameID']; 
if($timeRemaining<=0)
{
        $updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='gameOver' where gameInstanceID = '$gameInstanceID'";
        $updateGameInstanceData = $conn->query($updateGameInstanceQuery);


        // **** INSERT INTO USERGAME DATA FOR ALL PLAYERS ******
		
	// *** INSERT Lost FOR SELF *****
	
	 // ******** ACTIVITY DETAIL AT THE BEGINNING OF THIS GAME ********* 
         $startStepsQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'inProgress/hotPotato'ORDER BY userGameDataID DESC LIMIT 1";
	$startStepsData = $conn->query($startStepsQuery);
        $startStepsrow = $startStepsData->fetch_assoc();
        $startSteps = $startStepsrow ['startSteps'];
        $startActiveTime = $startStepsrow['startActiveTime'];

         // *********** ACTIVITY DETAIL IN PREVIOUS ACTIVE SESSIONS OF THIS GAME**************
	$prevStepsQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'inProgress' ORDER BY userGameDataID DESC LIMIT 1";
        $prevStepsData = $conn->query($prevStepsQuery);
	if($prevStepsData->num_rows>0)
        {
                 // GET PREVIOUSLY COLLECTED STEPS
                $prevStepsrow = $prevStepsData->fetch_assoc();
                $prevSteps = $prevStepsrow['stepsCollected'];
                $prevActiveTime = $prevStepsrow['activeTimeCollected'];
                $stepsAccumulated = $prevSteps +$currentSteps - $startSteps;
                $activeTimeAccumulated = $prevActiveTime + $activeTime - $startActiveTime;
        }
        else
        {
                $stepsAccumulated = $currentSteps - $startSteps;
                $activeTimeAccumulated = $activeTime - $startActiveTime;
        }
	$userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$selfID', '$gameID', '$currDate', '$stepsAccumulated','$activeTimeAccumulated','$gameInstanceID','Lost','0','0')";
        $UserGameDataSelf = $conn->query($userGameDataSelfQuery);
	

	// *********** INSERT WON FOR OTHERS ************
	$playersQuery = "SELECT * from userGroups WHERE groupID = '$groupID'";
        $playersData = $conn->query($playersQuery);
        while($playersrow = $playersData->fetch_assoc())
        {
                $userID = $playersrow['userID'];
                $userGameStatusQuery = "SELECT * from userGameData WHERE userID = '$userID' AND gameInstanceID= '$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
                $userGameStatusData = $conn->query($userGameStatusQuery);
                $userGameStatusrow = $userGameStatusData->fetch_assoc();
                $userGameStatus = $userGameStatusrow['userGameStatus'];
                if($userGameStatus!='Quit' && $userID!= $selfID)
                {
                        // *********** ACTIVITY DETAIL IN PREVIOUS ACTIVE SESSIONS OF THIS GAME**************
                        $prevStepsQuery = "Select * from userGameData where userID= '$userID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'inProgress' ORDER BY userGameDataID DESC LIMIT 1";
                        $prevStepsData = $conn->query($prevStepsQuery);
                        if($prevStepsData->num_rows>0)
                        {
           			$prevStepsrow = $prevStepsData->fetch_assoc();
                                $stepsAccumulated = $prevStepsrow['stepsCollected'];
                                $activeTimeAccumulated = $prevStepsrow['activeTimeCollected'];
                        }
                        else
                        {
                                $stepsAccumulated=0;
                                $activeTimeAccumulated = 0;
                        }
                        $userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$userID' , '$gameID' , '$currDate' , '$stepsAccumulated' , '$activeTimeAccumulated', '$gameInstanceID' , 'Won', '0', '0')";
                        $userGameData = $conn->query($userGameDataSelfQuery);

			// SEND NOTIFICATION 
			
			$deviceIDQuery = "SELECT * FROM users where userID='$userID'";
                        $deviceIDData = $conn->query($deviceIDQuery);
                        $deviceIDrow = $deviceIDData->fetch_assoc();
                        $deviceID = $deviceIDrow['deviceRegistrationID'];
			$message = "Game Over ";
			$data = array('type' =>'gameOver', 'gameInstanceID' => $gameInstanceID);
			sendNotification($deviceID, $data, $message  );
	
                }
        }

	

}






	
		

// *** GET CURRENT FITNESS DATA FOR SELF ****

$activeUserFitBitTokenQuery = "SELECT * FROM users WHERE userID = '$selfID'";
$activeUserFitBitTokenData = $conn->query($activeUserFitBitTokenQuery);
$activeUserFitBitTokenrow = $activeUserFitBitTokenData->fetch_assoc();
$activeUserFitBitToken = $activeUserFitBitTokenrow['fitbitID'];
$activeUserAccessToken = $activeUserFitBitTokenrow['accessToken'];
$expTime = $activeUserFitBitTokenrow['tokenExpDate'];
if($currDate>$expTime)
{
	// ************* If Tokens EXPIRED ***********************";
	$RF_token = $activeUserFitBitTokenrow['refreshToken'];
	$data = array ('grant_type' => 'refresh_token','refresh_token' => $RF_token, 'expires_in' => 3700 );
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
	$activeUserAccessToken = $data_authentication['access_token'];
	$RF_token = $data_authentication['refresh_token'];
	$update = "UPDATE users SET accessToken = '$activeUserAccessToken', refreshToken = '$RF_token', tokenExpDate = '$expiryTime' where email= '$self'";
	$conn->query($update);
	
}	
$url2 = "https://api.fitbit.com/1/user/".$activeUserFitBitToken."/profile.json";
$url3 = "https://api.fitbit.com/1/user/".$activeUserFitBitToken."/activities/date/".$todaysDate.".json";

$opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$activeUserAccessToken."\r\n"
                        ));
$context = stream_context_create($opts2);
$file_contents = file_get_contents($url2,false,$context);
$data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
$data_user_summary = $data_user_activity['summary'];
if($data_user_summary['steps']){
        $activeTime = $data_user_summary['fairlyActiveMinutes']+$data_user_summary['veryActiveMinutes'];
        $currentSteps = $data_user_summary['steps'];}


$json_arr = array('steps'=>$currentSteps, 'activityTime' => $activeTime, 'timeRemaining' => $timeRemaining);
$json_data = json_encode($json_arr);
echo $json_data;
?> 
