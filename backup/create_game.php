 <?php

$servername = "127.0.0.1";
$username = "root";
$password = "K1dzteam!";
$dbname = "FitData";
$client = '228NH4';
$secret = 'acd369c14cacd73f6985f84b24d4267d';
$encoding = base64_encode("$client:$secret");
$url = 'https://api.fitbit.com/oauth2/token';
$sqlDateTime = date_create('now');
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$expiryTime = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ************ HOT POTATO ***************

$potatoGame = "INSERT INTO games (gameName, createDate, status) VALUES ('hotPotato', '$currDate','TRUE')";
if($conn->query($potatoGame)===TRUE)
{
	echo " Hot Potato Game Created";
}
else
{
	echo " Hot Potato Game Creation Failed";
}
