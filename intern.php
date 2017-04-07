<?php
require 'wp-includes/class.DBConnector.php';

session_start();
if(!isset($_SESSION['userid'])) {
	die('Bitte zuerst <a href="../login">einloggen</a>');
}

if(isset($_GET['logout']))
{
	session_destroy();
	 
	echo "Du hast dich erfolgreich ausgeloggt<br><br>";
	die("Weiter zum <a href='../login'>login</a>");
}

$dbConnection = (new DBConnector)->createDbConnection();

if (isset($_GET['updatelist'])) 
{
	updateTeampartner($_POST['firstParticipant'], $_POST['secondParticipant'], $_POST['eventId']);
}
if (isset($_GET['insertParticipant'])) 
{
	insertParticipantInDB($dbConnection, $_POST['eventId'], $_POST['participantId']);
}
if(isset($_GET['teamanmelden']))
{
	if ($_POST['inResult'] == '1') 
	{
		addTeamToResultTable($dbConnection, $_POST['registrationId']);
	}
	else
	{
		setTeamname($dbConnection, $_POST['registrationId'], $_POST['teamname']);
	}
}
if(isset($_GET['startRace']))
{
	$eventId = $_POST['event_id'];
	$raceQuery = $dbConnection->query("SELECT * FROM registrations where Event_ID = $eventId");
	while($event = $raceQuery->fetch_assoc())
	{
		//Test whether the curreent team is already in the result table for the current race
		$teamname = $event['Teamname'];
		$Teilnehmer1_ID = $event['Teilnehmer1_ID'];
		$Teilnehmer2_ID = $event['Teilnehmer2_ID'];
		$countText = $dbConnection->query("SELECT count(*) as count 
											FROM result 
											where Event_ID = $eventId AND 
											Teilnehmer1_ID = $Teilnehmer1_ID AND 
											Teilnehmer2_ID = $Teilnehmer2_ID");
		$result = $countText->fetch_assoc();
		if ($result['count'] == 0) 
		{
			$query = "INSERT INTO result (Teamname, Teilnehmer1_ID, Teilnehmer2_ID, Event_ID) VALUES ('$teamname', '$Teilnehmer1_ID', '$Teilnehmer2_ID', '$eventId')";
			$dbConnection->query($query);
		}
		else
		{
			$query = "UPDATE result 
						  Set Teamname='$teamname'
						  WHERE Event_ID = $eventId AND 
						  		Teilnehmer1_ID = $Teilnehmer1_ID AND 
						  		Teilnehmer2_ID = $Teilnehmer2_ID";
			$statement = $dbConnection->query($query);
		}

	}
}
if (isset($_GET['updateTeamnames']))
{
	//echo file_get_contents('php://input');
	echo $_POST['teams']."<br><br>";
	$message = $_POST['teams'];
	// Liefert: <body text='schwarz'>
	$bodytag1 = str_replace("\\", "", $message);
	//$bodytag2 = str_replace("\’", "'", $bodytag1);
	//$bodytag = str_replace("\‘", "'", $bodytag2);
	echo $bodytag1."<br>";
	$testdata = "[{'id': 110, 'teamname': 'first'}, {'id': 140, 'teamname': 'second'}]";
	echo "test: ".$testdata;
	$data = json_decode($testdata, true);

	echo $data[0]['id'];
	//$message2 = substr($message, 3);
	//$message3 = substr($message2, -3);
	//echo "%27".$message3."%27";
	//echo json_decode($_POST['teams']);
	echo "<br>Update Teamname";
	//echo $_POST['teams'];
	//$json = file_get_contents('php://input');
	//echo unescape($message);
	$data = json_decode($bodytag, true);
	//echo $data;
	//print_r($data);
	//echo $data;
	//var_dump($_POST['teams']);
	$data[0]['id'];
}
 
//Abfrage der Nutzer ID vom Login
$userid = $_SESSION['userid'];

$raceQuery = $dbConnection->query("SELECT * FROM teilnehmer WHERE ID = '$userid'");
if ($raceQuery->num_rows > 0) 
{
	$user = $raceQuery->fetch_assoc();

	$name = $user['name'];
	
	if($user['activated'] == '0')
	{
		activateAccount($dbConnection, $userid);
	}
	echo "Hallo $name,<br>hier im Mitgliedsbereich kannst du je nach Mitgliedsstatus einige administrative Dinge vornehmen:<br><br>";
} 
else 
{
	echo "Error: ID konnte in der DB nicht gefunden werden";
}

if(isset($_GET['changeMailPW'])) 
{
	changeMailPassword();
}
if(isset($_GET['changeSettings'])) 
{
	changeProfileSettings();
}


$dbConnection->close();

//----------------
function activateAccount($dbConnection, $userid)
{
	$query = "UPDATE teilnehmer 
					  Set activated='1'
					  WHERE ID = '$userid'";
	$statement = $dbConnection->query($query);
}

function printParticipentsOfFollowingEvents()
{
	$dbConnection = (new DBConnector)->createDbConnection();

	$query = "SELECT event.ID as eventID, event.Name as eventName, 
						event.Startdate as startdate,
						registrations.teamname as teamname, 
						registrations.Teilnehmer1_ID as teilnehmer1, 
						registrations.Teilnehmer2_ID as teilnehmer2, 
						registrations.Teilnehmer2_Email as teilnehmer2Unconfirmed,
						registrations.ID as registrationId,
						registrations.inResult as inResult
					from event, registrations
			  		where event.ID = registrations.Event_ID AND
			  				event.Enddate >= CURRENT_TIMESTAMP";
	$statement = $dbConnection->query($query);

	while($event = $statement->fetch_assoc()) 
	{					
		$statement2 = $dbConnection->query($query);
		$eventname = $event["eventName"];
		//$teamname = $event["teamname"];
		$participent1Object = getParticipantObject($dbConnection, $event["teilnehmer1"]);
		$participent2Object = getParticipantObject($dbConnection, $event["teilnehmer2"]);

		echo "<tr>
				<td>$eventname
				<form action='?teamanmelden' method='post'>
					<input type='text' id='".$event["registrationId"]."' name='teamname' value='".$event["teamname"]."' onfocusout='setTeamnames(".$event["registrationId"].", this)'>
					<input type='hidden' name='registrationId' value='".$event["registrationId"]."'>
					<input type='hidden' name='inResult' value='".$event["inResult"]."'>
				</td>
				<td><a href='mailto:".$participent1Object["email"]."'>".$participent1Object["name"]."</a><br>".$participent1Object["email"]."<br>".$participent1Object["mobilenumber"]."</td>
				<td><a href='mailto:".$participent2Object["email"]."'>".$participent2Object["name"]."</a><br>".$participent2Object["email"]."<br>".$participent2Object["mobilenumber"]."<br>
					<select name='team' id='team' onchange='includeRemoveTeampartner(".$participent1Object["ID"].", this, ".$event["eventID"].")'>
						<option value='0'>---</option>";
						while ($event2 = $statement2->fetch_assoc()) 
						{
							$participent1Object2 = getParticipantObject($dbConnection, $event2["teilnehmer1"]);
							$participent2Object2 = getParticipantObject($dbConnection, $event2["teilnehmer2"]);
							echo "<option value='".$event2["teilnehmer1"]."'>".$participent1Object2["name"]."</option>";
							if ($event2["teilnehmer2"] != 0) {
								echo "<option value='".$event2["teilnehmer2"]."'>".$participent2Object2["name"]."</option>";
							}
						}
					echo "<option value='0'>--delete--</option>
					</select>
				<i>".$event["teilnehmer2Unconfirmed"]."</i></td>
			</tr>\n";	
	}
}

function getParticipantObject($dbConnection, $participantID)
{
	$participent2Query = $dbConnection->query("SELECT * FROM teilnehmer WHERE ID = '$participantID'");
	if ($participent2Query->num_rows > 0) 
	{
		return $participent2Query->fetch_assoc();
	}
}

function changeMailPassword()
{
	$dbConnection = (new DBConnector)->createDbConnection();

	$oldpasswort = $_POST['oldPW'];
	$passwort = $_POST['newPW'];
	$passwort2 = $_POST['newPW2'];
	$email = $_POST['emailaddress'];
	$userid = $_SESSION['userid'];

	if (strlen($oldpasswort) == 0 && strlen($password) == 0 && strlen($passwort2) == 0 && strlen($email) == 0) 
	{
		header("Location: intern");
	}
	 
	if(strlen($passwort) == 0) 
	{
		$error = true;
		if (strlen($oldpasswort) != 0) 
		{
			echo 'Bitte ein neues Passwort angeben.<br>Moechtest du das Passwort nicht ändern, so gebe bitte kein altes Passwort ein.<br>';
		}
	}
	if($passwort != $passwort2) 
	{
		echo '<h2>Fehler:Die beiden neuen Passwoerter stimmen nicht ueberein!</h2><br>';
		$error = true;
	}

	if(strlen($email) != 0 )
	{
		updateEMailAddress($dbConnection, $email, $userid);
	}

	if(!$error) 
	{	
		updatePassword($dbConnection, $userid, $passwort, $oldpasswort);
	}
}

function updateEMailAddress($dbConnection, $email, $userid)
{
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) 
	{
	echo 'Bitte eine gültige E-Mail-Adresse eingeben<br>';
	$mailerror = true;
	}
	if(!$mailerror)
	{
		$selectquery = "SELECT email FROM `teilnehmer` WHERE email = '$email' AND ID != '$userid'";
		$statement = $dbConnection->query($selectquery);
		if($statement->fetch_assoc() )
		{
			echo "Die eingegebene E-Mail-Adresse ist bereits vergeben. Bitte waehle eine andere.<br>";
		}
		else
		{
			$query = "UPDATE teilnehmer 
						  Set email='$email'
						  WHERE ID = '$userid'";
			$statement = $dbConnection->query($query);
		
			if($statement) 
			{		
				echo "Deine E-Mail-Adresse wurde erfolgreich auf $email geändert.<br>";
				header("Location: intern");
			} 
			else 
			{
				echo 'Beim ändern ist leider ein Fehler aufgetreten. Bitte wende dich an sporttrampen(a)gmail.com.<br>';
			}
		}
	}
}

