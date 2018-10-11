<?php

/* startGame.php

input -- SELF, ACCESS TOKEN, GAMEINSTANCE, GAMEID

output --
'type' =>'gameStart',											-all
'timeRemaining' =>$randGameTime, 									-all
'currentUser' => $activeUserID or null,									-hotPotatoAsync & hotPotatoCompetitive
'currentUserName' => $activeUserName or null,								-hotPotatoAsync & hotPotatoCompetitive
stageInterval'=> activity to achieve to pass the potato or currentActiveCumulativeTime or null		-hotPotatoAsync & hotPotatoCompetitive & escapeTheTunnelSyncCollaborative
'self' => true if I'm the active owner else null							-hotPotatoAsync & hotPotatoCompetitive
currentActivity => activity at the start of the game							-all
endValue => collaborative activity to achieve to win 							-escapeTheTunnelSyncCollaborative & escapeTheTunnelSyncCompetitive


*/
$serverKey=$_GET['serverKey'];


function sendNotification($to, $data,$msg)
{
        define( 'API_ACCESS_KEY', $serverKey );
	/*
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
        );*/
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

include "appConstants.php";
$currDate = date("Y-m-d H:i:s", strtotime("now"));

$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$startTime = date("Y-m-d H:i:s", strtotime("now"));
$endTime = date("Y-m-d H:i:s", strtotime("now"));
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
        $json_arr = array('error'=>"No Such Game Found! ");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;

}

//************* get metric type for the game ********************
$gameMetricQuery ="SELECT * from games where gameID='$gameID'";
$gameMetricData = $conn->query($gameMetricQuery);
$gameMetricRow = $gameMetricData->fetch_assoc();
$gameMetric = $gameMetricRow['metric'];
$gameName = $gameMetricRow['gameName'];

//  ***** GET GAME STATUS BEFORE PLAYING ***********
$groupIDQuery = "SELECT * from gameInstance where gameInstanceID= '$gameInstanceID'";
$groupIDData =$conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameStatus = $groupIDrow['gameStatus'];
if($gameStatus == 'gameReadyToPlay')
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
else if($gameStatus == 'gameQuit')
{
        $json_arr = array('error'=>"This game has been Quit");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}
else if($gameStatus == 'gameInProgress')
{
        $json_arr = array('error'=>"Game already Started");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}
else {
// ****** GAME REPLAY *************

}

$activeUserID=0;
$currentActiveTimeCumulative=0;
$currentActiveStepsCumulative=0;
$activeUserName ="";
$currentSteps=0;
$activeMinutes=0;
$stageInterval=0;
$endValue=0;
$msg="";


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
        if($userGameStatus!='userQuit')
        {
                $numberOfPlayers=$numberOfPlayers+1;
                array_push($playerIDList, $userID);
        }
}


// ********************* Duration of Game *******************
switch($gameName)
{
	case 'hotPotatoAsync':
	case 'hotPotatoCompetitive':
		$lowerLimit = 0.5*$numberOfPlayers*3600;
		$upperLimit = $numberOfPlayers*3600;
		$randGameTime =  rand($lowerLimit, $upperLimit);	
		$duration = "+".$randGameTime." seconds";
		$endTime = date("Y-m-d H:i:s", strtotime($duration));
		break;
	case 'escapeTheTunnelSyncCompetitive':
	case 'escapeTheTunnelSyncCollaborative':
		$randGameTime =  1500;   //25 minutes
		$startTime = date("Y-m-d H:i:s", strtotime("now"));
		$duration = "+".$randGameTime." seconds";
		$endTime = date("Y-m-d H:i:s", strtotime($duration));
		break;
	default:
		$json_arr = array('error' =>"No such gameID found");
		$json_data = json_encode($json_arr);
        	echo $json_data;
        	return;	

}


// ************** MESSAGES IN NOTIFICATION *****************

switch($gameName)
{
	case 'hotPotatoAsync':
	case 'hotPotatoCompetitive':
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
// TODO

}

// ************** FIND ACTIVE USER ******************************

switch($gameName)
{
        case 'hotPotatoAsync':
        case 'hotPotatoCompetitive':
  		$activeUserID = rand(1,$numberOfPlayers);
		$activeUserID = $playerIDList[$activeUserID-1];
		$activeUserNameQuery = "SELECT * from users where userID ='$activeUserID'";
		$activeUserNameQueryData = $conn->query($activeUserNameQuery);
		$activeUserNamerow = $activeUserNameQueryData->fetch_assoc();
		$activeUserName = $activeUserNamerow['userName'];
		break;
	default:
		$activeUserName = 'null';
}


