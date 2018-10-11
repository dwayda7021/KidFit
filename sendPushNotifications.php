<?php
// API access key from Google API's Console
$serverKey = 'AAAAgL6BhsA:APA91bH0YaFKWoS_lwKC_9kfSq9ghbXBr0Gq8pNlgRqssweqyGqDwz4ZctGk5-B0CnSHwCXJ29XcxjxgZdn6V38d0sbsKodiot7wsLKq44CUOf-ePChIFSF_TGl-P9PkgEKL0Cit_dGb'; 
$apiKey = 'AIzaSyAhukMMK0vTOr8Eu_hl5dhqad9RiAfjP74';
define( 'API_ACCESS_KEY', $serverKey );

$registrationIds = $_GET['id'] ;

// prep the bundle
//$data = $_GET['data'];
$gameInstanceID =1;
$randGameTime =2500;
$activeUserID=1;
$startTime=0;

 $data = array('type' =>'gameStart', 'gameInstanceID' => $gameInstanceID, 'timeRemaining' =>$randGameTime, 'currentUser' => $activeUserID, 'stageInterval'=>2000, 'startTime'=>$startTime, 'self' => true);


$msg = array
(
	'message' 	=> 'here is a message. message',
	'title'		=> 'This is a title. title',
	'subtitle'	=> 'This is a subtitle. subtitle',
	'tickerText'	=> 'Ticker text here...Ticker text here...Ticker text here',
	'vibrate'	=> 1,
	'sound'		=> 1,
	'largeIcon'	=> 'large_icon',
	'smallIcon'	=> 'small_icon',
	'gameInstanceID' => 1,
);
$fields = array
(
	'to'	=> $registrationIds,
	'notification'			=> $msg,
	'data' =>$data
);
 
$headers = array
(
	'Authorization: key=' . API_ACCESS_KEY,
	'Content-Type: application/json'
);
 
$ch = curl_init();
curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
curl_setopt( $ch,CURLOPT_POST, true );
curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
$result = curl_exec($ch );
curl_close( $ch );
echo $result;

?>
