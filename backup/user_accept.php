<html>
 <head>
  <title>Accept</title>
 </head>
 <body>
 

<?php 

 echo 'Hello ';
 $user_ID = $_GET['user_id'];
 $username = $_GET['username'];
 echo $username;
 echo "<br/>";
echo "some information" . phpversion();
echo "<br />";
$randData = generateRandomString(10);
 $saltr = base64_encode($randData);
 $token = $username.$randData;
 print_r($token);
 $serverAccessToken = hash('md5',$token);
echo"<br/>";print_r($serverAccessToken);




// User defined functions

function generateRandomString($length){ 
    $characters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $charsLength = strlen($characters) -1;
    $string = "";
    for($i=0; $i<$length; $i++){
        $randNum = mt_rand(0, $charsLength);
        $string .= $characters[$randNum];
    }
    return $string;
}

?>

 </body>
</html>
