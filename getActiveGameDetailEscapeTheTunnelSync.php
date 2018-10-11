<?php
/* getActiveGameDetailEscapeTheTunnelSync.php
INPUT -- EMAIL, ACCESS TOKEN, GAME INSTANCE ID AND GAME ID
OUTPUT -- {"timeRemaining":-154167,startActiveMinutes, CurrenACtiveMinutes & EndValue to achieve
        $data = array('gameStatus'=>'Game Over', 'activeMinutesAccumulated'=>$currentActiveTimeCumulative, 'playerStatus'=>$game); if game is over
*/
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
$selfAccessToken = $loginrow['accessToken'];
$selfFitBitID = $loginrow['fitbitID'];

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


//  ***** GET GAME STATUS  ***********
$groupIDQuery = "SELECT * from gameInstance where gameInstanceID= '$gameInstanceID'";
$groupIDData =$conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameID = $groupIDrow['gameID'];
$gameStatus = $groupIDrow['gameStatus'];
$endDate = strtotime($groupIDrow['endDate']);
$startDateTime = strtotime("now");
$timeRemaining = $endDate-$startDateTime;
$endValue =  $groupIDrow['endVaue'];
$playerIDList=array();
$game;

if($timeRemaining<=0 && $gameStatus!='Over')
{
	// ************ CALCULATE ACTIVE TIME COLLECTED BY EACH USER *******
	
	// *********** CALCULATE ACTIVE MINUTES AT THIS POINT FOR EACH USER ************
	
	//  **** GET NUMBER OF PLAYERS (NOT QUIT STATUS)  **********
	$numberOfPlayers=0;
	$playersQuery = "SELECT * from userGroups WHERE groupID = '$groupID'";
	$playersData = $conn->query($playersQuery);
	$currentActiveTimeCumulative=0.0;
	$prevActiveTimeCumulative=0.0;
	$currentSteps = 0;
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
			// ******* GET ACTIVITY DETAILS AT The START OF The GAME
			$startactivityQuery = "SELECT * from userGameData where userID = '$userID' AND gameInstanceID= '$gameInstanceID' AND userGameStatus='inProgress' ORDER BY userGameDataID DESC LIMIT 1";
			$startactivityData = $conn->query($startactivityQuery);
			$startactivityrow = $startactivityData->fetch_assoc();
			$startSteps = $startactivityrow['startSteps'];
			$startActiveTime = $startactivityrow['startActiveTime'];
			$prevActiveTimeCumulative = $prevActiveTimeCumulative+$startActiveTime;		
			// ********** GET ACTIVITY DETAILS NOW ******************
			$userFitBitTokenQuery = "SELECT * FROM users WHERE userID = '$userID'";
                	$userFitBitTokenData = $conn->query($userFitBitTokenQuery);
			$userFitBitTokenrow = $userFitBitTokenData->fetch_assoc();
			$userFitBitToken = $userFitBitTokenrow['fitbitID'];
			$userFitBitAccessToken = $userFitBitTokenrow['accessToken'];
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
				$currentSteps = $data_user_summary['steps'];
				$currentActiveTime = $data_user_summary['fairlyActiveMinutes']+$data_user_summary['veryActiveMinutes'];
			}
			else
			{
				$currentSteps=0;
				$currentActiveTime=0;
			}
			$currentActiveTimeCumulative = $currentActiveTimeCumulative + $currentActiveTime;
	
			// ********* CALCULATE activity gained by each abd cumulative activity INSERT into usergamedata and update GameInstance ******
			$stepsCollected = $currentSteps - $startSteps;
			$activeTimeCollected = $currentActiveTime - $startActiveTime;
			// ******** INSERT INTO USERGAMEDATA ******
                $InsertUserCurrentActivityQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$userID','$gameID', '$currDate', '$stepsCollected','$activeTimeCollected','$startSteps', '$startActiveTime', '$gameInstanceID', 'over')";
                $conn->query($InsertUserCurrentActivityQuery);			
		}


	}// end of while loop

	// ******** UPDATE  INTO USERGAMEDATA ******
	if(($currentActiveTimeCumulative - $prevActiveTimeCumulative)>$endValue)
	{
		$game='won';
	}
	else
	{
		$game='lost';
	}


	for($x=0;$x<$numberOfPlayers;$x=$x+1)
	{
		$userID  = $playerIDList[$x];		
		$updatePlayerStatusQuery = " UPDATE userGameData SET userGameStatus='$game' where gameInstanceID= '$gameInstanceID' AND userID='$userID' AND userGameStatus='over' ORDER BY  userGameDataID DESC LIMIT 1";
		$updatePlayerStatusData = $conn->query($updatePlayerStatusQuery);
	}

	// *********** UPDATE GAME INSTANCE ************
		
	$updategameInstanceQuery = "UPDATE gameInstance SET gameStatus='Over' wherewhere gameInstanceID= '$gameInstanceID'";
	$updategameInstanceData =  $conn->query($updategameInstanceQuery);

	// COMPOSE RESPONSE
	$data = array('gameStatus'=>'GameOver', 'activeMinutesAccumulated'=>$currentActiveTimeCumulative- $prevActiveTimeCumulative, 'playerStatus'=>$game);
	$json_data = json_encode($data);
	echo $json_data;

}
else if($timeRemaining>0)
{
// ****** Time remaining greater than 0 ************
	//  **** GET NUMBER OF PLAYERS (NOT QUIT STATUS)  **********
        $numberOfPlayers=0;
        $playersQuery = "SELECT * from userGroups WHERE groupID = '$groupID'";
        $playersData = $conn->query($playersQuery);
        $currentActiveTimeCumulative=0.0;
	$prevActiveTimeCumulative=0.0;
        $currentSteps = 0;
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


			// ******* GET ACTIVITY DETAILS AT The START OF The GAME
                        $startactivityQuery = "SELECT * from userGameData where userID = '$userID' AND gameInstanceID= '$gameInstanceID' AND userGameStatus='inProgress' ORDER BY userGameDataID DESC LIMIT 1";
                        $startactivityData = $conn->query($startactivityQuery);
                        $startactivityrow = $startactivityData->fetch_assoc();
                        $startSteps = $startactivityrow['startSteps'];
                        $startActiveTime = $startactivityrow['startActiveTime'];
			$prevActiveTimeCumulative = $prevActiveTimeCumulative +$startActiveTime;

			// ********** GET ACTIVITY DETAILS NOW ******************
                        $userFitBitTokenQuery = "SELECT * FROM users WHERE userID = '$userID'";
                        $userFitBitTokenData = $conn->query($userFitBitTokenQuery);
                        $userFitBitTokenrow = $userFitBitTokenData->fetch_assoc();
                        $userFitBitToken = $userFitBitTokenrow['fitbitID'];
                        $userFitBitAccessToken = $userFitBitTokenrow['accessToken'];
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
                                $currentSteps = $data_user_summary['steps'];
                                $currentActiveTime = $data_user_summary['fairlyActiveMinutes']+$data_user_summary['veryActiveMinutes'];
                        }
                        else
                        {
                                $currentSteps=0;
                                $currentActiveTime=0;
                        }
                        $currentActiveTimeCumulative = $currentActiveTimeCumulative + $currentActiveTime;
		}
	}// end of while lop
// ****** COMPOSE RESPONSE

        $data = array('gameStatus'=>'inProgress', 'activeMinutesAccumulated'=>$currentActiveTimeCumulative - $prevActiveTimeCumulative, 'startActiveMinutes' =>$prevActiveTimeCumulative, 'endValue'=>$endValue,'timeRemaining'=>$timeRemaining);
	$json_data = json_encode($data);
echo $json_data;
	
}
else if($timeRemaining<0)
{
	// GET GAME WIN OR LOSE STATUS
	$gameStatusQuery = "SELECT * from userGameData where gameInstanceID= '$gameInstanceID' AND userID='$self' ORDER BY userGameDataID DESC LIMIT 1";
	$gameStatusData = $conn->query($gameStatusQuery);
	$gameStatusrow = $gameStatusData->fetch_assoc();
	$gameStatus = $gameStatusrow['userGameStatus'];
	$data = array('gameStatus'=>'GameOver', 'playerStatus'=>$gameStatus);
	$json_data = json_encode($data);
	echo $json_data;


}






?>
