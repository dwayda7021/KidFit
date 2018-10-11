<?php

/* startGameHotPotato.php 
INPUT	-- SELF, ACCESS TOKEN, GAMEINSTANCE, GAMEID
OUTPUT -- ('type' =>'gameStart','timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID,'currentUserName' => $activeUserName, 'stageInterval'=>2000, 'self' => true, currentSteps => 0, 'startSteps'=> steps at that time);



*/

function sendNotification($to, $data)
{
	$serverKey = 'AAAAXqJwzGY:APA91bEE--VjeC0tLQr21uoHnMjDIx8Cr6nASML1t89r9BIB5RAjanlmXVb8Waglk7IqkiU3s0e7b_GC_eVoBuK9jsLegCegdMaIphzzfP_wazBs0tCaKDyEDYjPwTjtH-BkQotQ1uScfIu1PnM_hZzVGdLD5jptwg';
	define( 'API_ACCESS_KEY', $serverKey );
	
	$msg = array
	(
        	'message'       => 'Game Started',
        	'title'         => 'Hot Potato',
        	'subtitle'      => 'Hot Potato Game Started',
        	'tickerText'    => 'Ticker text here...Ticker text here...Ticker text here',
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
$gameID = $_GET['gameID'];

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

if(!$gameInstanceID)
{
        $json_arr = array('error'=>"No Such Game Found! Please refresh");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;

}

//  ***** GET GAME STATUS BEFORE PLAYING ***********
$groupIDQuery = "SELECT * from gameInstance where gameInstanceID= '$gameInstanceID'";
$groupIDData =$conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameStatus = $groupIDrow['gameStatus'];
$gameID = $groupIDrow['gameID'];
if($gameStatus == 'ReadyToPlay')
{
	// ********** START GAME **********
	
}
else if($gameStatus == 'gameInvited')
{
	$json_arr = array('error'=>"Waiting for other players");
	$json_data = json_encode($json_arr);
        echo $json_data;
        return;
}
else if($gameStatus == 'Quit')
{
	$json_arr = array('error'=>"This game has been Quit");
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}
else if($gameStatus == 'inProgress')
{
	$json_arr = array('error'=>"Game already Started");
	$json_data = json_encode($json_arr);
        echo $json_data;
        return;
}
else {
// ****** GAME REPLAY *************

}



//  **** GET NUMBER OF PLAYERS (NOT QUIT STATUS)  **********
$playerIDList=array();
$numberOfPlayers=0;
$playersQuery = "SELECT * from userGroups WHERE groupID = '$groupID'";
$playersData = $conn->query($playersQuery);

while($playersrow = $playersData->fetch_assoc())
{
	$userID = $playersrow['userID'];
        $userGameStatusQuery = "SELECT * from userGameData WHERE userID = '$userID' AND gameInstanceID= '$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
        $userGameStatusData = $conn->query($userGameStatusQuery);
        $userGameStatusrow = $userGameStatusData->fetch_assoc();
        $userGameStatus = $userGameStatusrow['userGameStatus'];
        if($userGameStatus!='Quit')
	{
		$numberOfPlayers=$numberOfPlayers+1;
		array_push($playerIDList, $userID);
	}
}

$lowerLimit = 0.5*$numberOfPlayers*3600;
$upperLimit = $numberOfPlayers*3600;
$randGameTime =  rand($lowerLimit, $upperLimit);

$activeUserID = rand(1,$numberOfPlayers);
$activeUserID = $playerIDList[$activeUserID-1];



// *** GET CURRENT FITNESS DATA FOR ACTIVE USER ****

$activeUserFitBitTokenQuery = "SELECT * FROM users WHERE userID = '$activeUserID'";
$activeUserFitBitTokenData = $conn->query($activeUserFitBitTokenQuery);
$activeUserFitBitTokenrow = $activeUserFitBitTokenData->fetch_assoc();
$activeUserFitBitToken = $activeUserFitBitTokenrow['fitbitID'];
$activeUserAccessToken = $activeUserFitBitTokenrow['accessToken'];
$activeUserName = $activeUserFitBitTokenrow['userName'];

$url = "https://api.fitbit.com/1/user/".$activeUserFitBitToken."/activities/date/".$todaysDate.".json";
$opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$activeUserAccessToken."\r\n"
                        ));
$context = stream_context_create($opts2);
$data_user_activity = json_decode(file_get_contents($url, false, $context), true);
$data_user_summary = $data_user_activity['summary'];
if($data_user_summary['steps']){
	$activeTime = $data_user_summary['fairlyActiveMinutes']+$data_user_summary['veryActiveMinutes'];
	$currentSteps = $data_user_summary['steps'];}
else
	{$currentSteps=0;$activeTime = 0;}


$stageInterval = 2000; //steps


// ******************** UPDATE GAME INSTANCE **********
	// ** CHANGE GAME STATUS**
	// ** CHANGE CREATE TIME **
	// ** CHANGE END TIME **
	// ** CHANGE STAGE INTERVAL ** 
	// ** CHANGE ACTIVE USER **
//$randGameTime = 120;
$startTime = date("Y-m-d H:i:s", strtotime("now"));
$duration = "+".$randGameTime." seconds";
$endTime = date("Y-m-d H:i:s", strtotime($duration));

$updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='inProgress', createDate='$startTime', endDate='$endTime', stageInterval= '$stageInterval', activeUser='$activeUserID' where gameInstanceID= '$gameInstanceID'";
$updateGameInstanceData = $conn->query($updateGameInstanceQuery);

