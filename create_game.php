 <?php

/*

create_game.php
input -- gameName, metric type
gameNameTypes --- hotPotatoAsync, hotPotatoCompetitive, escapeTheTunnelSyncCompetitive, escapeTheTunnelSyncCollaborative
metricType --- steps, activityTime


*/

$gameName = $_GET['gameName'];
$gameMetric = $_GET['metric'];


// ****** CONNECT TO THE DATABASE ***********

include "appConstants.php";
$currDate = date("Y-m-d H:i:s", strtotime("now"));
$todaysDate = date_create('now');
$todaysDate = date_format($todaysDate, 'Y-m-d');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if(!$gameName)
{
	$json_arr= array('error' => 'Please provide a game Name. Options are hotPotatoAsync, hotPotatoCompetitive, escapeTheTunnelSyncCompetitive, escapeTheTunnelSyncCollaborative');
	$json_data = json_encode($json_arr);
	echo $json_data;
	return; 
}

if(!$gameMetric)
{	
	$gameMetric = 'activityTime';
}

switch($gameMetric){
	case 'steps':
	case 'activityTime':
	break;
	default:
		$json_arr= array('error' => 'Invalid metric type. Options are steps and activityTime');
		$json_data = json_encode($json_arr);
	        echo $json_data;
        	return;
/*
if(!($gameMetric=='steps' )|| !($gameMetric =='activityTime'))
{
	$json_arr= array('error' => 'Invalid metric type. Options are steps and activityTime');
	$json_data = json_encode($json_arr);
        echo $json_data;
        return;

}

*/
}

switch($gameName)
{
	case "hotPotatoAsync": 
		$hotPotatoAsyncSearch = "SELECT * from games WHERE gameName = '$gameName' AND metric = '$gameMetric'";
		$hotPotatoAsyncData = $conn->query($hotPotatoAsyncSearch);
		if($hotPotatoAsyncData->num_rows<1)
		{
			$hotPotatoAsyncInsert = "INSERT INTO games (gameName, createDate, status, metric ) VALUES ('$gameName', '$currDate','TRUE','$gameMetric')";
			$hotPotatoAsyncInsertData = $conn->query($hotPotatoAsyncInsert);
			if($hotPotatoAsyncInsertData)
			{
				$json_arr= array('success' =>"hotPotatoAsync created successfully");	
			}
			else
			{
				$json_arr= array('error' => "hotPotatoAsync creation failed");
			}
		}	
		else
		{
			$json_arr = array('error' => 'game Already Exists');
		}
		$json_data = json_encode($json_arr);
		echo $json_data;
		break;
	case "hotPotatoCompetitive":
		$hotPotatoCompetitiveSearch =  "SELECT * from games WHERE gameName = '$gameName' AND metric = '$gameMetric'";
		$hotPotatoCompetitiveData = $conn->query($hotPotatoCompetitiveSearch);
		if($hotPotatoCompetitiveData->num_rows<1)
                {
			$hotPotatoCompetitiveInsert = "INSERT INTO games (gameName, createDate, status, metric ) VALUES ('$gameName', '$currDate','TRUE','$gameMetric')";
			$hotPotatoCompetitiveInsertData = $conn->query($hotPotatoCompetitiveInsert);
			if($hotPotatoCompetitiveInsertData)
			{
				$json_arr= array('success' =>"hotPotatoCompetitive created successfully");
                        }
                        else
                        {
				$json_arr= array('error' => "hotPotatoCompetitivecreation failed");
                        }
                }
                else
                {
                        $json_arr = array('error' => 'game Already Exists');
                }
		$json_data = json_encode($json_arr);
                echo $json_data;
                break;
	case "escapeTheTunnelSyncCompetitive":
		$escapeTheTunnelSyncCompetitiveSearch = "SELECT * from games WHERE gameName = '$gameName' AND metric = '$gameMetric'";
		$escapeTheTunnelSyncCompetitiveData = $conn->query($escapeTheTunnelSyncCompetitiveSearch);
		if($escapeTheTunnelSyncCompetitiveData->num_rows<1)
		{
			$escapeTheTunnelSyncCompetitiveInsert = "INSERT INTO games (gameName, createDate, status, metric ) VALUES ('$gameName', '$currDate','TRUE','$gameMetric')";
			$escapeTheTunnelSyncCompetitiveInsertData = $conn->query($escapeTheTunnelSyncCompetitiveInsert);
			if($escapeTheTunnelSyncCompetitiveInsertData)
			{
				$json_arr = array('success' => "escapeTheTunnelSyncCompetitive created successfully");
                        }
                        else
                        {
                               $json_arr= array('error' => "escapeTheTunnelSyncCompetitive creation failed");
			}
		}
		else
                {
                        $json_arr = array('error' => 'game Already Exists');
                }
                $json_data = json_encode($json_arr);
                echo $json_data;
                break;
	case "escapeTheTunnelSyncCollaborative":
		$escapeTheTunnelSyncCollaborativeSearch = "SELECT * from games WHERE gameName = '$gameName' AND metric = '$gameMetric'";
		$escapeTheTunnelSyncCollaborativeData = $conn->query($escapeTheTunnelSyncCollaborativeSearch);
		if($escapeTheTunnelSyncCollaborativeData->num_rows<1)
		{
			$escapeTheTunnelSyncCollaborativeInsert = "INSERT INTO games (gameName, createDate, status, metric ) VALUES ('$gameName', '$currDate','TRUE','$gameMetric')";
			$escapeTheTunnelSyncCollaborativeInsertData =  $conn->query($escapeTheTunnelSyncCollaborativeInsert);
			if($escapeTheTunnelSyncCollaborativeInsertData)
			{
				$json_arr = array('success' => "escapeTheTunnelSyncCollaborative  created successfully");
                        }
                        else
                        {
                                $json_arr= array('error' => "escapeTheTunnelSyncCollaborative creation failed");
                        }
                }
                else
                {
                        $json_arr = array('error' => 'game Already Exists');
                }
                $json_data = json_encode($json_arr);
                echo $json_data;
                break;
	default:
		$json_arr = array('Game type with this game name cannot be created ');
		$json_data = json_encode($json_arr);
		echo $json_data;
                break;

}













