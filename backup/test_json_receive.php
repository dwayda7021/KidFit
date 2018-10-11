
<?php

$add = 'http://kidsteam.boisestate.edu/kidfit/test_json_send.php';
$postFitbitID = http_build_query(array('user_id' => $user_SQL));
$opts = array('http' =>
    array(
        'method'  => 'GET',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => '5GP9WG'
    )
);

$context  = stream_context_create($opts);
//echo "<br/> Context <br/>";
//print_r($opts);
$fetch_data = file_get_contents($add, false,$context);
echo "<br/> fetch_data:";
print_r($fetch_data);

//echo "<br/> fetch_data_steps: <br/>";

$fetch_data2 = json_decode($fetch_data,true);

echo "<br/> fetch_Data2:";
print_r($fetch_data2);

echo "<br/> step_data: ";
print_r($fetch_data2['steps']);

/*

echo"<br/> <br/>";
print_r("hello");
echo "<br/>";
print_r("Inside handle redirect");
echo "<br/>";*/
 ?> 