//    ************* INSERT INTO USERGAMEDATA FOR ALL USERS **********

 	// ************* INSERT INTO USERGAMEDATA FOR CURRENT ACTIVE USER **********

$stepsCollectedQuery = "SELECT * from userGameData where userID = '$activeUserID' AND gameInstanceID= '$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
$stepsCollectedData = $conn->query($stepsCollectedQuery);
$stepsCollectedrow = $stepsCollectedData->fetch_assoc();
$stepsCollected = $stepsCollectedrow['stepsCollected'];
$activeTimeCollected = $stepsCollectedrow['activeTimeCollected'];

$InsertCurrentActiveUserQuery =	"INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$activeUserID','$gameID', '$startTime', '0','0','$currentSteps', '$activeTime', '$gameInstanceID', 'inProgress/hotPotato')"; 
$updateCurrentActiveUserQuery = "UPDATE userGameData SET createDate='$startTime',stepsCollected='$currentSteps', activeTimeCollected='$activeTime',userGameStatus='inProgress' where userID='$activeUserID' AND gameInstanceID= '$gameInstanceID'";
$conn->query($InsertCurrentActiveUserQuery);

			// ************* INSERT INTO USERGAMEDATA FOR OTHER USERS
for($x=0;$x<$numberOfPlayers;$x=$x+1)
{
	$pID = $playerIDList[$x];
	if(!($pID==$activeUserID))
	{
		// *********GET ACTIVITY DETAIL FOR USER ************
		$loginQuery = "SELECT * from users WHERE userID='$pID'";
		$loginData = $conn->query($loginQuery);
		$loginrow = $loginData->fetch_assoc();
		$userAccessToken = $loginrow['accessToken'];
		$userFitbitToken = $loginrow['fitbitID'];
		$opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$userAccessToken."\r\n"
                        ));
        $url3 = "https://api.fitbit.com/1/user/".$userFitbitToken."/activities/date/".$todaysDate.".json";
        $context = stream_context_create($opts2);
        $data_user_activity = json_decode(file_get_contents($url3, false, $context), true);
        $data_user_summary = $data_user_activity['summary'];
	$userSteps = $data_user_summary['steps'] ;
	$userActiveTime = $data_user_summary['veryActiveMinutes'] +$data_user_summary['fairlyActiveMinutes'];
		





		$InsertCurrentActiveUserQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$pID','$gameID', '$startTime', '0', '0','$userSteps','$userActiveTime', '$gameInstanceID', 'inProgress')";
		$conn->query($InsertCurrentActiveUserQuery);               
	}
}


$updateUserGameStatusQuery = "UPDATE userGameData SET userGameStatus='inProgress' WHERE gameInstanceID ='$gameInstanceID' AND userGameStatus='ReadyToPlay'";


if($selfID == $activeUserID)
{
	// ** COMPOSE RESPONSE **
	$json_arr = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>2000, 'startTime'=>$startTime, 'self' => true, 'currentSteps' => $currentSteps);
	$json_data = json_encode($json_arr);
	echo $json_data; 

	// ******** SEND NOTIFICATION TO OTHERS
	for($x=0;$x<$numberOfPlayers;$x=$x+1)
	{
		$pID = $playerIDList[$x];
		if(($pID != $selfID)) 
		{
			$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>0, 'startTime'=>$startTime, 'self' => false);
			$getDeviceIDQuery = "SELECT * FROM users WHERE userID = '$pID'";
			$getDeviceIDData = $conn->query($getDeviceIDQuery);
			$getDeviceIDrow = $getDeviceIDData->fetch_assoc();
			$deviceID = $getDeviceIDrow['deviceRegistrationID'];
			sendNotification($deviceID, $data);
					
		}
	}
}
else
{
	//  ******** COMPOSE NOTIFICATION  for CURRENT ACTIVE USER  **
	$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID,'currentUserName' => $activeUserName, 'stageInterval'=>2000, 'startTime'=>$startTime, 'self' => true, currentSteps => $currentSteps);
	
	// ** GET deviceRegistrationID **
	$getDeviceIDQuery = "SELECT * FROM users WHERE userID = '$activeUserID'";
	$getDeviceIDData = $conn->query($getDeviceIDQuery);
	$getDeviceIDrow = $getDeviceIDData->fetch_assoc();
	$deviceID = $getDeviceIDrow['deviceRegistrationID'];
	sendNotification($deviceID, $data);

	// ************COMPOSE RESPONSE TO SELF
	$json_arr = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID, 'stageInterval'=>0,'startTime'=>$startTime,'currentUserName' => $activeUserName, 'self' => false);
        $json_data = json_encode($json_arr);
        echo $json_data;
	// ******** SEND NOTIFICATION TO OTHERS
        for($x=0;$x<$numberOfPlayers;$x=$x+1)
        {
                $pID = $playerIDList[$x];
                if(!($pID== $selfID)) 
                {
                        if(!($pID==$activeUserID))
                        {
                                $data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID, 'stageInterval'=>0, 'startTime'=>$startTime, 'self' => false,'currentUserName' => $activeUserName);
                                $getDeviceIDQuery = "SELECT * FROM users WHERE userID = '$pID'";
                                $getDeviceIDData = $conn->query($getDeviceIDQuery);
                                $getDeviceIDrow = $getDeviceIDData->fetch_assoc();
                                $deviceID = $getDeviceIDrow['deviceRegistrationID'];
                                sendNotification($deviceID, $data);

                        }
                }
        }
}
?>
