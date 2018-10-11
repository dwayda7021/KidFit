<html>
 <head>
  <title>Registration page</title>
 </head>
 <body>

<?php 
/* Redirect Browser */
	ob_start();
	header("Location: https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=228NH4&redirect_uri=http%3A%2F%2Fkidsteam.boisestate.edu%2Fkidfit%2Fhandle_redirect.php&scope=activity%20heartrate%20location%20nutrition%20profile%20settings%20sleep%20social%20weight"); 
	
	ob_end_flush();
	exit();
?>
	
  </body>
</html>

