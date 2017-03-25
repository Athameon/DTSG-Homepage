<?php
require 'wp-includes/class.DBConnector.php';
require 'wp-includes/pluggable.php';

session_start();
$dbConnection = (new DBConnector)->createDbConnection();

displayTeilnehmerDropdown($dbConnection);


if(isset($_GET['message'])) 
{
	sendMail($dbConnection);
}

if(isset($_GET['userID']) && $_GET['userID'] != 0) 
{
	$userID = $_GET['userID'];
	$user = findNameOfParticipent($userID, $dbConnection);
	$name = $user['name'];
}
else
{
	die("Ungueltige Anfrage: Es wurde kein Teilnehmer ausgewaehlt");
}

$dbConnection->close();
//----------------------

function sendMail($dbConnection)
{
	$from = $_POST['sender'];
	$messagePreText = "Hallo, du hast eben eine Nachricht von '".$from."' bekommen.<br>Hier die Nachricht:<br>";
	$messageContent = $_POST['messageText'];
	$messagePostText = "<br><br>Diese Nachricht wurde ueber das Kontaktformuler der Deutschen Trampsport Gemeinschaft <a href='http://www.sporttrampen.de'>DTSG</a> geschickt";

	$messageText = $messagePreText.$messageContent.$messagePostText;
	$userID = $_POST['id'];
	$user = findNameOfParticipent($userID, $dbConnection);
	
	if ($user['email'] == "---" || $user['PnActivated'] == '0') {
		die("Der ausgewaehlte Teilnehmer hat keine Kontaktadresse hinterlegt oder moechte keine PNs empfangen.<br>Bitte schreibe uns ueber das allgemeine Kontaktformular.<br><br>Deine Eingegebene Nachricht:<br>'".$_POST['messageText']."'");
	}

	add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
	wp_mail($user['email'], "Nachricht vom DTSG Homepage-Kontaktformular", $messageText);
	remove_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
	
	die("Deine Nachricht wurde verschickt.<br> Zurueck zum <a href='teilnehmer/?userID=$userID'>Teilnehmer</a> oder zur <a href='Ergebnisliste'>Ergebnisliste</a>");
}

function wpdocs_set_html_mail_content_type() 
{
	return 'text/html';
}

