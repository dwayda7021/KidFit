<?php
/*
INPUT -- EMAIL, ACCESS TOKEN, GAMEINSTANCE ID, USERS OF WHICH STATUS TYPE WANTED
OUTPUT -- NUMBER OF PLAYERS AND NAME OF EACH PLAYER OF GIVEN STATUS TYPE

*/


$gameInstanceID = $_GET['gameInstanceID'];
$accessToken = $_GET['accessToken'];
$self= $_GET['self'];
$userGameStatus = $_GET['userGameStatus'];
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
if(!$act== $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}


if(!$gameInstanceID)
{
	$json_arr = array('error'=>" No Such Group Found");
	$json_data = json_encode($json_arr);
	echo $json_data; 
	return;
}

$groupIDquery = "SELECT * FROM gameInstance where gameInstanceID='$gameInstanceID'";
$groupIDqueryData = $conn->query($groupIDquery);
$groupIDqueryrow = $groupIDqueryData->fetch_assoc();
$groupID = $groupIDqueryrow['groupID'];
$players="";
$json_arr= array();
$count=0;
$playersQuery = "SELECT * FROM userGroups where groupID='$groupID'";
$playersData = $conn->query($playersQuery);
while($playersrow= $playersData->fetch_assoc())
{
	$playerID = $playersrow['userID'];
	$userGameStatusQuery = "SELECT * FROM userGameData WHERE userID = '$playerID' AND gameInstanceID='$gameInstanceID' ORDER BY userGameDataID DESC LIMIT 1 ";
	$userGameStatusData = $conn->query($userGameStatusQuery);
	$userGameStatusrow = $userGameStatusData->fetch_assoc();

	if($userGameStatus == $userGameStatusrow['userGameStatus'])
	{
		$count=$count+1;
		$userNameQuery = "SELECT * from users WHERE userID = '$playerID'";
		$userNameData = $conn->query($userNameQuery);
		$userNamerow = $userNameData->fetch_assoc();
		$userName = $userNamerow['userName'];
		array_push($json_arr,$userName);
	}
}	
$json_arr['numberOfPlayers'] = $count;


$json_data = json_encode($json_arr);
echo $json_data;

?>
