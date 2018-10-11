 <?php

/* INPUT  --   NAME OF THE GAME AND METRIC
OUTPUT	 -- ID OF THE GAME */

$gameName = $_GET['gameName'];
$metric = $_GET['metric'];
// ***** CONNECT TO THE DATABASE ***********

include "appConstants.php";
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if(!$metric)
{
	$metric = 'activityTime';
}
$searchQuery = "SELECT * FROM games WHERE gameName = '$gameName' AND metric='$metric'";
$result = $conn->query($searchQuery);
if($result->num_rows > 0)
{
	$row = $result->fetch_assoc();
	$gameID = $row['gameID'];
	$json_arr = array('gameID' => $gameID);
	$json_data = json_encode($json_arr);
	echo $json_data;
}
else
{
	$json_arr = array('error' => " No such Game Found");
	$json_data = json_encode($json_arr);
        echo $json_data;
}
?>	
