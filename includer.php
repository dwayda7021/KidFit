<?php
include "including.php";
echo $json_data ;
$json_arr = array('addPlayer' => "success");
$json_data = json_encode($json_arr);
echo $json_data;
return;

?>



