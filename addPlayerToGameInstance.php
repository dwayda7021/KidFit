<?php

/* addPlayerToGameInstance.php
input -- email, accessToken, friends ,gameInstanceID
output -- 'success' => "addplayer"

*/

$gameInstanceID = $_GET['gameInstanceID'];
$self= $_GET['self'];
$frnd = $_GET['friend'];
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


if($self==$frnd)
{
        $json_arr = array('error' =>"Cannot Add Self");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


// GET GAME ID
$gameIDQuery = "SELECT * from gameInstance WHERE gameInstanceID = '$gameInstanceID'";
$gameIDData = $conn->query($gameIDQuery);
$gameIDrow = $gameIDData->fetch_assoc();
$gameID = $gameIDrow['gameID'];
$gameStatus = $gameIDrow['gameStatus'];

if($gameStatus == 'gameQuit')
{
	$json_arr= array('error' =>'Cannot add player. This game has been quit');
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}

// *********** check if gameType is hotPotatoCompetitive ****************
$gameNameQuery = "SELECT * FROM games where gameID ='$gameID'";
$gameNameQueryData = $conn->query($gameNameQuery);
$gameNameRow = $gameNameQueryData->fetch_assoc();
$gameName = $gameNameRow['gameName'];
if($gameName =='hotPotatoCompetitive')
{
        $json_arr= array('error' => 'Cannot add player. This game only allows two players');
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

$groupID = $gameIDrow['groupID'];


// ******** GET FRIEND DATA *******
$frndQuery = "SELECT * from users where email='$frnd'";
$frndData = $conn->query($frndQuery);
if($frndData->num_rows<1)
{
	// *********** Friend not in Databse, SEND email Invite


        $msg = "Hello ".$frnd.",\n Your friend $selfName would like to invite you to play kidFit Games. ";
        $msg = wordwrap($msg,70);
        mail($frnd,"Get Active with your friend,".$self.Name." with kidFit Games!" ,$msg);
        $insertUser = "INSERT INTO users ( fitbitID, email, pswrd, userName, createDate, accessToken, refreshToken, tokenExpDate, appAccessToken, appSalt, appTokenExpDate, userStatus, dataAccess, deviceRegistrationID) VALUES  ( 'null' ,'$frnd', 'null', '$frnd', '$currDate','null','null','$currDate','null', 'null','$currDate','InvitePending',false,'null')";
        if($conn->query($insertUser))
	{
		$frndID =$conn->insert_id;
	}
	$frndName = "";
}
else
{

	 // *********** Friend found in Databse ******
	$frndrow = $frndData->fetch_assoc();
	$frndID=$frndrow['userID'];
	$frndName = $frndrow['userName'];
}



// ******* AVOID DUPLICATE ADDITION *******

$reAdding=false;
$duplicateQuery = "SELECT DISTINCT userID from userGameData where gameInstanceID = '$gameInstanceID'";
$duplicateQueryData = $conn->query($duplicateQuery);
while( $duplicateQueryrow= $duplicateQueryData->fetch_assoc())
{
	if($duplicateQueryrow['userID']==$frndID)
	{

		// TAKE THE LATEST ENTRY FOR THAT PLAYER FROM USER GAME DATA
		$duplicatefrndQuery = "SELECT * from userGameData where gameInstanceID = '$gameInstanceID' AND userID = '$frndID' ORDER BY userGameDataID DESC LIMIT 1";
		$duplicatefrndData = $conn->query($duplicatefrndQuery);
		$duplicatefrndrow = $duplicatefrndData->fetch_assoc();

		if($duplicatefrndrow['userGameStatus']=='userQuit')
		{
			 $updateFrndStatus = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$frndID','$gameID', '$currDate', '0','0','0','0', '$gameInstanceID', 'userInvited')"; 
		 	$updateFrndData = $conn->query($updateFrndStatus);
			// ******** CHANGE GAME STATUS if number of players is 2 ***********
			if($gameStatus=='gameQuit')
			{
				$updateGameStatusQuery = "UPDATE gameInstance SET gameStatus='gameInvited' where gameInstanceID = '$gameInstanceID'";
				$updateGameStatusData = $conn->query($updateGameStatusQuery);
			}
			$reAdding=true;	
		}
		else
		{
			$json_arr = array('error' => "Duplicate Addition");
			$json_data = json_encode($json_arr);
			echo $json_data;
			return;
		}
	}

}



// ************ GET GROUP DATA ***********
$groupDataQuery = "SELECT * from groupTable where groupID ='$groupID'";
$groupData = $conn->query($groupDataQuery);
$groupDatarow = $groupData->fetch_assoc();
$groupName = $groupDatarow['groupName'];
$newGroupName = $groupName.$frndName[0];


// INSERT FRND IN USER GAME DATA ************
if(!$reAdding)
{
        $userGameDataInsertQuery = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, startSteps, startActiveTime,  gameInstanceID, userGameStatus) VALUES ('$frndID','$gameID', '$currDate', '0','0','0','0','$gameInstanceID','userInvited')";
        $userGameDataInsert = $conn->query($userGameDataInsertQuery);
        if(!$userGameDataInsert)
        {
                $json_arr = array('error' => "UserGameDataInsertFailed");
                $json_data = json_encode($json_arr);
                echo $json_data;
                return;
        }
}



// ****** UPDATE GROUP NAME ******
if(!$reAdding)
{
	$groupNameUpdate = "UPDATE groupTable SET groupName ='$newGroupName' where groupID = '$groupID'";
	$groupNameUpdateData = $conn->query($groupNameUpdate);
}

// ********* INSERT FRND IN USER GROUPS ***********
if(!$reAdding)
{
	$userGroupInsertQuery = "INSERT INTO userGroups (groupID, userID) VALUES ('$groupID','$frndID')";
	$userGroupInsert = $conn->query($userGroupInsertQuery);
}

	
$json_arr = array('type' => "addplayer");
$json_data = json_encode($json_arr);
echo $json_data;
return;



?>

