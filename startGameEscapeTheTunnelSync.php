<?php

/* startGameEscapeTheTunnelSync.php

INPUT   -- SELF, ACCESS TOKEN, GAMEINSTANCE, GAMEID

OUTPUT -- ('type' =>'gameStart','timeRemaining' =>$randGameTime,'stageInterval'=>currentActiveCumulativeTime, 'endValue'=> finalActiveCumulativeTime);

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
$currentActiveTimeCumulative=0.0;
$startSteps = 0;
$currentActiveTime=0;
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
		// ********** GET CURRENT Fitness DATA FOR ALL USERS  & INSERT THEM INTO USERGAMEDATA and calculate  currentActiveTimeCumulative ***************
		

		$userFitBitTokenQuery = "SELECT * FROM users WHERE userID = '$userID'";
		$userFitBitTokenData = $conn->query($userFitBitTokenQuery);
		$userFitBitTokenrow = $userFitBitTokenData->fetch_assoc();
		$userFitBitToken = $userFitBitTokenrow['fitbitID'];			
		$userFitBitAccessToken = $userFitBitTokenrow['accessToken'];
		$userFitBitName = $userFitBitTokenrow['userName'];
		$url = "https://api.fitbit.com/1/user/".$userFitBitToken."/activities/date/".$todaysDate.".json";
			$opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$userFitBitAccessToken."\r\n"
                        ));
		$context = stream_context_create($opts2);
		$data_user_activity = json_decode(file_get_contents($url, false, $context), true);
		$data_user_summary = $data_user_activity['summary'];
		if($data_user_summary['steps']){
			 $startSteps = $data_user_summary['steps'];
			 $currentActiveTime = $data_user_summary['fairlyActiveMinutes']+$data_user_summary['veryActiveMinutes'];
		}
		else
		{
			//echo "Fitness data not fetched for user ", $userFitBitName,"<br/>";
			$startSteps=0;
			$currentActiveTime=0;
		}
		$currentActiveTimeCumulative = $currentActiveTimeCumulative + $currentActiveTime;
		
		// ******** INSERT INTO USERGAMEDATA ******
		$InsertUserCurrentActivityQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$userID','$gameID', '$currDate', '0','0','$startSteps', '$currentActiveTime', '$gameInstanceID', 'inProgress')";
		$conn->query($InsertUserCurrentActivityQuery);

		
        }
	

}
$GameTime =  1500;
$activityTimeToGet = $numberOfPlayers*18;
$startTime = date("Y-m-d H:i:s", strtotime("now"));
$duration = "+".$GameTime." seconds";
$endTime = date("Y-m-d H:i:s", strtotime($duration));

// ********* UPDATE GAME INSTANCE TO IN PROGRESS ***********


$updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='inProgress', createDate='$startTime', endDate='$endTime', stageInterval= '$currentActiveTimeCumulative',endVaue ='$activityTimeToGet' where gameInstanceID= '$gameInstanceID'";
$updateGameInstanceData = $conn->query($updateGameInstanceQuery);



// COMPOSE RESPONSE

$json_arr = array('type' =>'gameStart','gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$GameTime,'currentActivityTime'=>$currentActiveTimeCumulative, 'endValue'=> $activityTimeToGet);
$json_data = json_encode($json_arr);
echo $json_data;


// ******** SEND NOTIFICATION TO OTHERS
for($x=0;$x<$numberOfPlayers;$x=$x+1)
{
	$pID = $playerIDList[$x];
        if(($pID != $selfID))
        {
		$data = array('type' =>'gameStart','gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$GameTime,'currentActivityTime'=>$currentActiveTimeCumulative, 'endValue'=> $activityTimeToGet);
                $getDeviceIDQuery = "SELECT * FROM users WHERE userID = '$pID'";
                $getDeviceIDData = $conn->query($getDeviceIDQuery);
                $getDeviceIDrow = $getDeviceIDData->fetch_assoc();
                $deviceID = $getDeviceIDrow['deviceRegistrationID'];
                sendNotification($deviceID, $data);

        }
}
	


?>













