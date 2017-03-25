<?php
require 'wp-includes/class.DBConnector.php';
require 'wp-includes/pluggable.php';

$dbConnection = (new DBConnector)->createDbConnection();

/*session_start();
if(!isset($_SESSION['userid'])) {
	#die('Um dich zu dem Wettbewert anzumelden <a href="../login/?pre=rennanmeldung">einloggen</a>');
	header("Location: login/?pre=rennanmeldung");
}*/
if(isset($_GET['code'])) 
{
	confirmTeampartnerParticipant($dbConnection);
	die();
}
else if(isset($_GET['postMessage'])) 
{	
	processRaceRegistration($dbConnection);
	die();
}
else if(isset($_GET['furterData'])) 
{	
	processFurtherDataRegistrationInDb($dbConnection);
	die();
}

$dbConnection->close();
//------------------
function confirmTeampartnerParticipant($dbConnection)
{	
	$code = $_GET['code'];

	echo "Hallo, schön, dass du der Einladung gefolgt bist.<br>
	Um deine Anmdung durchzuführen gebe bitte deine E-Mail-Adresse ein:<br>";
	
	echo '<form action="?postMessage=1" method="post">
	E-Mail:<br>
	<input type="text" size="40" maxlength="250" name="ownEmail2"><br><br>

	<input type="hidden" name="codeCopy" value="'.$code.'">

	<input type="submit" value="Weiter / Anmeldung abschicken">';

}

