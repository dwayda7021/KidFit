<?php

/* removePlayerFromGameInstance.php
INPUT -- EMAIL, ACCESS TOKEN, FRND TO BE REMOVED AND GAME INSTANCE ID
OUTPUT -- SUCCESS 
	

*/ 

$gameInstanceID = $_GET['gameInstanceID'];
$self= $_GET['self'];
$frndID = $_GET['friendID'];
$accessToken = $_GET['accessToken'];


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
$selfID = $loginrow['userID'];

if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


if(!$gameInstanceID)
{
        $json_arr = array('error' =>"Inavlid Game Instance");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


// ************ GET NUMBER OF PLAYERS (NON QUIT) IN THE GAME INSTANCE ***********

$groupIDQuery = "SELECT * FROM gameInstance WHERE gameInstanceID = '$gameInstanceID'";
$groupIDData = $conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$ownerID = $groupIDrow['ownerUserID'];
$gameStatus = $groupIDrow['gameStatus'];
$gameID = $groupIDrow['gameID'];
if($gameStatus == 'gameQuit')
{
	$json_arr = array('error' =>"Game Not Active");
	$json_data = json_encode($json_arr);
        echo $json_data;
        return;

}
if(!($ownerID==$selfID))
{
	$json_arr = array('error' => "Only game Owner can Delete Players");
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}
$playerQuery = "SELECT * FROM userGroups WHERE groupID='$groupID'";
$playersData = $conn->query($playerQuery);
$noPlayers = 0;
$isFrndinGroup=false;
while($playersrow= $playersData->fetch_assoc())
{
	
	$playerID = $playersrow['userID'];
	// **** GET USER GAME STATUS FOR THIS PLAYER
	$userGameStatusQuery = "SELECT * FROM userGameData where userID='$playerID' AND gameInstanceID ='$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
	$userGameStatusData = $conn->query($userGameStatusQuery);
	$userGameStatusrow = $userGameStatusData->fetch_assoc();
	$userGameStatus = $userGameStatusrow['userGameStatus'];
	if($userGameStatus !='userQuit')
	{
		if($playerID==$frndID)
		{
			$isFrndinGroup=true;
		}
	 
		$noPlayers=$noPlayers+1;
	}
}

if(!$isFrndinGroup)
{
	$json_arr = array('error' => "Player Not Found in group or Player Already Quit");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}
if($noPlayers>2)
{
	$updateUserGameStatusQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$frndID', '$gameID', '$currDate' , '0','0','0','0','$gameInstanceID', 'userQuit')";
        if($conn->query($updateUserGameStatusQuery))
	{
		$json_arr = array("type"=>"removePlayerFromGameInstance");
		$json_data = json_encode($json_arr);
		echo $json_data;

	}
}
else
{
	//*****  CHANGE GAME STATUS TO QUIT AND UPDATE USER GAME DATA
	$updateUserGameStatusQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected,startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$frndID', '$gameID', '$currDate' , '0','0','0','0','$gameInstanceID', 'userQuit')";
	if($conn->query($updateUserGameStatusQuery))
	{
		$json_arr = array("type"=>"removePlayer");
                $json_data = json_encode($json_arr);
                echo $json_data;
	}
	$updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='gameQuit' where gameInstanceID ='$gameInstanceID'";
	$updateGameInstanceData = $conn->query($updateGameInstanceQuery);


}

?>
