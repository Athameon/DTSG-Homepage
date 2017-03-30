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
						registrations.ID as registrationId
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
					<input type='text' name='teamname' value='".$event["teamname"]."'>
					<input type='hidden' name='registrationId' value='".$event["registrationId"]."'>
					<input type='submit' value='Team anmelden'>
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
?>
	<script>
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
		<form action="../executeRace" method="post">
 			<input style="width:100%;height:50px" type="submit" value="Ein Rennen Starten" > <br>
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