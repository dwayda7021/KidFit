<?php
/* getCurrentActivityTime.php

INPUT -- EMAIL, ACCESS TOKEN
OUTPUT -- fairlyActiveMinutes and veryActiveMinutes

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
$selfID=$loginrow['userID'];
$selfName = $loginrow['userName'];
if($act!= $accessToken)
{
        $json_arr = array('error'=> "accessToken does not match");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
}
$email = $self;
$acToken= $accessToken;

include "fetchFitBitData.php";
$json_arr= array("veryActiveMinutes"=>$veryActiveMinutes, "fairlyActiveMinutes" => $fairlyActiveMinutes);
$json_data = json_encode($json_arr);
echo $json_data;
?>