function updatePassword($dbConnection, $userid, $passwort, $oldpasswort)
{
	$passwort_hash = password_hash($passwort, PASSWORD_DEFAULT);

	$query = "SELECT * FROM teilnehmer WHERE ID = '$userid'";//Q7uzRoOdRn
	$statement = $dbConnection->query($query);
	$user = $statement->fetch_assoc();
	
	//Überprüfung des Passworts
	if ($user !== false && password_verify($oldpasswort, $user['passwort'])) 
	{			
		$query = "UPDATE teilnehmer 
						  Set passwort='$passwort_hash'
						  WHERE ID = '$userid'";
		$statement = $dbConnection->query($query);
	
		if($statement) 
		{		
			echo "Dein Passwort wurde erfolgreich geändert.";
			header("Location: intern");
		} 
		else 
		{
			echo 'Beim Ändern ist leider ein Fehler aufgetreten. Bitte wende dich an sporttrampen(a)gmail.com.<br>';
		}
	} 
	else 
	{
		echo "Dein altes Passwort ist false. Bitte gebe es erneut ein.<br>";
	}
}

function changeProfileSettings()
{
	$dbConnection = (new DBConnector)->createDbConnection();

	$receiveMessages = $_POST['receiveMessages']  == "on"? 1 : 0;
	$mobilenumber = $_POST['mobilenumber'];
	$description = $_POST['description'];
	$userid = $_SESSION['userid'];

	$query = "UPDATE teilnehmer 
					  Set mobilenumber='$mobilenumber', PnActivated = '$receiveMessages', description = '$description'
					  WHERE ID = '$userid'";
	$statement = $dbConnection->query($query);
	if($statement)
	{
		echo "Update erfolgreich";
		header("Location: intern");
	}

}

