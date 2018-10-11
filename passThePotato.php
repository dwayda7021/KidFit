<?php
/*   passThePotato.php
INPUT -- SELF, ACCESS TOKEN, GAMEINSTANCE, GAMEID
OUTPUT -- RESPONSE TO SELF

NOTIFICATION TO CURRENT USER


*/

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
$gameID = $_GET['gameID'];


// ****** CONNECT TO THE DATABASE ***********

include "appConstants.php";
$currDate = date("Y-m-d H:i:s", strtotime("now"));
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
$selfEmail = $loginrow['email'];
$selfAcToken = $loginrow['appAccessToken'];
$currentSteps=0;
$currentActivityTime=0;

// *** GET CURRENT FITNESS DATA FOR SELF  ****
$email = $selfEmail;
$acToken = $selfAcToken;
include "fetchFitBitData.php";
$currentSteps = $steps;
$currentActivityTime = $veryActiveMinutes + $fairlyActiveMinutes;



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





//  **** GET TIME REMAINING BEFORE PASSING ************

$groupIDQuery = "SELECT * from gameInstance where gameInstanceID= '$gameInstanceID'";
$groupIDData =$conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameStatus = $groupIDrow['gameStatus'];
$gameID = $groupIDrow['gameID'];
$activeUserID = $groupIDrow['activeUser'];
$endDate = strtotime($groupIDrow['endDate']);
$startDateTime = strtotime("now");
$timeRemaining = $endDate-$startDateTime;

