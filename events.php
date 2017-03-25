<?php
require 'wp-includes/class.DBConnector.php';

function printEvents($isFuture)
{
	$events = getEventObject();
	$today = (new DateTime());//->format('d.m.Y');
	
	foreach($events as $event)
	{
		$startdate = (new DateTime($event['Startdate']));//->format('d.m.Y');
		$enddate = (new DateTime($event['Enddate']));//->format('d.m.Y');
		
		$eventname = $event['eventtype'] == 1 ? "<b>Rennen:</b> ".$event['Name'] : $event['Name'];
		$eventlink = $event['link'];
		
		if($isFuture && $startdate < $today)
		{
			return;
		}
		if(!$isFuture && $startdate > $today)
		{
			continue;
		}
		echo "<tr><td>".$startdate->format('d.m.Y')."</td><td>-</td><td>".$enddate->format('d.m.Y')."</td><td><a target='_parent' href='$eventlink'>$eventname</a></td></tr>\n";
	}
}

function getEventObject()
{
	$dbConnection = (new DBConnector)->createDbConnection();
	
	$raceQuery = $dbConnection->query("SELECT * FROM  event WHERE eventtype IN('1', '3', '6') ORDER BY Startdate desc");
	while ($event = $raceQuery->fetch_assoc()) 
	{
		 $events[] = $event;
	}
	return $events;
}

?>
<!DOCTYPE html> 
<html> 
	<head>
		<title>Veranstaltungen</title>	
		<meta charset="utf-8"/>
	</head> 
	<body>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
		<div id="futureEvents">
			<h2>Zukünftige Veranstaltungen</h2>
			<table><body>
			<?php
				printEvents(true);
			?>
			</body></table
		</div>
		<div id="pastEvents">
			<br><br>
			<h2>Vergangene Veranstaltungen</h2>
			<table><body>
				<?php
					printEvents(false);
				?>
			</body></table
		</div>
	</body>
</html>
