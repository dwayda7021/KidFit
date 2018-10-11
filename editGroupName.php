<?php


/* editGroupName.php

input -- email, accesstoken, gameInstanceID, new group name
output  -- 


*/

$gameInstanceID = $_GET['gameInstanceID'];
$self= $_GET['self'];
$accessToken = $_GET['accessToken'];
$newGroupName = $_GET['newGroupName'];
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
        $json_arr = array('error' =>"Invalid Game Instance");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}

// **** GET GROUPID FROM GAME INSTANCE *********

$groupIDQuery = "SELECT * FROM gameInstance where gameInstanceID='$gameInstanceID'";
$groupIDData = $conn->query($groupIDQuery);
$groupIDrow = $groupIDData->fetch_assoc();
$groupID = $groupIDrow['groupID'];
$gameID = $groupIDrow['gameID'];

// ***** UPDATE GROUP Table **********
$groupNameUpdateQuery = "UPDATE groupTable SET groupName = '$newGroupName' WHERE groupID = '$groupID' AND gameID = '$gameID'";
$groupNameUpdateData = $conn->query($groupNameUpdateQuery);

$json_arr = array('type =>'editGroupName', 'newGroupName' => $newGroupName);
$json_data = json_encode($json_arr);
echo $json_data;

?>