/*







// ************ HOT POTATO ***************
$potatoSearch = "SELECT * from games WHERE gameName ='hotPotatoAsync'";
$result = $conn->query($potatoSearch);
if($result->num_rows<1)
{
	$potatoGame = "INSERT INTO games (gameName, createDate, status, metric ) VALUES ('hotPotatoAsync', '$currDate','TRUE','activityMinutes')";
	if($conn->query($potatoGame)===TRUE)
	{
		echo " Hot Potato Game Asynchronous Created";
	}
	else
	{
		echo " Hot Potato Game Creation Failed";
	}
}

$potatoSearch = "SELECT * from games WHERE gameName ='hotPotatoCompetitive'";
$result = $conn->query($potatoSearch);
if($result->num_rows<1)
{
        $potatoGame = "INSERT INTO games (gameName, createDate, status, metric) VALUES ('hotPotatoCompetitive', '$currDate','TRUE','activityMinutes')";
        if($conn->query($potatoGame)===TRUE)
        {
                echo " Hot Potato Competitive Game Created";
        }
        else
        {
                echo " Hot Potato Game Creation Failed";
        }
}

$tunnelSearch = "SELECT * from games WHERE gameName = 'escapeTheTunnelSync'";
$result = $conn->query($tunnelSearch);
if($result->num_rows<1)
{
	$tunnelGame = "INSERT INTO games (gameName, createDate, status, metric) VALUES ('escapeTheTunnelSync','$currDate','TRUE','activityMinutes')";
	if($conn->query($tunnelGame)===TRUE)
        {
		echo "Escape the Tunnel Synchronous created";
	}
	else
	{
		echo "Escape the Tunnel Synchronous creation Failed";
	}
}

*/
?>
