
 <?php
/* acceptGameInvite.php 
INPUT -- EMAIL, ACCESS TOKEN, GAME INSTANCE ID  
OUTPUT -- userGameStatus'=>"updated"
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

if($act!= $accessToken||!$accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

$selfID = $loginrow['userID'];
$selfName = $loginrow['userName'];



// ******** UPDATE USER GAME STATUS IN userGameData *****

$userGameDataQuery = "SELECT * from userGameData where gameInstanceID = '$gameInstanceID' AND userID='$selfID' ORDER BY userGameDataID DESC LIMIT 1";
$userGameData = $conn->query($userGameDataQuery);
if($userGameData->num_rows >0)
{
	$userGameDatarow = $userGameData ->fetch_assoc();
	$gameID = $userGameDatarow['gameID'];

	$updateUserGameStatus = "INSERT INTO userGameData (userID, gameID, createDate, stepsCollected, activeTimeCollected, startSteps, startActiveTime, gameInstanceID, userGameStatus) VALUES ('$selfID', '$gameID', '$currDate','0','0','0','0','$gameInstanceID', 'userReadyToPlay')"; 
	if($conn->query($updateUserGameStatus))
	{
		$json_arr = array('type'=>'acceptInvite', 'userGameStatus'=>"updated");
		$json_data = json_encode($json_arr);
		echo $json_data;
	}
}
else
{
	$json_arr = array('error' =>" User Not Found in this game");
	$json_data = json_encode($json_arr);
	echo $json_data;
	return;
}

// ** UPDATE GAME INSTANCE STATUS IF EVERYONE IS READY TO PLAY *****
// ***** GET GROUP ID *********

$groupIDQuery = "SELECT * from gameInstance WHERE gameInstanceID = '$gameInstanceID'";
$groupIDData = $conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];

//  ******* GET ALL PLAYERS IN THAT GROUP ********
$gameStatus =0;
$playersQuery = "SELECT * FROM userGroups WHERE groupID = '$groupID'";
$playersData = $conn->query($playersQuery);
while($playersrow = $playersData->fetch_assoc())
{
        $userID = $playersrow['userID'];
	// *** GET GAME STATUS OF THIS USER 
	$userStatusQuery = "SELECT * from userGameData where gameInstanceID = '$gameInstanceID' AND userID='$userID' ORDER BY userGameDataID DESC LIMIT 1";
	$userStatusData = $conn->query($userStatusQuery);
	$userStatusrow = $userStatusData->fetch_assoc();
	$userStatus = $userStatusrow['userGameStatus'];
	if($userStatus == "userInvited")
	{
		$gameStatus=1;
	}
}

if($gameStatus ==0)
{
	$updateGameInstanceQuery = "UPDATE gameInstance SET gameStatus='gameReadyToPlay' where gameInstanceID = '$gameInstanceID'";
	$updateGameInstanc = $conn->query($updateGameInstanceQuery);
	
}

?>