function updateTeampartner($firstParticipantId, $partnerId, $eventId)
{
	$dbConnection = (new DBConnector)->createDbConnection();

	//Input participant to teampartner
	$query = "UPDATE registrations 
					Set Teilnehmer2_ID='$partnerId'
					WHERE Event_ID = '$eventId' AND Teilnehmer1_ID = '$firstParticipantId'";
	$statement = $dbConnection->query($query);

	//Delete participant from first column
	$query = "UPDATE registrations 
					Set Teilnehmer1_ID='0'
					WHERE Event_ID = '$eventId' AND Teilnehmer1_ID = '$partnerId'";
	$statement = $dbConnection->query($query);

	//Delete participant from second column
	$query = "UPDATE registrations 
					Set Teilnehmer2_ID='0'
					WHERE Event_ID = '$eventId' AND (Teilnehmer1_ID = '$partnerId' OR (Teilnehmer2_ID = '$partnerId' AND Teilnehmer1_ID != '$firstParticipantId'))";
	$statement = $dbConnection->query($query);

	//Delete empty rows
	$query = "DELETE FROM registrations 
					WHERE Teilnehmer1_ID = '0' AND Teilnehmer2_ID = '0'";
	$statement = $dbConnection->query($query);
}

function insertParticipantInDB($dbConnection, $eventId, $articipantId)
{
	$query = "INSERT INTO registrations (Event_ID, Teilnehmer1_ID) VALUES ('$eventId', '$articipantId')";
	$statement = $dbConnection->query($query);
}