// ********************* STAGE INTERVAL  & END VALUE **********************

switch($gameName)
{
        case 'hotPotatoAsync':
        case 'hotPotatoCompetitive':
                $stageInterval=0;
                $endValue='0';
                $stepsToPassThePotato=0;
                $activityTimeToPassThePotato =0;
                include 'appConstants.php';
                if($gameMetric=='steps')
                {
                        $stageInterval = $stepsToPassThePotato;
                }
                else
                {
                        $stageInterval = $activityTimeToPassThePotato;
                }
                break;
        case 'escapeTheTunnelSyncCollaborative':
                
		for($x=0;$x<$numberOfPlayers;$x=$x+1)
                {
			$pID = $playerIDList[$x];
			$emailQuery = "SELECT * from users where userID = '$pID'";
			$emailQueryData = $conn->query($emailQuery);
			$emailQueryRow = $emailQueryData->fetch_assoc();
			$email = $emailQueryRow['email'];
			$acToken = $emailQueryRow['appAccessToken'];
			$currentSteps=0;
			$activeMinutes=0;
			include "fetchFitBitData.php";
			$currentSteps = $steps;
			$activeMinutes = $veryActiveMinutes + $fairlyActiveMinutes;
			$currentActiveTimeCumulative = $currentActiveTimeCumulative + $activeMinutes;
			$currentActiveStepsCumulative = $currentActiveStepsCumulative + $currentSteps;
		}
		if($gameMetric=='steps')
		{
			$stageInterval = $currentActiveStepsCumulative;
		}
		else
		{
			$stageInterval = $currentActiveTimeCumulative;
		}
                $endValue = 0;
                include 'appConstants.php';
                if($gameMetric=='steps')
                {
                        $endValue = $cumulativeStepsToGet * $numberOfPlayers;
                }
                else
                {
                        $endValue = $numberOfPlayers * $cumulativeActiveTimeToGet;
                }
                break;
        case 'escapeTheTunnelSyncCompetitive':
                $endValue ='0';
                $stageInterval = '0';
}