function updateDbWithSecondUser($dbConnection)
{
	$email = $_POST['ownEmail2'];
	//echo "Called updateDbWithSecondUser. Mail:".$email."Code: ".$_POST['codeCopy'];

	$raceQuery = $dbConnection->query("SELECT ID FROM `teilnehmer` WHERE email = '$email'");
	$user = $raceQuery->fetch_assoc();
	$userID = $user['ID'];
	
	$code = $_POST['codeCopy'];
	$query = "UPDATE registrations 
					  Set confirmed='1', Teilnehmer2_ID='$userID', Teilnehmer2_Email=''
					  WHERE code = '$code'";
	$statement = $dbConnection->query($query);

	if($statement) 
	{		
		echo "Hiermit hast du deine Einladung zum Rennen bestaetigt.";
		
		$participent1Query = $dbConnection->query("SELECT * FROM registrations, teilnehmer WHERE registrations.Teilnehmer1_ID = teilnehmer.ID AND code = '$code'");
		if ($participent1Query->num_rows > 0) 
		{
			$participent1 = $participent1Query->fetch_assoc();
			sendMail($participent1, "", $dbConnection, "", "");
		}
	} 
	else 
	{
		echo 'Beim Bestaetigen ist leider ein Fehler aufgetreten. Bitte wende dich zur Klaering an sporttrampen@gmail.com.<br>';
		return 0;
	}
	return 1;
}
function processRaceRegistration($dbConnection)
{
	$OK = 0;
	$securityCodeForTeampartner = generateRandomString();
	$new = false;
	$email = $_POST['ownEmail'];
	if ($email == "") 
	{
		$email = $_POST['ownEmail2'];
	}

	if($_POST['new'] != 1 && participantIsNew($dbConnection, $email))
	{
		echo "<b>ERROR:</b> Du hast angegeben, dass du bereits bei einem Rennen mitgemacht hast.<br> Deine E-Mail-Adresse befindet sich allerdings noch nicht in der Datenbank.<br>
			Das bedeutet, dass du zuvor eine andere E-Mail-Adresse zur Anmeldung genutzt hast. Bitte <a href='../rennanmeldung'>wiederhole</a> die Anmeldung mit der korrekten Adresse.<br><br>
			Du hast vergessen mit welcher Adresse du dich angemeldet hast oder möchtest dich mit einer anderen Adresse anmelden? Dann helfen wir dir gerne per <a href='mailto:sporttrampen@gmail.com'>Mail</a> weiter.";
		die();
	}

	if (participantIsNew($dbConnection, $email)) 
	{
		insertEMailInParticipantDB($dbConnection, $email);
		$new = true;
	}

	checkDoubleRegistration($dbConnection, $email);

	if ($_POST['ownEmail2'] != "") 
	{
		$OK += updateDbWithSecondUser($dbConnection);
	}
	else
	{
		$OK += insertRaceRegistrationIntoDB($_POST["race_id"], $_POST["team"], $_POST["selection"], $_POST["emailaddress"], $dbConnection, $securityCodeForTeampartner, $email);
	}
	
	$participant1 = getParticipantObject($dbConnection, $_SESSION['userid']);
	
	if ($_POST["team"] == "single") 
	{
		$OK++;
	}
	else //If team
	{
		$OK += sendMail($participant1, $_POST["emailaddress"], $dbConnection, $securityCodeForTeampartner, $_POST["race_id"]);
		if($OK == 2)
		{
			echo "Dein Teampartner hat soeben eine E-Mail bekommen und muss die Einladung noch bestaetigen<br>";
		}
	}

		
	$OK += sendMail($participant1, $email, $dbConnection, '0', $_POST["race_id"]);	//Confirmationsmail for participant and sporttrampen@gmail.com
	
	if($OK == 3)
	{	
		echo "Deine Anmeldung wurde erfolgreich abgeschickt.<br><br>";
		if ($new) 
		{
			echo "Herzlich Willkommen bei deinem ersten Rennen hier bei der DTSG. Wir freuen uns schon dich kennen zu lernen.<br><br>
			Um deine Anmeldung Abzuschließen ergänze bitte noch folgende Daten:<br>";
			echo '<form action="?furterData" method="post">
			Vorname:<br>
			<input type="text" size="40" maxlength="30" name="firstname"><br><br>

			Nachname:<br>
			<input type="text" size="40" maxlength="30" name="lastname"><br><br>

			Handynummer:<br>
			<input type="text" size="40" maxlength="20" name="mobilenumber"><br><br>

			<input type="hidden" name="email" value="'.$email.'">
 
			<input type="submit" value="Abschicken">';
		}

		/*die("Zur Bestätigung haben wir dir soeben eine E-Mail geschickt.<br> Zurueck zum <a href='../intern'>Internen bereich</a>");*/
	}
	else
	{
		die("ERROR: Bei deiner Anmeldung ist etwas schief gegangen. Bitte wende dich an sporttrampen@gmail.com");
	}
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function checkDoubleRegistration($dbConnection, $email)
{
	//$query = "SELECT 'ID' FROM `teilnehmer` WHERE email = '$email'";
	$raceQuery = $dbConnection->query("SELECT ID FROM `teilnehmer` WHERE email = '$email'");
	$user = $raceQuery->fetch_assoc();
	$userId = $user['ID'];

	$raceId = $_POST["race_id"];
	$query = "SELECT * FROM `registrations` WHERE (`Teilnehmer1_ID` = '$userId' || `Teilnehmer2_ID` = '$userId') && Event_ID = '$raceId'";
	$participent2Query = $dbConnection->query($query);

	if ($participent2Query->num_rows > 0) 
	{
		die("Du bist bereits fuer das Rennen angemeldet und kannst dich kein weiteres mal anmelden. Weiter zum <a href='../intern'>internen Bereich</a>");
	}
}

function participantIsNew($dbConnection, $email)
{
	$query = "SELECT * FROM teilnehmer WHERE email = '$email'";
	$statement = $dbConnection->query($query);
	if ($statement->num_rows > 0) 
	{
		return false;
	}
	return true;
}

function insertEMailInParticipantDB($dbConnection, $email)
{
	$passwort_hash = password_hash(generateRandomString($length = 10), PASSWORD_DEFAULT);
	$securitycode = generateRandomString();

	$query = "INSERT INTO teilnehmer (email, passwort, securitycode) VALUES ('$email', '$passwort_hash', '$securitycode')";
	$statement = $dbConnection->query($query);//
	
	if($statement) {
		return 1;
	} else {
		return 0;
	}
}



function insertRaceRegistrationIntoDB($raceID, $isTeam, $partnerID, $partnerMail, $dbConnection, $code, $email)
{
	$raceQuery = $dbConnection->query("SELECT ID FROM `teilnehmer` WHERE email = '$email'");
	$user = $raceQuery->fetch_assoc();
	$userId = $user['ID'];

	if($isTeam == "team")
	{
		$query = "INSERT INTO registrations (Event_ID, Teilnehmer1_ID,  Teilnehmer2_ID, Teilnehmer2_Email, code) VALUES ('$raceID', '$userId', '$partnerID', '$partnerMail', '$code')";
	}
	else
	{
		$query = "INSERT INTO registrations (Event_ID, Teilnehmer1_ID) VALUES ('$raceID', '$userId')";
	}
	$result = $dbConnection->query($query);//
	if($result) {		
		return 1;
	} else {
		echo 'ERROR: Beim Abspeichern in die Datenbank ist leider ein Fehler aufgetreten<br>';
		return 0;
	}
}

function getParticipantObject($dbConnection, $participantID)
{
	$participent2Query = $dbConnection->query("SELECT * FROM teilnehmer WHERE ID = '$participantID'");
	if ($participent2Query->num_rows > 0) 
	{
		return $participent2Query->fetch_assoc();
	}
	return false;
}

function wpdocs_set_html_mail_content_type() 
{
	return 'text/html';
}


function sendMail($participent1, $address, $dbConnection, $code, $raceID)
{
	$racesubject = "";
	if($code != "")
	{
		$race = getRaceObject($dbConnection, $raceID);
		$startdate = (new DateTime($race['Startdate']))->format('d.m.Y');
		$enddate = (new DateTime($race['Enddate']))->format('d.m.Y');
		$racesubject = $race['Name']." ($startdate - $enddate)";
	}
	else
	{
		$address = $participent1["email"];
	}
	#$from = $participent1['email'];
	$name = $participent1['name'];
	if ($name == "") 
	{
		$name = $participent1["email"];
	}

	if($code == '0')
	{
		$subject = "Anmeldebestätigung zum DTSG Rennen $racesubject";
		$msg = createHTMLConfirmation($name);
		
		add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
		wp_mail($address, $subject, $msg);
		remove_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
	}
	else if($code != "")
	{
		$subject = "Einladung zum DTSG-Rennen $racesubject";
		$msg = createHTMLInvitation($name, $code);
		
		add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
		wp_mail($address, $subject, $msg);
		remove_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
	}
	else
	{
		$subject = "Registrierungsbestaetigung deines Teampartners";
		$msg = "Hallo $name,<br>Dein Teampartner hat soeben deine Renneinladung bestätigt.<br><br>Deine DTSG";
		add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
		wp_mail($address, $subject, $msg);
		remove_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
	}

	add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
	wp_mail("sporttrampen@gmail.com", "Registrierung eines Teilnehmers", "Soeben hat sich ".$name." oder sein Teampartner für das Rennen: ".$race['Name']."angemeldet.<br>Kontakt: \"".$address."\"");
	remove_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );

	return 1;
}