function addTeamToResultTable($dbConnection, $registrationId)
{
	echo "Add team to result table: registrationId: ".$registrations;
}

function setTeamname($dbConnection, $registrationId, $teamname)
{
	echo "Set teamname: registrationId:".$registrationId."; Teamname:".$teamname;
}
?>
	<script>
	function setTeamnames(registrationId, teem)
	{
		var teams = document.getElementsByName("teamname");
		var jsonstring ='[';
		for(var x = 0; x < teams.length; x++) 
		{    
		    jsonstring += '{"id": ' + teams[x].id + ', "teamname": "' + teams[x].value + '"}, ';
		}
		jsonstring = jsonstring.substring(0, jsonstring.length - 2);
		jsonstring +=']';
		alert(escape(JSON.stringify("[{'id': 110, 'teamname': 'first'}, {'id': 140, 'teamname': 'second'}]")));
        form = document.createElement("form"),
		node = document.createElement("input");
		var element1 = document.createElement("input");
		form.method = "POST";
		//element1.value = escape(JSON.stringify("[{'id': 110, 'teamname': 'first'}, {'id': 140, 'teamname': 'second'}]"));
		element1.value = "[{'id': 110, 'teamname': 'first'}, {'id': 140, 'teamname': 'second'}]";
		element1.name ="teams";
		form.appendChild(element1);  
		form.appendChild(node.cloneNode());
		form.action = "?updateTeamnames";
		form.style.display = "none";
		document.body.appendChild(form);
		form.submit();
		document.body.removeChild(form);
	}
	function includeRemoveTeampartner(firstParticipant, secondParticipant, eventId) 
	{
		form = document.createElement("form"),
		node = document.createElement("input");
		var element1 = document.createElement("input");
		form.method = "POST";
		element1.value=firstParticipant;
		element1.name="firstParticipant";
		form.appendChild(element1);  
    	var element2 = document.createElement("input");
		element2.value=secondParticipant.value;
		element2.name="secondParticipant";
		form.appendChild(element2);
    	var element3 = document.createElement("input");
		element3.value=eventId;
		element3.name="eventId";
		form.appendChild(element3);
		form.appendChild(node.cloneNode());
		form.action = "?updatelist";
		form.style.display = "none";
		document.body.appendChild(form);
		form.submit();
		document.body.removeChild(form);
	}
	function addParticipantToRace(eventId, participantId)
	{
		form = document.createElement("form"),
		node = document.createElement("input");
		var element1 = document.createElement("input");
		form.method = "POST";
		element1.value=eventId;
		element1.name="eventId";
		form.appendChild(element1);  
    	var element2 = document.createElement("input");
		element2.value=participantId.value;
		element2.name="participantId";
		form.appendChild(element2);
		form.appendChild(node.cloneNode());
		form.action = "?insertParticipant";
		form.style.display = "none";
		document.body.appendChild(form);
		form.submit();
		document.body.removeChild(form);
	}
	function displayChangeLoginDataToChange()
	{
		removeAllOpenFields();
		infoEMail.appendChild(document.createTextNode("E-Mail Adresse ändern"));
		var mailDiv=document.createElement('div');
		infoEMail2.appendChild(document.createTextNode("Neue E-Mail:"));
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='width:100%' type='text' maxlength='250' name='emailaddress'>";
		document.getElementById('inputEMail').appendChild(mailDiv);
		mailPasswordseparator.appendChild(document.createTextNode("------------------------------"));
		infoPW.appendChild(document.createTextNode("PASSWORT ändern:"));
		infoOldPW.appendChild(document.createTextNode("Altes Passwort:"));
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='width:100%' type='password' maxlength='250' name='oldPW'>";
		document.getElementById('inputOldPW').appendChild(mailDiv);
		infoNewPW.appendChild(document.createTextNode("Neues Passwort:"));
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='width:100%' type='password' maxlength='250' name='newPW'>";
		document.getElementById('inputNewPW').appendChild(mailDiv);
		infoNewPW2.appendChild(document.createTextNode("Neues Passwort wiederholen:"));
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='width:100%' type='password' maxlength='250' name='newPW2'>";
		document.getElementById('inputNewPW2').appendChild(mailDiv);
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='height:70px;' type='submit' value='Änderung(en) jetzt abschicken'>";
		document.getElementById('submitLogin').appendChild(mailDiv);
	}
	function displayFurtherSettings()
	{
		removeAllOpenFields();
		<?php 
		$dbConnection = (new DBConnector)->createDbConnection();
		$user = getParticipantObject($dbConnection, $_SESSION['userid']);
		
		$checked = $user['PnActivated'] == 1? "checked" : "";
		?>
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input type='checkbox'name='receiveMessages' <?php echo $checked ?> > Empfange PNs ueber die Ergebnisliste";
		document.getElementById('infoReceiveMessages').appendChild(mailDiv);
		infoMobileNumber.appendChild(document.createTextNode("Handynummer: "));
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='width:100%' maxlength='20' type='input'name='mobilenumber' value='<?php echo $user['mobilenumber'] ?>'>";
		document.getElementById('infoMobileNumber').appendChild(mailDiv);
		infoDescription.appendChild(document.createTextNode("Beschreibung: "));
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='width:100%' cols='50' rows='4' maxlength='250' type='input' name='description' value='<?php echo $user['description'] ?>'>";
		document.getElementById('infoDescription').appendChild(mailDiv);
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='height:70px;' type='submit' value='Änderung(en) jetzt abschicken'>";
		document.getElementById('submitLogin2').appendChild(mailDiv);
	}
	function startRace()
	{
		removeAllOpenFields();
		<?php 
			$dbConnection = (new DBConnector)->createDbConnection();
			//$selectquery = "SELECT count('id') from event where enddate > CURRENT_TIMESTAMP";
			$selectquery = "SELECT * FROM event where enddate > CURRENT_TIMESTAMP";
			$statement = $dbConnection->query($selectquery);
			//$event = $statement->fetch_assoc();
			/*if ($event['id'] == '1')
			{
				echo "only one event ...";
			}*/
			$selectiontext = "<select name='event_id' id='event_id'>";
			while($singleEvent = $statement->fetch_assoc())
			{
				$selectiontext = $selectiontext."<option value=".$singleEvent['ID'].">".$singleEvent['Name']."</option>";
			}
			$selectiontext = $selectiontext."</select>";
		?>
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<?php echo $selectiontext?>";
		document.getElementById('selectionHeadder').appendChild(mailDiv);
		var mailDiv=document.createElement('div');
		mailDiv.innerHTML = "<input style='height:70px;' type='submit' value='Rennen jetzt starten'>";
		document.getElementById('submitStartRace').appendChild(mailDiv);
	}
	function removeAllOpenFields()
	{
		while(infoEMail.hasChildNodes())
		{
			infoEMail.removeChild(infoEMail.lastChild);
			infoEMail2.removeChild(infoEMail2.lastChild);
			inputEMail.removeChild(inputEMail.lastChild);
			mailPasswordseparator.removeChild(mailPasswordseparator.lastChild);
			infoPW.removeChild(infoPW.lastChild);
			infoOldPW.removeChild(infoOldPW.lastChild);
			inputOldPW.removeChild(inputOldPW.lastChild);
			infoNewPW.removeChild(infoNewPW.lastChild);
			inputNewPW.removeChild(inputNewPW.lastChild);
			infoNewPW2.removeChild(infoNewPW2.lastChild);
			inputNewPW2.removeChild(inputNewPW2.lastChild);
			submitLogin.removeChild(submitLogin.lastChild);
		}
		while(infoReceiveMessages.hasChildNodes())
		{
			infoReceiveMessages.removeChild(infoReceiveMessages.lastChild);
			infoMobileNumber.removeChild(infoMobileNumber.lastChild);
			inputMobileNumber.removeChild(inputMobileNumber.lastChild);
			infoDescription.removeChild(infoDescription.lastChild);
			inputDescription.removeChild(inputDescription.lastChild);
			submitLogin2.removeChild(submitLogin2.lastChild);
		}
	}
	</script>

	<form action="?logout=1" method="post">
 		<input style="width:100%;height:50px" type="submit" value="Ausloggen" > <br>
	</form>
	<input style="width:100%;height:50px" type="submit" value="E-Mail oder Passwort ändern" onclick="displayChangeLoginDataToChange()">
	
	<form action="?changeMailPW=1" method="post">

		<div id="infoEMail"></div>
		<div id="infoEMail2"></div>
		<div id="inputEMail"></div>
		<div id="mailPasswordseparator"></div>
		<div id="infoPW"></div>
		<div id="infoOldPW"></div>
		<div id="inputOldPW"></div>
		<div id="infoNewPW"></div>
		<div id="inputNewPW"></div>
		<div id="infoNewPW2"></div>
		<div id="inputNewPW2"></div>
		<div id="submitLogin" style="text-align:center"></div>

	</form>
	<input style="width:100%;height:50px" type="submit" value="Profileinstellungen ändern" onclick="displayFurtherSettings()">

		<form action="?changeSettings=1" method="post">

		<div id="infoReceiveMessages"></div>
		<div id="infoMobileNumber"></div>
		<div id="inputMobileNumber"></div>
		<div id="infoDescription"></div>
		<div id="inputDescription"></div>
		<div id="submitLogin2" style="text-align:center"></div>

	</form>

	<form action="../rennanmeldung" method="post">
		<input style="width:100%;height:50px" type="submit" value="Zu einem Event/Rennen anmelden" > <br>
	</form>
	
	<?php if($user['isMember'])
	{ ?>

		<form action="../newPost" method="post">
 			<input style="width:100%;height:50px" type="submit" value="Eine Nachricht/Event auf der Homepage und dem E-Mail-Verteiler veroeffentlichen" > <br>
		</form>
		<!--<form action="../executeRace" method="post">-->
 			<input style="width:100%;height:50px" type="submit" value="Ein Rennen starten" onclick="startRace()">

		<form action="?startRace=1" method="post">
			<div id="selectionHeadder"></div>
			<div id="submitStartRace" style="text-align:center"></div>
		</form>
		<form action="../finishRace" method="post">
 			<input style="width:100%;height:50px" type="submit" value="Ein Rennen Abschließe" > <br>
		</form>
		<br>
		<div style="text-align:center">
			Fuer das folgende Rennen/Event gibt es bereits folgende Anmeldungen:<br><br>
			<table align="center"><body>
				<tr>
					<td><b>Event</b></td>
					<td><b>Teilnehmer 1</b></td>
					<td><b>Teilnehmer 2</b></td>
				</tr>
				<?php 
					printParticipentsOfFollowingEvents();
					$dbConnection = (new DBConnector)->createDbConnection();

					$query = "SELECT * from event
							  where Enddate >= CURRENT_TIMESTAMP";
					$statement = $dbConnection->query($query);
					$event = $statement->fetch_assoc();
					$nextEventId = $event["ID"];

					$query = "SELECT * from teilnehmer";
					$statement = $dbConnection->query($query);

					echo "<tr><td></td><td>";
					echo "<select name='test' id='test' onchange='addParticipantToRace($nextEventId, this)'>";
					echo "<option value='0'>---</option>";
					while ($teilnehmer = $statement->fetch_assoc()) 
					{
						echo "<option value='".$teilnehmer["ID"]."'>".$teilnehmer["name"]." ".$teilnehmer["lastname"]."</option>";
					}
					echo "</select>";
					echo "</td></tr>";
				?>
			</body></table>
		</div>
	<?php } ?>	