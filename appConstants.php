<?php

$timeForFitBitDataFetch = 2; // Time after which new data is to be fetched



$servername = "127.0.0.1";
$username = "root";
$password = "K1dzteam!";
$dbname = "FitData";
$client = '22D8TS';
$secret = '5cc349811897de9def400cbf8452c69a';//'acd369c14cacd73f6985f84b24d4267d';'1ae284e8dd38682cd019328b15a9b6ff';
$encoding = base64_encode("$client:$secret");
$url = 'https://api.fitbit.com/oauth2/token';
$stepsToPassThePotato = 2000;
$activityTimeToPassThePotato = 10;
$cumulativeStepsToGet = 2000;
$cumulativeActiveTimeToGet = 18;

?>
