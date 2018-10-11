<style>
<?php include 'CSS/indexStyle.css';
?> 
</style>

<?php
$request_uri = explode('?', $_SERVER['REQUEST_URI'], 2);
print_r($request_uri[0]);

// Route it up!

switch ($request_uri[0]) {
    // Home page
    case '/kidfit/':
        require 'register.php';
        break;
    // About page
    case '/kidfit/user_accept':
		echo "<p>Here I am ! </p>";
        require 'user_accept.php';
        break;
    // Get the user's steps
    case '/kidfit/fetch_user_steps':
	echo $request_uri[1];
	$FITBITID= '4ZXQZN';
	//header("Location: http://kidsteam.boisestate.edu/kidfit/fetch_user_steps.php?fitbitID={$fitbitID}");	
	//require 'fetch_user_steps.php';
	include 'fetch_user_steps.php';
// function call "GetUserStepsByID(passed-in-uer-id);
	echo "Hello User";
//	dispalyfitbitID($fitbitID);
	break;
    // Everything else
    default:
	echo "<p> Case Default</p>";
	echo $request_uri[0];
        header('HTTP/1.0 404 Not Found');
        require '404.php';
        break;
}
?>