function printRaceResult($dbConnection, $userID)
{
	$raceQuery = $dbConnection->query("select result.ID as resultID, event.link as eventLink, event.ID as eventID, event.name as eventName, result.Teamname as teamname, result.Result as resultTime, result.Teilnehmer1_ID as teilnehmer1_ID, result.Teilnehmer2_ID as teilnehmer2_ID
																		from result, teilnehmer, event
																		where (Teilnehmer1_ID = '$userID' OR Teilnehmer2_ID = '$userID') AND
																		result.Teilnehmer2_ID = teilnehmer.ID AND result.Event_ID = event.ID
																		ORDER BY result.ID DESC");
	if ($raceQuery->num_rows > 0) 
	{
		while($result = $raceQuery->fetch_assoc()) 
		{
			$teampartnerID = ($result['teilnehmer1_ID'] == $userID)? $result['teilnehmer2_ID'] : $result['teilnehmer1_ID'];
			$teampartner = findNameOfParticipent($teampartnerID, $dbConnection);
			$teampartnername = $teampartner['name'];
			$teampartnerLink = "href='teilnehmer/?userID="."$teampartnerID'";
			$eventLink = $result["eventLink"];
			
			//printTableLine($dbConnection, $result, $teampartnerLink, $teampartnername, $userID);
			echo "<tr>\n";
			echo "<td><a target='_parent' href='$eventLink'>".$result["eventName"]."</td>\n";
			echo "<td><a $teampartnerLink>$teampartnername</a></td>\n";
			echo "<td>".getRankInCurrentRace($dbConnection, $result["eventID"], $result['resultTime'], $userID)."</td>\n";
			echo "</tr>\n";
		}
	} 
	else 
	{
		echo "<tr><td> Keine Ergebnisse</td></tr><br>\n";
	}
}

function getRankInCurrentRace($dbConnection, $eventID, $resultTime, $userID)
{
		$selectName = "SELECT * FROM result where Event_ID = $eventID ORDER BY Result";
		$rankResult = $dbConnection->query($selectName);
		$rank = 0;
		while($raceInfo = $rankResult->fetch_assoc())
		{
			if($raceInfo["Teilnehmer1_ID"] == $userID || $raceInfo["Teilnehmer2_ID"] == $userID)
			{
				
				$result = new DateTime($raceInfo["Result"]);
				$finishTime = $result->format('H:m:s');
				if($raceInfo["Finisher"] == 0)
					return "...";
				else if($finishTime == "00:00:00")
					return $raceInfo["Comment"];
				else if($raceInfo["Result"] == $resultTime) {
					return ++$rank;
				}
			}
			if ($raceInfo["Result"] != "0000-00-00 00:00:00")
				$rank++;
		}
	return 0;
}

function findNameOfParticipent($participentID, $dbConnection)
{
		$selectName = "SELECT * FROM teilnehmer where id = $participentID";
		$nameresult = $dbConnection->query($selectName);
		if ($nameresult->num_rows > 0) 
		{ 
			$selectname = $nameresult->fetch_assoc(); 
			return $selectname; 
		} 
		else 
		{
			return "...";
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

function displayTeilnehmerDropdown($dbConnection)
{
	$participentQuery = $dbConnection->query("SELECT teilnehmer.ID as id, teilnehmer.name as name, count(result.ID) 
											  FROM teilnehmer, result
											  where teilnehmer.ID = result.Teilnehmer1_ID || teilnehmer.ID = result.Teilnehmer2_ID
											  group by teilnehmer.name
											  ORDER BY count(result.ID) desc");
	echo "<form action='' method='get'>";
		echo "Teilnehmer: <select name='userID'>";
		while ($participent = $participentQuery->fetch_assoc()) {
			echo "<option value=".$participent['id'].">".$participent['name']."</option>";
		}
		echo "</selection>";
		echo "<input type='submit' value='OK'></input>";
	echo "</form>";
}
?>

<!DOCTYPE html> 
<html> 
<body>
	<?php
		$dbConnection = (new DBConnector)->createDbConnection();
		$user = getParticipantObject($dbConnection, $_GET['userID']);
	?>
	<div id="innerBody">
		<div style="overflow: auto" id="headder">
				<div id="name"><h2><?php echo $user["name"]?></h2></div>
			<div style="float: left;" id="picture">
				<br>
				<img src="../showProfileImage.php?id=<?php echo $user["ID"];?>" alt="Profilbild" width="230" />
			</div>
			<div style="float: left;" id="about">
				<div style="width: 350px;" id="description"><br><?php echo $user["description"]?></div>
				<div id="contact">
					<form action="?message=1" method="post">
						<?php
						echo "<input type='hidden' size='5' name='id' value='".$_GET['userID']."'></input>"
						?>
						<br>Von:<br>
						<input type="text" size="40" name="sender"></input>
						Nachricht:<br>
						<textarea type="text" cols="30" rows="2" name="messageText"></textarea>
						<br>
						<input type="submit" value="Abschicken">
					</form>
				</div>
			</div>
		</div>
		<div style="overflow: auto" id="body">
			<div style="float: left;" id="navigation">.            .</div>
			<div style="float: left;" id="resultTable">
			 	<table><tbody>
					<tr><td><b>Event</b></td><td><b>Teampartner</b></td><td><b>Platz</b></td></tr>
					<?php
						printRaceResult($dbConnection, $_GET['userID']);
					?>
				</tbody></table>
			</div>
		</div>
		<br>
		Zurueck zur <a href="Ergebnisliste">Ergebnisliste</a>
	</div>
</body>
</html>



