 <?php
require 'wp-includes/class.DBConnector.php';


//-------------END-------------

function printRaceResults($dbConnection)
{
	$raceQuery = $dbConnection->query("SELECT id, name, link, eventtype FROM event");
	if ($raceQuery->num_rows > 0) 
	{
		while($raceInfo = $raceQuery->fetch_assoc()) 
		{
			if($raceInfo["eventtype"] == 1 || $raceInfo["eventtype"] == 2)
				printEachRace($dbConnection, $raceInfo);
		}
	} 
	else 
	{
		//echo "<tr> <td colspan=''><style='text-align: left;'>\n";
		echo "<tr><td> Keine Ergebnisse</td></tr><br>\n";
	}
}

function printEachRace($dbConnection, $raceInfo)
{
	printRaceHeadder($raceInfo["id"], $raceInfo["name"], $raceInfo["link"]);

	$currentRaceQuery = getOrderedRaceResults($raceInfo["id"], $dbConnection);

	if ($currentRaceQuery->num_rows > 0) 
	{
		printCurrentRaceResultAsTable($currentRaceQuery, $dbConnection);
	} 
	else 
	{
		echo "<tr> <td colspan=''><style='text-align: left;'>\n";
		echo "<td> Keine Ergebnisse</td></tr>\n";
	}
	echo "<br>\n";
}

function printRaceHeadder($raceID, $raceName, $raceLink)
{
  echo "<tr> <td colspan='4'><h3 style='text-align: left;'>\n";
  echo "$raceID. Rennen: <a target='_parent' href='$raceLink'>$raceName</a></h2>\n";
}

function getOrderedRaceResults($event_id, $dbConnection)
{
	return $dbConnection->query("SELECT id, teamname, teilnehmer1_ID, teilnehmer2_ID, result, finisher 
																	 FROM result 
																	 where event_id = ".$event_id." 
																	 order by finisher desc, comment, result"
																	 );
}

function printCurrentRaceResultAsTable($currentRaceQuery, $dbConnection)
{
	printCurrentRaceHeadder();
	$rank = 1;
	while($currentTeam = $currentRaceQuery->fetch_assoc()) 
	{
		getSingleTeamResultAndPrintResult($currentTeam, $rank, $dbConnection);
		$rank++;
	}
	echo "</tbody></table>\n";
}

function printCurrentRaceHeadder()
{
	echo "<table><tbody>\n";
	echo "<tr> <td colspan='4'><h3 style='text-align: left;'>\n";
	echo "<tr> <td width=60 align='center'><b>Platz</b></td><td width=180><b> Teamname</b></td> <td width=170><b>Teammitglieder  </b></td> <td style='text-align: center;'><b>Zielzeit</b></td> </tr>\n";
}

function getSingleTeamResultAndPrintResult($currentTeam, $rank, $dbConnection)
{
	$rank = ($currentTeam["finisher"] == '0')? '...' : $rank;
	$teamname = $currentTeam["teamname"];
	$teilnehmer1Link = "href='teilnehmer/?userID=".$currentTeam["teilnehmer1_ID"]."'";
	$teilnehmer2Link = "href='teilnehmer/?userID=".$currentTeam["teilnehmer2_ID"]."'";
	$participent1 = findNameOfParticipent($currentTeam["teilnehmer1_ID"], $dbConnection);
	$participent2 = findNameOfParticipent($currentTeam["teilnehmer2_ID"], $dbConnection);
	
	$finish = $currentTeam["result"];
	if ($currentTeam["finisher"] == '0') 
		echo "<tr> <td colspan='2'>Disqualifiziert: </tr> </td>\n";
	$finish = ($finish == '0000-00-00 00:00:00')? '...' : $finish;
	echo "<tr> <td align='center'>$rank.</td> <td>$teamname</td> <td> <a $teilnehmer1Link>$participent1</a>  &amp; <a $teilnehmer2Link>$participent2</a></td> <td align='center'>$finish</td> </tr>\n";
}


function findNameOfParticipent($participentID, $dbConnection)
{
		$selectName = "SELECT name FROM teilnehmer where id = $participentID";
		$nameresult = $dbConnection->query($selectName);
		if ($nameresult->num_rows > 0) 
		{ 
			$selectname = $nameresult->fetch_assoc(); 
			return $selectname["name"]; 
		} 
		else 
		{
			return "...";
		}
}

?> 

<!DOCTYPE html> 
<html> 
<body>
	<table><tbody>
	<?php
	$dbConnection = (new DBConnector)->createDbConnection();

	printRaceResults($dbConnection);
	$dbConnection->close();

	?>
	</tbody></table>
</body>
</html>