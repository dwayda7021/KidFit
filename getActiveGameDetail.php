<?php


/*

getActiveGameDetail.php

input -- EMAIL, ACCESS TOKEN, GAME INSTANCE ID AND GAME ID

output 
type 													-all
timeRemaining =>											-all 
activeUserID => user carrying the potato 								-hotPotatoAsync & hotPotatoCompetitive
activeUserName =>user carrying the potato								-hotPotatoAsync & hotPotatoCompetitive
stageInterval =>activity to achieve to pass the potato or endValue					-hotPotatoAsync & hotPotatoCompetitive & escapeTheTunnelSyncCollaborative
self =>trur or false											-hotPotatoAsync & hotPotatoCompetitive
startActivityValue =>											-all
CurrentActivityValue =>											-all
endValue =>												-escapeTheTunnelSyncCollaborative & escapeTheTunnelSyncCompetitive
gameStatus =>												-all
playerStatus =>												-all

*/


$self= $_GET['self'];
$accessToken = $_GET['accessToken'];
$gameInstanceID = $_GET['gameInstanceID'];
$gameID = $_GET['gameID'];


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
$selfEmail = $loginrow['email'];
$selfAcToken = $loginrow['appAccessToken'];
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
$endValue =  $groupIDrow['endValue'];
$gameStatusCurrent;
$currentActivityTime=0;
$currentSteps=0;
$stepsAccumulated=0;
$activeTimeAccumulated=0;
$prevSteps=0;
$prevActiveTime=0; 
$startSteps=0;
$startActiveTime=0;
$prevActiveStepsCumulative=0;
$max = 0;
$prevActiveTimeCumulative=0;
$currActiveStepsCumulative=0;
$currActiveTimeCumulative=0;

if($gameStatus!='gameInProgress')
{
	$json_arr = array('error'=>"Not an Active Game");
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
                $numberOfPlayers=$numberOfPlayers+1;
                array_push($playerIDList, $userID);
        }
}

