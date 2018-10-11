<?php
/* getFinishedGames.php


INPUT -- EMAIL, ACESS TOKEN
OUTPUT -- numberOfFinishedGames,for each game(gameInstanceID, Name of Players and my status

*/


$self= $_GET['self'];
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

if($act!= $accessToken || !$accessToken )
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

$selfID = $loginrow['userID'];
$selfName = $loginrow['userName'];
$noOfFinishedGames=0;
$json_arr = array();

// *** GETTING DISTINCT Games FOR SELF *********
$gameInstanceQuery = "SELECT DISTINCT gameInstanceID from userGameData where userID='$selfID'";
$gameInstanceData = $conn->query($gameInstanceQuery);
while($gameInstancesrow = $gameInstanceData->fetch_assoc())
{
	// ******** CHECK GAME STATUS **********
	$userStatus ="";
	$gameInstanceID = $gameInstancesrow['gameInstanceID'];
	$gameStatusQuery = "SELECT * FROM gameInstance where gameInstanceID='$gameInstanceID'";
	$gameStatusData = $conn->query($gameStatusQuery);
	$gameStatusrow = $gameStatusData->fetch_assoc();
	$gameStatus = $gameStatusrow['gameStatus'];
	if($gameStatus == 'gameOver')
	{
		$noOfFinishedGames = $noOfFinishedGames+1;
		array_push($json_arr,$gameInstanceID);
		 
		// **** GET PLAYER NAMES 	
		$groupID = $gameStatusrow['groupID'];
		$playersQuery = "SELECT * FROM userGroups WHERE groupID='$groupID'";
		$playersData = $conn->query($playersQuery);
		$players ="";
		while($playersrow = $playersData->fetch_assoc())
                {
			$playerID = $playersrow['userID'];
			// ********* check if the player  has not QUIT
			$playerStatusQuery = "SELECT * FROM userGameData WHERE userID ='$playerID' AND gameInstanceID='$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1";
			$playerStatusData = $conn->query($playerStatusQuery);
			$playerStatusrow = $playerStatusData->fetch_assoc();
			if($playerID ==$selfID)
			{
				$userStatus = $playerStatusrow['userGameStatus'];
			}
			if($playerStatusrow['userGameStatus']!='userQuit' && $playerID !=$selfID)
			{
				// ****** GET NAME OF PLAYER ****
				$userNameQuery = "SELECT * FROM users where userID = '$playerID'";
				$userNameData = $conn->query($userNameQuery);
				$userNamerow = $userNameData->fetch_assoc();
				$userName = $userNamerow['userName'];
				$players = $players.$userName.",";
					
			}
			
		}
		$players = substr($players, 0, -1);
		array_push($json_arr, $players);
		array_push($json_arr, $userStatus);
	}
}



$json_arr['noOfFinishedGames'] = $noOfFinishedGames;
$json_data = json_encode($json_arr);
echo $json_data;
?>


