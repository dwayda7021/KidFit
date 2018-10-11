<?php

/* 

declineGameInvite.php
INPUT -- username, access token and gameInstanceID
OUTPUT -- success or error

*/



$self= $_GET['self'];
$accessToken = $_GET['accessToken'];
$gameInstanceID = $_GET['gameInstanceID'];


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

if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


$selfID = $loginrow['userID'];
$selfName = $loginrow['userName'];
$selfAccessToken = $loginrow['accessToken'];
$selfFitbitToken = $loginrow['fitbitID'];


// **** GET GROUP ID ******

$groupIDQuery = "SELECT * FROM gameInstance where gameInstanceID = '$gameInstanceID'";
$groupIDData = $conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameID = $groupIDrow['gameID'];

// ********** UPDATE user Entry in USERGAMEDATA ********
$updateUserGameDataQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, startSteps, startActiveTime, activeTimeCollected, gameInstanceID, userGameStatus) VALUES ('$selfID' ,  '$gameID' , '$currDate', '0', '0','0','0', '$gameInstanceID', 'userQuit')";
$updateUserGameData= $conn->query($updateUserGameDataQuery);

// ********** UPDATE gameInstance Table  if number of users is less than 2********
	
	
// **** No. of users in that particular Group
$userNum=0;
$userNumQuery = "SELECT * FROM userGroups where groupID='$groupID'";
$userNumData = $conn->query($userNumQuery);
while($userNumrow = $userNumData->fetch_assoc())
{
	$playerID = $userNumrow['userID'];
	//  *** GET USER STATUS
	$userStatusQuery = "SELECT * FROM userGameData where userID ='$playerID' AND gameInstanceID = '$gameInstanceID' ORDER by userGameDataID DESC LIMIT 1";
	$userStatusData = $conn->query($userStatusQuery);
	$userStatusrow = $userStatusData->fetch_assoc();
	$userStatus = $userStatusrow['userGameStatus'];
	if($userStatus!='userQuit')
	{
		$userNum=$userNum+1;
	}
}
if($userNum<=1)
{
	// ********* SET GAME INSTANCE STATUS TO QUIT **********
	$status = 'gameQuit';
	$updateGameStatusQuery = "UPDATE gameInstance SET gameStatus = '$status' where gameInstanceID = '$gameInstanceID'";
	$updateGameStatusData = $conn->query($updateGameStatusQuery);

	// ********** SET OTHER PLAYER TO QUIT **********
	$userIDQuery ="SELECT * FROM userGameData where gameInstanceID='$gameInstanceID'";
	$userIDData = $conn->query($userIDQuery);
	while($userIDrow = $userIDData->fetch_assoc())
	{ 
		$userIDuser = $userIDrow['userID'];
		if($userIDuser!=$selfID)
		{
			$updateUserGameDataQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, startSteps, startActiveTime, activeTimeCollected, gameInstanceID, userGameStatus) VALUES ('$userIDuser' ,  '$gameID' , '$currDate', '0', '0','0','0', '$gameInstanceID', 'userQuit')";
			$updateUserGameData= $conn->query($updateUserGameDataQuery);
		}
	}
}
$json_arr= array("type" =>"declineInvite");
$json_data = json_encode($json_arr);
echo $json_data;

?>
