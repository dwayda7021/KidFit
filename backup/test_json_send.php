<?php

//$steps_encoded = utf8_encode('steps');
$steps_encoded = 'steps';
//echo "<br/> steps-encoded:";
//echo $steps_encoded;

//$number_encoded = utf8_encode('10');
$number_encoded = 10;
//echo "<br/> number-encoded:";
//echo $number_encoded;

$json_arr = array($steps_encoded => $number_encoded);
//echo "<br/> json_array: ";
//print_r($json_arr);

//$json_nested_arr = array($json_arr);
//echo "<br /> json_nested_array:";
//print_r($json_nested_arr);

$json_response = json_encode($json_arr);
//echo "<br/> json_response:";
echo $json_response;

?>