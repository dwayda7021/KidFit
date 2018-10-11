<?php
/*  getActiveGameDetail.php
 INPUT -- EMAIL, ACCESS TOKEN, GAME INSTANCE ID AND GAME ID
OUTPUT -- {"timeRemaining":-154167,"currentUser":"1","stageInterval":2000,"self":true,"currentSteps":6869,"StartSteps":0000} AND ACTIVE USER NAME  */

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
$activeUserID = $groupIDrow['activeUser'];
$endDate = strtotime($groupIDrow['endDate']);
$startDateTime = strtotime("now");
$timeRemaining = $endDate-$startDateTime;


if(!($gameStatus == 'inProgress'))
{
        $json_arr = array('error'=> "Not an Active Game");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


if($timeRemaining<=0 && !$gameStatus=='gameOver')
{
    if($activeUserID>0)
    {
	
	
	// ********** SET THE ACTIVE PLAYER IS LOSER, OTHERS AS WINNERS ***************

		// ******** GET CURRENT FITNESS DATA OF ACTIVE USER ******
	$userTokenQuery = "SELECT * FROM users where userID = '$activeUserID'";
	$userTokenData =$conn->query($userTokenQuery);
	$userTokenrow = $userTokenData->fetch_assoc();
	$userAccessToken = $userTokenrow['accessToken'];
	$userFitbitID = $userTokenrow['fitbitID'];
	$url = "https://api.fitbit.com/1/user/".$userFitbitID."/activities/date/".$todaysDate.".json";
	$opts2 = array(
            'http'=>array(
            'method'=>"GET",
            'header'=>"Authorization: Bearer ".$userAccessToken."\r\n"
            ));
	$context = stream_context_create($opts2);
	$data_user_activity = json_decode(file_get_contents($url, false, $context), true);
	$data_user_summary = $data_user_activity['summary'];
	if($data_user_summary['steps'])
	{
        	$currentSteps = $data_user_summary['steps'];
        	$activeTime = $data_user_summary['fairlyActiveMinutes']+$data_user_summary['veryActiveMinutes'];
	}
	else
	{
		$currentSteps=0;
		$activeTime=0;
	}
	
	// ******** ACTIVITY DETAIL AT THE BEGINNING OF THIS GAME *********	
	$startStepsQuery = "Select * from userGameData where userID= '$activeUserID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'inProgress/hotPotato'ORDER BY userGameDataID DESC LIMIT 1";
	$startStepsData = $conn->query($startStepsQuery);
	$startStepsrow = $startStepsData->fetch_assoc();
	$startSteps = $startStepsrow['startSteps'];
	$startActiveTime = $startStepsrow['startActiveTime'];

	// *********** ACTIVITY DETAIL IN PREVIOUS ACTIVE SESSIONS OF THIS GAME**************
	$prevStepsQuery = "Select * from userGameData where userID= '$activeUserID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'inProgress' ORDER BY userGameDataID DESC LIMIT 1";
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
	
	 $userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$activeUserID' , '$gameID' , '$currDate' , '$stepsAccumulated' , '$activeTimeAccumulated', '$gameInstanceID' , 'Lost', '0', '0')";
	$userGameData = $conn->query($userGameDataSelfQuery);

	// ********** SET OTHERS AS WINNERS ***************

	$playersQuery = "SELECT * from userGroups WHERE groupID = '$groupID'";
	$playersData = $conn->query($playersQuery);
	while($playersrow = $playersData->fetch_assoc())
	{
		$userID = $playersrow['userID'];
		$userGameStatusQuery = "SELECT * from userGameData WHERE userID = '$userID' AND gameInstanceID= '$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
		$userGameStatusData = $conn->query($userGameStatusQuery);
		$userGameStatusrow = $userGameStatusData->fetch_assoc();
		$userGameStatus = $userGameStatusrow['userGameStatus'];
		if($userGameStatus!='Quit' && $userID!=$activeUserID)
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

		}
	}

   }


	// ********* UPDATE GAME STATUS TO OVER ************
	
	$updategamestatus = "UPDATE gameInstance SET gameStatus = 'gameOver' where gameInstanceID = '$gameInstanceID'";
	$updategamedata = $conn->query($updategamestatus);

	$json_arr = array('error'=> "Game Over");
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}

if(!($gameStatus == 'inProgress'))
{
        $json_arr = array('error'=> "Not an Active Game");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


// ****** ACTIVE USER NAME *************
$activeUsernameQuery = "SELECT * FROM users WHERE userID='$activeUserID'";
$activeUsernameData = $conn->query($activeUsernameQuery);
$activeUsernamerow = $activeUsernameData->fetch_assoc();
$activeUserName = $activeUsernamerow['userName'];
$activeUserFitBitToken = $activeUsernamerow['fitbitID'];
$activeUserAccessToken = $activeUsernamerow['accessToken'];

 
// *** IF I AM THE ACTIVE USER *******
if($selfID == $activeUserID)
{
//OUTPUT -- {"timeRemaining":-154167,"currentUser":"1","stageInterval":2000,"self":true,"currentSteps":diffrence of current - start steps,"StartSteps":0000} ACTIVEUSERNAME 
// ********  NOW GET STEPS AT THIS TIME *******
$startStepsQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'inProgress/hotPotato' ORDER BY userGameDataID DESC LIMIT 1";
$startStepsData = $conn->query($startStepsQuery);
$startStepsrow = $startStepsData->fetch_assoc();
$startSteps = $startStepsrow['startSteps'];
$startActiveTime = $startStepsrow['startActiveTime'];

// *** GET CURRENT FITNESS DATA FOR ACTIVE USER ****

$url = "https://api.fitbit.com/1/user/".$selfFitBitID."/activities/date/".$todaysDate.".json";
$opts2 = array(
                         'http'=>array(
                         'method'=>"GET",
                         'header'=>"Authorization: Bearer ".$selfAccessToken."\r\n"
                        ));
$context = stream_context_create($opts2);
$data_user_activity = json_decode(file_get_contents($url, false, $context), true);
$data_user_summary = $data_user_activity['summary'];
if($data_user_summary['steps'])
	{
        	$currentSteps = $data_user_summary['steps'];
		$activeTime = $data_user_summary['fairlyActiveMinutes']+$data_user_summary['veryActiveMinutes'];
	}
else
	{
		$currentSteps=0;
		$activeTime=0;
	}



$stepsAccumulated = $currentSteps-$startSteps;
$activeTimeAccumulated = $activeTime - $startActiveTime;





$data = array('type' =>'gameActive', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$timeRemaining, 'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>2000, 'startTime'=>$startTime, 'self' => true, currentSteps => $stepsAccumulated, startSteps =>$startSteps);
$json_data = json_encode($data);
echo $json_data;


}
else
{
$data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$timeRemaining, 'currentUserName' => $activeUserName, 'currentUser' => $activeUserID, 'stageInterval'=>2000, 'startTime'=>$startTime, 'self' => false);
$json_data = json_encode($data);
echo $json_data;
}
?>