function getRaceObject($dbConnection, $raceID)
{
	$raceQuery = $dbConnection->query("SELECT * FROM  event WHERE ID = '$raceID'");
	if ($raceQuery->num_rows > 0) 
	{
		return $raceQuery->fetch_assoc();
	}
}

function createHTMLInvitation($name, $code)
{
	return "Hallo,<br>
					zu wurdest soeben von $name zum DTSG-Rennen eingeladen.<br>
					<br>
					Um die Anmeldung zu bestätigen folge bitte diesem <a href='http://sporttrampen.de/rennanmeldung/?code=$code'>link</a>.<br>
					Manueller Link: http://sporttrampen.de/rennanmeldung/?code=$code<br>
					<br>
					Vielen Dank und bis bald.<br>
					<br>
					<br>
					Deine DTSG";
}

function createHTMLConfirmation($name)
{
	return "Hallo $name,<br>
					dies ist eine Anmeldebestätigung für das DTSG Rennen.<br>
					Wir freuen uns auf das Rennen und deine Teilnahme.<br>
					<br>
					Wir sehen uns beim Rennen.<br>
					<br>
					<br>
					Deine DTSG";
}

function processFurtherDataRegistrationInDb($dbConnection)
{
	$firstname = $_POST["firstname"];
	$lastname = $_POST["lastname"];
	$email = $_POST["email"];

	$mobilenumber = $_POST["mobilenumber"];
	$query = "UPDATE teilnehmer 
					  Set mobilenumber='$mobilenumber', name = '$firstname', lastname = '$lastname'
					  WHERE email = '$email'";
	$statement = $dbConnection->query($query);
	if($statement)
	{
		die("Vielen Dank. Deine Daten wurden verfollständigt.");
	}
	die("Fehler beim speichern deiner Daten.");
}
?>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.10.2.js"></script>
		<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
		<script>		
			function includeRemoveTeampartner(team, teilnehmerIDs, teilnehmerNames) 
			{
				while (infotext0.hasChildNodes()) 
				{
					infotext0.removeChild(infotext0.lastChild);
					//infotext1.removeChild(infotext1.lastChild);
					infotext2.removeChild(infotext2.lastChild);
					infotext3.removeChild(infotext3.lastChild);
					email.removeChild(email.lastChild);
			  	}
			  	while (selection.hasChildNodes()) 
					{
					  selection.removeChild(selection.lastChild);
			  	}
			  	while (email.hasChildNodes()) 
					{
					  email.removeChild(email.lastChild);
			  	}
			  	if(team.value == 'team')
		  		{
					infotext0.appendChild(document.createTextNode("Schoen, dass du bereits eine(n) Teampartner(in) hast..."));
					infotext2.appendChild(document.createTextNode("Bitte schick ihm/ihr eine E-Mail Einladung zum Rennen"));
					var mailDiv=document.createElement('div');
					mailDiv.innerHTML = "<input type='text' size='40' maxlength='250' name='emailaddress'>";
					document.getElementById('email').appendChild(mailDiv);
					infotext3.appendChild(document.createTextNode("E-Mail unbekannt? Dann gib deine eigene Adresse ein und leite den Anmeldelink weiter (z.B.: via Facebook)"));
				}
			}
			function disablePartnerEmail(partnerID)
			{
				while (email.hasChildNodes()) 
				{
				  email.removeChild(email.lastChild);
		  	}
		  	var mailDiv=document.createElement('div');
		  	if(partnerID.value == '0')
		  	{
		  		mailDiv.innerHTML = "<input type='text' size='40' maxlength='250' name='emailaddress'>";
		  	}
		  	else
		  	{
		  		mailDiv.innerHTML = "<input type='text' size='40' maxlength='250' name='deactivatedEmail' value='DEAKTIVIERT, da Partner ausgewaehlt' disabled onclick=alertError()>";
		  	}
				document.getElementById('email').appendChild(mailDiv);
			}
		</script>
		<div id="con">
			<form action="?postMessage=1" method="post">
				<p>Wir freuen uns, dass du dich für ein Rennen anmelden möchtest.
				</br>Deine E-Mail-Adresse:</p>
				<input type='text' size='40' maxlength='250' name='ownEmail'>
				<input type="checkbox" name="new" value="1">Dies ist das erste Mal, dass ich bei der DTSG mitmache.

				<p>Bitte wähle das Rennen aus:</p>
				<select name="race_id" id="race_id">
					<?php
						$dbConnection = (new DBConnector)->createDbConnection();
						$query = "SELECT * FROM event where (eventtype = 1 || eventtype = 2) && Enddate > CURRENT_TIMESTAMP order by Startdate";
						$statement = $dbConnection->query($query);
						while($event = $statement->fetch_assoc()) 
						{					
							if($eventType["ID"] == 99)
								continue;
							echo '<option value="'. $event["ID"] .'">'. $event["Name"] .'</option>';
						}
						$query = "SELECT ID, name, email FROM teilnehmer ORDER BY name";
						$statement = $dbConnection->query($query);
						while($event = $statement->fetch_assoc()) 
						{					
							$teilnehmerID[] = $event["ID"];
							$teilnehmerName[] = $event["name"];
						}
							echo "<script>\n";
							echo "var javascript_teilnehmerIDs = ". json_encode($teilnehmerID) . ";\n";
							echo "var javascript_teilnehmerNames= ". json_encode($teilnehmerName) . ";\n";
							echo "</script>\n";
					?>
				</select>
				<p>Hast du bereits einen Teampartner?</p>
				<select name="team" id="team" onchange="includeRemoveTeampartner(this, javascript_teilnehmerIDs, javascript_teilnehmerNames)">
					<option value='single'>Nein, ich möchte mich alleine anmelden</option>
					<option value='team'>Ja, ich habe bereits einen Teampartner</option>
				</select>
				<div id="infotext0"></div>
				<!--<div id="infotext1"></div>-->
				<div id="selection"></div>
				<div id="infotext2"></div>
				<div id="email"></div>
				<div id="infotext3"></div>
		 		<br>
				<br>
				<input type="submit" value="Weiter / Anmeldung abschicken">
			</form> 
		</div>
