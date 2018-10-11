<?php
session_start();

//Include Google client library 
include_once 'src/Google_Client.php';
include_once 'src/contrib/Google_Oauth2Service.php';

/*
 * Configuration and setup Google API
 */
$clientId = '409231448063-q3ol84iolperclea3ruhdhp8f5mrk3o5.apps.googleusercontent.com';
$clientSecret = 'IzxLz7oe_WMG__ngQCAKqyYV';
$redirectURL = 'http://kidsteam.boisestate.edu/kidfit/googleTest/redirect.php';

//Call Google API
$gClient = new Google_Client();
$gClient->setApplicationName('Login to CodexWorld.com');
$gClient->setClientId($clientId);
$gClient->setClientSecret($clientSecret);
$gClient->setRedirectUri($redirectURL);

$google_oauthV2 = new Google_Oauth2Service($gClient);
?>