if(!($activeUserID==$selfID))
{
	$json_arr = array('error'=>"You are not the Active User. Cannot pass the potato!");
	$json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


if($timeRemaining<=0)
{
	$updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='gameOver' where gameInstanceID = '$gameInstanceID'";
	$updateGameInstanceData = $conn->query($updateGameInstanceQuery);
	// **** INSERT INTO USERGAME DATA FOR ALL PLAYERS ******
	

	// *** INSERT Lost FOR SELF *****

	// ******** ACTIVITY DETAIL AT THE BEGINNING OF THIS GAME ********* 
	 $startStepsQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress/hotPotato'ORDER BY userGameDataID DESC LIMIT 1";
        $startStepsData = $conn->query($startStepsQuery);
       	$startStepsrow = $startStepsData->fetch_assoc();
	$startSteps = $startStepsrow ['startSteps'];
	$startActiveTime = $startStepsrow['startActiveTime'];	

	 // *********** ACTIVITY DETAIL IN PREVIOUS ACTIVE SESSIONS OF THIS GAME**************

	$prevStepsQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress' ORDER BY userGameDataID DESC LIMIT 1";
	$prevStepsData = $conn->query($prevStepsQuery);
	if($prevStepsData->num_rows>0)
	{
		 // GET PREVIOUSLY COLLECTED STEPS
		$prevStepsrow = $prevStepsData->fetch_assoc();
		$prevSteps = $prevStepsrow['stepsCollected'];
		$prevActiveTime = $prevStepsrow['activeTimeCollected'];
		$stepsAccumulated = $prevSteps +$currentSteps - $startSteps;
		$activeTimeAccumulated = $prevActiveTime + $currentActivityTime - $startActiveTime;
	}
	else
	{
		$stepsAccumulated = $currentSteps - $startSteps;
		$activeTimeAccumulated = $currentActivityTime - $startActiveTime;
	}

		
	$userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$selfID', '$gameID', '$currDate', '$stepsAccumulated','$activeTimeAccumulated','$gameInstanceID','userLost','0','0')";
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
		if($userGameStatus!='userQuit' && $userID!=$selfID)
        	{
			// *********** ACTIVITY DETAIL IN PREVIOUS ACTIVE SESSIONS OF THIS GAME**************
			$prevStepsQuery = "Select * from userGameData where userID= '$userID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress' ORDER BY userGameDataID DESC LIMIT 1";
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
                        $userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$userID' , '$gameID' , '$currDate' , '$stepsAccumulated' , '$activeTimeAccumulated', '$gameInstanceID' , 'userWon', '0', '0')";
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




	$json_arr = array('type'=>'gameOver', 'playerStatus' => 'userLost');
	$json_data = json_encode($json_arr);
        echo $json_data;
        return;
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
        if($userGameStatus!='userQuit')
        {
		if($userID!=$selfID)
		{
                	$numberOfPlayers=$numberOfPlayers+1;
                	array_push($playerIDList, $userID);
		}
        }
}


// ***  FIND NEW ACTIVE USER  ************
if($numberOfPlayers<2)
{
	$newactiveUserID = $playerIDList[0];
}
else
{
	$newactiveUserID = rand(1,$numberOfPlayers);
	$newactiveUserID = $playerIDList[$activeUserID-1];
}

// ******************** UPDATE GAME INSTANCE **********
$updateGameInstanceQuery = "UPDATE gameInstance SET activeUser='$newactiveUserID' where gameInstanceID = '$gameInstanceID'";
$conn->query($updateGameInstanceQuery);

// ***** INSERT INTO USERGAMEDATA FOR SELF AND ACTIVE USER *************

// STEPS & TIME COLLECTED 
$startStepsQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress/hotPotato' ORDER BY userGameDataID DESC LIMIT 1";
$startStepsData = $conn->query($startStepsQuery);
$startStepsrow = $startStepsData->fetch_assoc();
$startSteps = $startStepsrow['startSteps'];
$startActiveTime = $startStepsrow['startActiveTime'];
$startuserGameDataID = $startStepsrow['userGameDataID'];


// CHECK FOR MORE THAN ONE PASSES OF POTATO
$passPotatoQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress' ORDER BY userGameDataID DESC LIMIT 1";
$passPotatoData = $conn->query($passPotatoQuery);
if($passPotatoData->num_rows>0)
{	
	$passPotatorow = $passPotatoData->fetch_assoc();
	$lastPassed = $passPotatorow['userGameDataID'];
	$prevStepsAccumulated = $passPotatorow['stepsCollected'];
	$prevActiveTimeAccumulated = $passPotatorow['activeTimeCollected'];
}
else
{
	$lastPassed=0;
}
if($lastPassed>$startuserGameDataID)
{
	// ** HELD POTATO MORE THAN ONCE FOR CURRENT GAME INSTANCE
	$stepsCollected = $prevStepsAccumulated+ $currentSteps- $startSteps;
	$activeTimeCollected = $prevActiveTimeAccumulated + $activeTime  - $startActiveTime;
}
else
{
	$stepsCollected = $currentSteps- $startSteps;
	$activeTimeCollected =  $activeTime  - $startActiveTime;

}


// *** INSERT FOR SELF *********
$selfUserGameDataQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$selfID', '$gameID', '$currDate' ,'$stepsCollected' , '$activeTimeCollected', '$gameInstanceID', 'userInProgress' , '0', '0')";
$selfUserGameData = $conn->query($selfUserGameDataQuery);

// ********* INSERT FOR ACTIVE USER ***********
// GET FITNESS DATA 

// ****** ACTIVE USER NAME *************
$activeUsernameQuery = "SELECT * FROM users WHERE userID='$newactiveUserID'";
$activeUsernameData = $conn->query($activeUsernameQuery);
$activeUsernamerow = $activeUsernameData->fetch_assoc();
$activeUserName = $activeUsernamerow['userName'];
$email = $activeUsernamerow['email'];
$acToken = $activeUsernamerow['appAccessToken'];
$currentStepsActiveUser=0;
$activeTimeActiveUser =0;
$deviceID= $activeUsernamerow['deviceRegistrationID'];
include "fetchFitBitData.php";
$currentStepsActiveUser = $steps;
$activeTimeActiveUser = $fairlyActiveMinutes+$veryActiveMinutes;


$activeUserGameDataQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$newactiveUserID', '$gameID', '$currDate' ,'0' , '0', '$gameInstanceID', 'userInProgress/hotPotato' , '$currentStepsActiveUser', '$activeTimeActiveUser')";
if($conn->query($activeUserGameDataQuery))
{

$stageIntervalQuery = "SELECT * from gameInstance where gameInstanceID ='$gameInstanceID'";
$stageIntervalData = $conn->query($stageIntervalQuery);
$stageIntervalRow = $stageIntervalData->fetch_assoc();
$stageInterval = $stageIntervalRow['stageInterval'];

// SEND NOTIFICATION TO ACTIVE USER
$data = array('type' =>'potatoPassed', 'gameInstanceID' => $gameInstanceID, 'stageInterval'=>$stageInterval, 'self' => true,'currentUserName' => $activeUserName, 'currentSteps' => $currentStepsActiveUser,'currentActivityTime' => $activeTimeActiveUser);
$message = $selfName." has passed the potato to you";

sendNotification($deviceID, $data, $message  );

$json_arr = array('type'=>'potatoPassed');
$json_data= json_encode($json_arr);
echo $json_data;
}
else
{
$json_arr = array('error'=>'Insert Unsuccessful');
$json_data= json_encode($json_arr);
echo $json_data;
}
// SEND NOTIFICATION TO OTHERS

?>
