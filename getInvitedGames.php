<?php
/* 

getInvitedGames.php
INPUT -- EMAIL, ACESS TOKEN, GAMEID
OUTPUT -- numberOfInvitedGames,for each game(gameInstanceID, String with name of players and self status in each game 


*/ 
$self= $_GET['self'];
$accessToken = $_GET['accessToken'];
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

if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

$selfID = $loginrow['userID'];
$selfName = $loginrow['userName'];
$numberOfInvitedGames=0;
$json_arr = array();
//   *********** GETTING GAMES WITH STATUS INVITED  *****************

$gameInstanceQuery = "SELECT DISTINCT gameInstanceID from userGameData where userID='$selfID' and gameID ='$gameID'";
$gameInstanceData = $conn->query($gameInstanceQuery);
while($gameInstancesrow = $gameInstanceData->fetch_assoc())
{
        $gameInstanceID = $gameInstancesrow['gameInstanceID'];
        $userStatusQuery = "SELECT * FROM userGameData where userID='$selfID' AND gameInstanceID = '$gameInstanceID' AND gameID='$gameID' ORDER BY userGameDataID DESC LIMIT 1";
        $userStatusData = $conn->query($userStatusQuery);
        $userStatusrow = $userStatusData->fetch_assoc();
        $userStatus = $userStatusrow['userGameStatus'];
	if($userStatus=='userInvited' || $userStatus=='userReadyToPlay')
	{
		// *** CheCK GAMe STATUS
                $gameStatusQuery = "SELECT * from gameInstance where gameInstanceID = '$gameInstanceID' AND gameID='$gameID'";
                $gameStatusData = $conn->query($gameStatusQuery);
                $gameStatusrow = $gameStatusData->fetch_assoc();
                $gameStatus = $gameStatusrow['gameStatus'];
                $groupID = $gameStatusrow['groupID'];       
		if(($gameStatus=='gameInvited'))
		{
			$numberOfInvitedGames=$numberOfInvitedGames+1;
                        array_push($json_arr,$gameInstanceID);
                        $playersQuery = "SELECT * FROM userGroups WHERE groupID='$groupID'";
                        $playersData = $conn->query($playersQuery);
                        $players ="";
                        while($playersrow = $playersData->fetch_assoc())
                        {
                                $playerID = $playersrow['userID'];
                                // ********* check if the player  has not QUIT
                                $playerStatusQuery = "SELECT * FROM userGameData WHERE userID ='$playerID' AND gameInstanceID='$gameInstanceID' AND gameID='$gameID' ORDER BY userGameDataID DESC LIMIT 1";

                                $playerStatusData = $conn->query($playerStatusQuery);
                                $playerStatusrow = $playerStatusData->fetch_assoc();
                                if($playerStatusrow['userGameStatus']!='userQuit')
                                {
                                        // *** GET USER NAME OF PLAYER
                                        $userNameQuery = "SELECT * FROM users WHERE userID = '$playerID'";
                                        $userNameData = $conn->query($userNameQuery);
                                        $userNamerow = $userNameData->fetch_assoc();
                                        $userName = $userNamerow['userName'];
                                        if(!($userName==$selfName))
                                        {
                                                $players = $players.$userName.",";
                                        }
                                }
                        }
                        $players = substr($players, 0, -1);
                        array_push($json_arr, $players);
                        array_push($json_arr, $userStatus);
                }
        }


}




$json_arr['numberOfInvitedGames'] = $numberOfInvitedGames;
$json_data = json_encode($json_arr);
echo $json_data;









?>