// ************* COMPUTE PREVCUMULATIVE ACTIVITY AND CURRENT CUMULATIVE ACTIVITY **************
if($gameName == 'escapeTheTunnelSyncCollaborative')
{
	$gameStartQuery = "SELECT * from gameInstance where gameInstanceID='$gameInstanceID' ";
	$gameStartData = $conn->query($gameStartQuery);
	$gameStartRow = $gameStartData->fetch_assoc();
	if($gameMetric=='steps')
	{
		$prevActiveStepsCumulative= $gameStartRow['stageInterval'];
	}
	else
	{
		$prevActiveTimeCumulative = $gameStartRow['stageInterval'];
	}
	$endValue = $gameStartRow['endValue'];
	for($x=0;$x<$numberOfPlayers;$x=$x+1)
        {
		$pID = $playerIDList[$x];
		$emailQuery = "SELECT * from users where userID = '$pID'";
		$emailQueryData = $conn->query($emailQuery);
		$emailQueryRow = $emailQueryData->fetch_assoc();
		$email = $emailQueryRow['email'];
		$acToken = $emailQueryRow['appAccessToken'];
		$currentSteps=0;
		$currentActivityTime=0;
		include "fetchFitBitData.php";
		$currentSteps = $steps;
		$currentActivityTime = $veryActiveMinutes + $fairlyActiveMinutes;
		$currActiveStepsCumulative=$currActiveStepsCumulative+$currentSteps;
		$currActiveTimeCumulative=$currActiveTimeCumulative+$currentActivityTime;
	}
}
if($timeRemaining<0 && $gameStatus!='gameOver')
{
	switch($gameName)	
	{
		case 'hotPotatoAsync':
		case 'hotPotatoCompetitive':
			// ******** GET CURRENT FITNESS DATA FOR ACTIVE USER ***********
			$emailQuery = "SELECT * FROM users where userID='$activeUserID'";
			$emailData = $conn->query($emailQuery);
			$emailRow = $emailData->fetch_assoc();
			$email = $emailRow['email'];
			$acToken = $emailRow['appAccessToken'];
			include "fetchFitBitData.php";
			$currentSteps = $steps;	
			$currentActivityTime = $veryActiveMinutes + $fairlyActiveMinutes;

			 // ******** ACTIVITY DETAIL AT THE BEGINNING OF THIS GAME *********   
			 $startStepsQuery = "Select * from userGameData where userID= '$activeUserID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress/hotPotato'ORDER BY userGameDataID DESC LIMIT 1";
        		$startStepsData = $conn->query($startStepsQuery);
        		$startStepsrow = $startStepsData->fetch_assoc();
        		$startSteps = $startStepsrow['startSteps'];
        		$startActiveTime = $startStepsrow['startActiveTime'];
			
			// *********** ACTIVITY DETAIL IN PREVIOUS ACTIVE SESSIONS OF THIS GAME**************
			$prevStepsQuery = "Select * from userGameData where userID= '$activeUserID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress' ORDER BY userGameDataID DESC LIMIT 1";
			$prevStepsData = $conn->query($prevStepsQuery);
			if($prevStepsData->num_rows>1)
			{
				$prevStepsrow = $prevStepsData->fetch_assoc();
                		$prevSteps = $prevStepsrow['stepsCollected'];
                		$prevActiveTime = $prevStepsrow['activeTimeCollected'];
			}
			$stepsAccumulated = $prevSteps +$currentSteps - $startSteps;
			$activeTimeAccumulated = $prevActiveTime + $currentActivityTime - $startActiveTime;
			
			$userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$activeUserID' , '$gameID' , '$currDate' , '$stepsAccumulated' , '$activeTimeAccumulated', '$gameInstanceID' , 'userLost', '0', '0')";
				
			// ********** SET OTHERS AS WINNERS ***************
			for($x=0;$x<$numberOfPlayers;$x=$x+1)
			{
				$pID = $playerIDList[$x];
				if($pID!=$activeUserID)
				{
					// *********** ACTIVITY DETAIL IN PREVIOUS ACTIVE SESSIONS OF THIS GAME**************
					 $prevStepsQuery = "Select * from userGameData where userID= '$pID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress' ORDER BY userGameDataID DESC LIMIT 1";
					$prevStepsData = $conn->query($prevStepsQuery);
					if($prevStepsData->num_rows>0)
					{
						$prevStepsrow = $prevStepsData->fetch_assoc();
						$stepsAccumulated = $prevStepsrow['stepsCollected'];
						$activeTimeAccumulated = $prevStepsrow['activeTimeCollected'];
					}
					
					$userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$pID' , '$gameID' , '$currDate' , '$stepsAccumulated' , '$activeTimeAccumulated', '$gameInstanceID' , 'userWon', '0', '0')";
				        $userGameData = $conn->query($userGameDataSelfQuery);
				}
			}

			// ********* UPDATE GAME STATUS TO OVER ************

		        $updategamestatus = "UPDATE gameInstance SET gameStatus = 'gameOver' where gameInstanceID = '$gameInstanceID'";
	        	$updategamedata = $conn->query($updategamestatus);
		
	        	$json_arr = array('type'=> "gameOver", 'playerStatus' => 'userLost', 'stepsAccumulated' =>$stepsAccumulated, 'activeTimeAccumulated' =>$activeTimeAccumulated);
        		$json_data = json_encode($json_arr);
        		echo $json_data;
			break;

	case 'escapeTheTunnelSyncCompetitive':
		// ****** GET PREVIOUS DATA AND CURRENT FITNESS DATA**************
		$max = 0;
		for($x=0;$x<$numberOfPlayers;$x=$x+1)
                {
                                $pID = $playerIDList[$x];
				$emailQuery = "SELECT * from users where userID='$pID'";
				$emailQueryData = $conn->query($emailQuery);
				$emailQueryrow=$emailQueryData->fetch_assoc();
				$email = $emailQueryrow['email'];
				$acToken = $emailQueryrow['appAccessToken'];

				include "fetchFitBitData.php";
				$currentSteps = $steps;
				$currentActivityTime = $veryActiveMinutes + $fairlyActiveMinutes;


 	
				// ************ GET FITNESS DETAILS AT THE START OF THE GAME ***********
				$fitnessDetailQuery = "SELECT * from userGameData where userID= '$pID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress' ORDER BY userGameDataID DESC LIMIT 1";
				$fitnessDetailQueryData = $conn->query($fitnessDetailQuery);
				$fitnessDetailQueryrow=$fitnessDetailQueryData->fetch_assoc();				
				$prevSteps  = $fitnessDetailQueryrow['startSteps'];
				$prevActiveTime= $fitnessDetailQueryrow['startActiveTime'];

				$stepsAccumulated = $currentSteps-$prevSteps;
				$activeTimeAccumulated = $currentActivityTime- $prevActiveTime;
				if($gameMetric=='steps')
				{
					if($stepsAccumulated>$max)
					{
						$max= $stepsAccumulated;
					}
				}
				else
				{
					if($activeTimeAccumulated>$max)
					{
						$max=$activeTimeAccumulated;
					}
				}
	
				$userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$pID' , '$gameID' , '$currDate' , '$stepsAccumulated' , '$activeTimeAccumulated', '$gameInstanceID' , '--', '0', '0')";
				$conn->query($userGameDataSelfQuery);			
			}

		// ********* UPDATE USER STATUS AS WON OR LOST *************
		$userStatusQuery = "SELECT * FROM userGameData where gameInstanceID='$gameInstanceID' and userGameStatus = '--'";
		$userStatusData = $conn->query($userStatusQuery);
		while ($userStatusRow = $userStatusData->fetch_assoc())
		{
			$userStatus="";
			$userID = $userStatusRow['userID'];
			if($gameMetric=='steps')
			{	
				$stepsAccumulated = $userStatusRow['stepsCollected'];
				if($stepsAccumulated >=$max)
				{
					$userStatus="userWon";
				}
				else
				{
					$userStatus='userLost';
				}
			}
			else
			{
				$activeTimeAccumulated = $userStatusRow['activeTimeCollected'];
				if($activeTimeAccumulated>=$max)
				{
					$userStatus="userWon";
				}
				else
                                {
                                        $userStatus='userLost';
                                }
			}
			$updateUserGameStatus = "UPDATE userGameData SET userGameStatus = '$userStatus' where  gameInstanceID='$gameInstanceID' and userGameStatus = '--' AND userID='$userID'";
			if($userID==$selfID)
			{
				// ************ COMPOSE RESPONSE FOR SELF ********************
				$json_arr = array('type'=> "gameOver", 'playerStatus' => $userStatus, 'stepsAccumulated' =>$stepsAccumulated, 'activeTimeAccumulated' =>$activeTimeAccumulated);
                	        $json_data = json_encode($json_arr);
                        	echo $json_data;
			}
		}
		
		// ********* UPDATE GAME STATUS TO OVER ************

                        $updategamestatus = "UPDATE gameInstance SET gameStatus = 'gameOver' where gameInstanceID = '$gameInstanceID'";
                        $updategamedata = $conn->query($updategamestatus);
		
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
			$currentActivityTime=0;
			include "fetchFitBitData.php";
			$currentSteps = $steps;
			$currentActivityTime = $veryActiveMinutes + $fairlyActiveMinutes;		
			


			// ******** ACTIVITY AT THE START OF THE GAME ************
			 $fitnessDetailQuery = "SELECT * from userGameData where userID= '$pID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress' ORDER BY userGameDataID DESC LIMIT 1";
			$fitnessDetailQueryData = $conn->query($fitnessDetailQuery);
			$fitnessDetailQueryrow=$fitnessDetailQueryData->fetch_assoc();
			$prevSteps  = $fitnessDetailQueryrow['startSteps'];
			$prevActiveTime= $fitnessDetailQueryrow['startActiveTime'];

			$stepsAccumulated = $currentSteps-$prevSteps;
			$activeTimeAccumulated = $currentActivityTime- $prevActiveTime;
			// ********* INSERT INTO USERGAMEDATA *************
			
			$userGameDataSelfQuery ="INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, gameInstanceID, userGameStatus, startSteps, startActiveTime) VALUES ('$pID' , '$gameID' , '$currDate' , '$stepsAccumulated' , '$activeTimeAccumulated', '$gameInstanceID' , '--', '0', '0')";
                        $conn->query($userGameDataSelfQuery);
		}

		$userStatus="";
		if($gameMetric=='steps')
                {
			if(($currActiveStepsCumulative- $prevActiveStepsCumulative)>$endValue)
			{
				$userStatus='userWon';
			}
			else
			{
				$userStatus='userLost';
			}
		}
		else
		{
			if(($currActiveTimeCumulative-$prevActiveTimeCumulative)>$endValue)
			{
                                $userStatus='userWon';
                        }
                        else
                        {
                                $userStatus='userLost';
                        }
                }

		for($x=0;$x<$numberOfPlayers;$x=$x+1)
		{
			$pID = $playerIDList[$x];
			$updateUserGameStatus = "UPDATE userGameData SET userGameStatus = '$userStatus' where gameInstanceID='$gameInstanceID' and userGameStatus = '--' AND userID='$pID'";
			if($pID==$selfID)
			{
				$json_arr = array('type'=> "gameOver", 'playerStatus' => $userStatus, 'stepsAccumulated' =>$stepsAccumulated, 'activeTimeAccumulated' =>$activeTimeAccumulated);
			$json_data = json_encode($json_arr);
			echo $json_data;

			}
		}


		// // ********* UPDATE GAME STATUS TO OVER ************
		$updategamestatus = "UPDATE gameInstance SET gameStatus = 'gameOver' where gameInstanceID = '$gameInstanceID'";
		$updategamedata = $conn->query($updategamestatus);
		break;
	} //switch
	return;
} // if time<0
else if ($timeRemaining>0)
{
	switch($gameName)
        {
                case 'hotPotatoAsync':
                case 'hotPotatoCompetitive':
			// ****** ACTIVE USER NAME *************
			$activeUsernameQuery = "SELECT * FROM users WHERE userID='$activeUserID'";
			$activeUsernameData = $conn->query($activeUsernameQuery);
			$activeUsernamerow = $activeUsernameData->fetch_assoc();
			$activeUserName = $activeUsernamerow['userName'];
			
			// ****** FETCH  SELF CURRENT ACTIVITY **********
			$email = $selfEmail;				
			$acToken = $selfAcToken;
			include "fetchFitBitData.php";
			$currentSteps = $steps;
			$currentActivityTime = $veryActiveMinutes + $fairlyActiveMinutes;
			// ******** FETCH SELF ACTIVITY AT THE START OF THE GAME ***********
			$startStepsQuery = "Select * from userGameData where userID= '$selfID' AND gameInstanceID = '$gameInstanceID' AND userGameStatus = 'userInProgress/hotPotato' ORDER BY userGameDataID DESC LIMIT 1";		
			$startStepsData = $conn->query($startStepsQuery);
			$startStepsrow = $startStepsData->fetch_assoc();
			$startSteps = $startStepsrow['startSteps'];
			$startActiveTime = $startStepsrow['startActiveTime'];
			$stepsAccumulated = $currentSteps-$startSteps;	
			$activeTimeAccumulated = $currentActivityTime - $currentActivityTime;
			
			// ****** GET STAGE INTERVAL ****************
			$stageIntervalQuery = "SELECT * FROM gameInstance where gameInstanceID = '$gameInstanceID'";
			$stageIntervalData = $conn->query($stageIntervalQuery);
			$stageIntervalrow = $stageIntervalData->fetch_assoc();
			$stageInterval = $stageIntervalrow['stageInterval'];
			$startTime = $stageIntervalrow['createDate'];
			// ********** IF I AM THE ACTIVE USER ***************************
			if($selfID ==$activeUserID)
			{
				$data = array('type' =>'gameInProgress', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$timeRemaining,'activeUserID'=>$activeUserID, 'activeUserName'=>$activeUserName, 'stageInterval'=>$stageInterval, 'self'=>true, 'startSteps' =>$startSteps, 'startActiveTime'=>$startActiveTime, 'currentSteps'=>$currentSteps, 'currentActivityTime'=>$currentActivityTime,'endValue'=>'0');
			}
			else
			{
				$data = array('type' =>'gameInProgress', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$timeRemaining,'activeUserID'=>$activeUserID, 'activeUserName'=>$activeUserName, 'stageInterval'=>$stageInterval, 'self'=>false, 'startSteps' =>$startSteps, 'startActiveTime'=>$startActiveTime, 'currentSteps'=>$currentSteps, 'currentActivityTime'=>$currentActivityTime,'endValue'=>'0');
			}
			$json_data = json_encode($data);
                        echo $json_data;
			break;
		case 'escapeTheTunnelSyncCollaborative':
		case 'escapeTheTunnelSyncCompetitive':
			if($gameMetric=='steps')					
			{
				$stageInterval = $prevActiveStepsCumulative;
			}
			else
			{
				$stageInterval = $prevActiveTimeCumulative;
			}
			
			// ******* ACTIVITY AT THE START OF THE GAME ********
			
			$startActivityQuery = "SELECT * from userGameData where userID='$selfID'AND gameInstanceID= '$gameInstanceID' AND userGameStatus = 'userInProgress'";
			$startActivityData = $conn->query($startActivityQuery);	
			$startActivityRow = $startActivityData->fetch_assoc();
			$startSteps = $startActivityRow['startSteps'];
			$startActiveTime = $startActivityRow['startActiveTime'];

			// ************** CURRENT ACTIVITY SELF *************
			$email =$selfEmail;
			$acToken = $selfAcToken;
			$currentSteps=0;	
			$currentActivityTime=0;
			include "fetchFitBitData.php";
			$currentSteps = $steps;
			$currentActivityTime = $veryActiveMinutes + $fairlyActiveMinutes;
			
			$data = array('type' =>'gameInProgress', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$timeRemaining,'activeUserID'=>'0', 'activeUserName'=>'null', 'stageInterval'=>$stageInterval, 'self'=>false, 'startSteps' =>$startSteps, 'startActiveTime'=>$startActiveTime, 'currentSteps'=>$currentSteps, 'currentActivityTime'=>$currentActivityTime,'endValue'=>$endValue);
			$json_data = json_encode($data);
			echo $json_data;
	}//switch

}//if (time >0)
else if($timeRemaining<0)
{
	// GET GAME WIN OR LOSE STATUS
	$gameStatusQuery = "SELECT * from userGameData where gameInstanceID= '$gameInstanceID' AND userID='$self' ORDER BY userGameDataID DESC LIMIT 1";
	$gameStatusData = $conn->query($gameStatusQuery);	
	$gameStatusrow = $gameStatusData->fetch_assoc();
	$gameStatus = $gameStatusrow['userGameStatus'];
	$data = array('type'=>'GameOver', 'playerStatus'=>$gameStatus);
	$json_data = json_encode($data);
	echo $json_data;
}