// ********************** Insert Activity Data into userGameData and set userGameStatus **************************
switch($gameName)
{
        case 'hotPotatoAsync':
	case 'hotPotatoCompetitive':
		// ****************** get Fitness data only for current active user ***************		
		for($x=0;$x<$numberOfPlayers;$x=$x+1)
		{
			$pID = $playerIDList[$x];
			$getDeviceIDQuery = "SELECT * FROM users WHERE userID = '$pID'";
			$getDeviceIDData = $conn->query($getDeviceIDQuery);
			$getDeviceIDrow = $getDeviceIDData->fetch_assoc();
			$deviceID = $getDeviceIDrow['deviceRegistrationID'];	
			if($pID==$activeUserID)
			{
				$emailQuery = "SELECT * from users where userID = '$activeUserID'";
				$emailQueryData = $conn->query($emailQuery);
				$emailQueryRow = $emailQueryData->fetch_assoc();
				$email = $emailQueryRow['email'];
				$acToken = $emailQueryRow['appAccessToken'];
				$currentSteps=0;
				$activeMinutes=0;
				include "fetchFitBitData.php";
				$currentSteps = $steps;
				$activeMinutes = $veryActiveMinutes + $fairlyActiveMinutes;
				$insertUserGamedataActiveUserQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$activeUserID', '$gameID', '$startTime', '0','0', '$currentSteps' , '$activeMinutes' , '$gameInstanceID','userInProgress/HotPotato')";
				$insertUserGamedataActiveUserQueryData = $conn->query($insertUserGamedataActiveUserQuery);

				if( $pID!=$selfID)
				{ 
				// ********* SEND NOTIFICATION TO ACTIVE USER ********
					if($gameMetric=='steps')
					{
						$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID,'currentUserName' => $activeUserName, 'stageInterval'=>$stageInterval, 'startTime'=>$startTime, 'self' => true, currentSteps => $currentSteps);
					}
					else
					{
						$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID,'currentUserName' => $activeUserName, 'stageInterval'=>$stageInterval, 'startTime'=>$startTime, 'self' => true, currentActivityTime => $activeMinutes);
					}
					sendNotification($deviceID, $data);
				}
				else
				{
				// ************ COMPOSE RESPONSE FOR SELF ******************
					if($gameMetric=='steps')
					{
						$json_arr = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>$stageInterval, 'startTime'=>$startTime, 'self' => true, 'currentSteps' => $currentSteps);
					}
					else
					{
						$json_arr = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID,'currentUserName' => $activeUserName, 'stageInterval'=>$stageInterval, 'startTime'=>$startTime, 'self' => true, currentActivityTime => $activeMinutes);
					}
					$json_data = json_encode($json_arr);
        				echo $json_data;
				}

			}
			else
			{

				 $insertUserGamedataQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$activeUserID', '$gameID', '$startTime', '0','0', '0' , '0' , '$gameInstanceID','userInProgress')";
				$insertUserGamedataQueryData = $conn->query($insertUserGamedataQuery);
				if( $pID!=$selfID)
                                { 
					// ********* SEND NOTIFICATION TO  ALL OTHER USERS **************
					if($gameMetric=='steps')
					{
						$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID,'currentUserName' => $activeUserName, 'stageInterval'=>'0', 'startTime'=>$startTime, 'self' => false, currentSteps => $currentSteps);
					}
					else
					{
						$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID,'currentUserName' => $activeUserName, 'stageInterval'=>'0', 'startTime'=>$startTime, 'self' => false, currentActivityTime => $activeMinutes);
					}
					sendNotification($deviceID, $data);
				}
				else
				{
					// ************ COMPOSE RESPONSE FOR SELF ******************
					if($gameMetric=='steps')
					{
						$json_arr = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>'0','startTime'=>$startTime, 'self' => false, currentSteps => $currentSteps);
					}
					else
					{
						$json_arr = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>'0','startTime'=>$startTime, 'self' => false, currentActivityTime => $activeMinutes);
					}
					$json_data = json_encode($json_arr);
                                        echo $json_data;
				}
			}
		}
		break;
	case 'escapeTheTunnelSyncCompetitive':
	case 'escapeTheTunnelSyncCollaborative':
		// ****************** get Fitness data for all users **********************
		for($x=0;$x<$numberOfPlayers;$x=$x+1)
                {
                        $pID = $playerIDList[$x];
			$emailQuery = "SELECT * from users where userID = '$pID'";
			$emailQueryData = $conn->query($emailQuery);
			$emailQueryRow = $emailQueryData->fetch_assoc();
			$email = $emailQueryRow['email'];
			$acToken = $emailQueryRow['appAccessToken'];
			$currentSteps=0;
			$activeMinutes=0;
			include "fetchFitBitData.php";
			$currentSteps = $steps;
			$activeMinutes = $veryActiveMinutes + $fairlyActiveMinutes;
			$currentActiveTimeCumulative = $currentActiveTimeCumulative + $activeMinutes;
			$insertUserGamedataActiveUserQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$pID', '$gameID', '$startTime', '0','0', '$currentSteps' , '$activeMinutes' , '$gameInstanceID','userInProgress')";
			$conn->query($insertUserGamedataActiveUserQuery);
			if($pID==$selfID)
			{
				// ************ COMPOSE RESPONSE FOR SELF ******************
                                if($gameMetric=='steps')
                                {
					$json_arr = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>$stageInterval,'startTime'=>$startTime, 'self' => false, currentSteps => $currentSteps);
				}
				else
				{
					$json_arr = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>$stageInterval,'startTime'=>$startTime, 'self' => false, currentActivityTime => $activeMinutes);
				}
				$json_data = json_encode($json_arr);
                                echo $json_data;
			}
			else
			{
				// ********** SEND NOTIFICATION TO OTHERS ****************
				if($gameMetric=='steps')
				{
					$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>$stageInterval,'startTime'=>$startTime, 'self' => false, currentSteps => $currentSteps);
				}
				else
				{
					$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime,'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>$stageInterval,'startTime'=>$startTime, 'self' => false, currentActivityTime => $activeMinutes);
				}
				sendNotification($deviceID, $data);
				
			}
		}	
		break;

		
}






// **************** UPDATE gameInstance **************************

$updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='gameInProgress', createDate='$startTime',endDate='$endTime', stageInterval= '$stageInterval',activeUser='$activeUserID', endValue ='$endValue' where gameInstanceID= '$gameInstanceID'";
$updateGameInstanceData = $conn->query($updateGameInstanceQuery);



?>	
