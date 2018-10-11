 

<?php



$gameID = $_GET['gameID'];
$self= $_GET['self'];
$frnd = $_GET['friend'];
$json_arr = array('gameID' => $gameID, 'self' => $self, 'frnd' => $frnd);
$json_data = json_encode($json_arr);
echo $json_data;
return;
// ****** CONNECT TO THE DATABASE ***********

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
	$json_arr = array('error' =>"DataBase Connect Failed");
        $json_data = json_encode($json_arr);
        echo $json_data;
        return;
 
   die("Connection failed: " . $conn->connect_error);
}


?>

