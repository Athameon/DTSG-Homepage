<?php
require 'wp-includes/class.DBConnector.php';
$dbConnection = (new DBConnector)->createDbConnection();

session_start();
if(!isset($_SESSION['userid'])) 
{
	die('Bitte zuerst <a href="login.php">einloggen</a>');
}
else
{
	$currentUser = getParticipantObject($dbConnection, $_SESSION['userid']);
	if($currentUser["isMember"] == 0)
	{
		die("Du bist nicht berechtigt ein Rennen zu beenden.");
	}
}
if(isset($_GET['finishRace']))
{
	storeResultInDB($dbConnection);
}

function getParticipantObject($dbConnection, $participantID)
{
	$participent2Query = $dbConnection->query("SELECT * FROM teilnehmer WHERE ID = '$participantID'");
	if ($participent2Query->num_rows > 0) 
	{
		return $participent2Query->fetch_assoc();
	}
}

function storeResultInDB($dbConnection)
{
	$team = $_POST['team'];
	$finished = $_POST['finished'] == "on"? 1 : 0;
	$finishTime = $_POST['finishTime'];
	echo $team."<br>";
	echo $finished."<br>";
	echo $finishTime."<br>";

	$query = "UPDATE result 
			  Set Result='$finishTime', Finisher = '$finished'
			  WHERE ID = '$team'";
	$statement = $dbConnection->query($query);
	if($statement)
	{
		echo "Update erfolgreich";
	}
}
?>
<?php 
if(isset($errorMessage)) {
	echo $errorMessage;
}
?>
<link rel="stylesheet" media="all" type="text/css" href="http://code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" href="lib/jquery-ui-timepicker-addon.css">
<script type="text/javascript" src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
<script type="text/javascript" src="http://code.jquery.com/ui/1.11.0/jquery-ui.min.js"></script>
<script src="lib/jquery-ui-timepicker-addon.js"></script>

<script>

	$(function() {
	  $('#datetimepicker').datetimepicker({
		controlType: myControl,
		dateFormat: 'yy-m-d',
	});
	});
	var myControl=  {
		create: function(tp_inst, obj, unit, val, min, max, step){
			$('<input class="ui-timepicker-input" value="'+val+'" style="width:50%">')
				.appendTo(obj)
				.spinner({
					min: min,
					max: max,
					step: step,
					change: function(e,ui){ // key events
							// don't call if api was used and not key press
							if(e.originalEvent !== undefined)
								tp_inst._onTimeChange();
							tp_inst._onSelectHandler();
						},
					spin: function(e,ui){ // spin events
							tp_inst.control.value(tp_inst, obj, unit, ui.value);
							tp_inst._onTimeChange();
							tp_inst._onSelectHandler();
						}
				});
			return obj;
		},
		options: function(tp_inst, obj, unit, opts, val){
			if(typeof(opts) == 'string' && val !== undefined)
				return obj.find('.ui-timepicker-input').spinner(opts, val);
			return obj.find('.ui-timepicker-input').spinner(opts);
		},
		value: function(tp_inst, obj, unit, val){
			if(val !== undefined)
				return obj.find('.ui-timepicker-input').spinner('value', val);
			return obj.find('.ui-timepicker-input').spinner('value');
		}
	};
</script>
<?php
	$dbConnection = (new DBConnector)->createDbConnection();
	$query = "SELECT Teamname, ID FROM result WHERE Event_ID = (SELECT max(Event_ID) FROM result)";
	$statement = $dbConnection->query($query);
	while($event = $statement->fetch_assoc()) 
	{
		
		$teamnames[$event["ID"]] = $event["Teamname"];
	}
	
	$query = "SELECT Name FROM event WHERE ID = (SELECT max(Event_ID) FROM result)";
	$statement = $dbConnection->query($query);
	if($event = $statement->fetch_assoc()) 
	{					
		$eventName = $event["Name"];
		
	}
?>
Hiermit setzt du die Ergebnisse fuer das Rennen: <h3><?php echo $eventName?> </h3>
Hinweis: Jedes Team wird einzeln gespeichert und<br>
es werden nur Teams mit beendetem Rennen erfasst.<br>
<br>
<form action="?finishRace=1" method="post">
	Teamname:
	<select name="team">
		<?php
		foreach ($teamnames as $id => $name) {
			echo "<option value=$id>$name</option>";
		}
		?>
	</select><br>
	<br>				 
	 Rennen gueltig abgeschlossen:
	 <input type="checkbox" name="finished" checked><br>
	 <br>
	Zielzeit:
	<input type="text" class="datetimepicker" id="datetimepicker" size="20"  maxlength="10" name="finishTime"><br>
	<br>
	<input type="submit" value="Team speichern">
</form> 
