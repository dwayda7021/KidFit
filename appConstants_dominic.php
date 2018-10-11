<?php

$timeForFitBitDataFetch = 2; // Time after which new data is to be fetched



$servername = "127.0.0.1";
$username = "root";
$password = "K1dzteam!";
$dbname = "FitData";
$client = '228NH4';
$secret = 'acd369c14cacd73f6985f84b24d4267d';
$encoding = base64_encode("$client:$secret");
$url = 'https://api.fitbit.com/oauth2/token';
$stepsToPassThePotato = 2000;
$activityTimeToPassThePotato = 10;
$cumulativeStepsToGet = 2000;
$cumulativeActiveTimeToGet = 18;

?>
