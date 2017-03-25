<?php
require 'wp-includes/class.DBConnector.php';

session_start();
if(!isset($_SESSION['userid'])) 
{
	die('Bitte zuerst <a href="../login">einloggen</a>');
}

$showFormular = true; //Variable ob das Registrierungsformular anezeigt werden soll

if(isset($_GET['changePassword'])) 
{
	$dbConnection = (new DBConnector)->createDbConnection();
	
	$oldpasswort = $_POST['oldpasswort'];
	$passwort = $_POST['passwort'];
	$passwort2 = $_POST['passwort2'];
	
	$userid = $_SESSION['userid'];
	 
	if(strlen($passwort) == 0) 
	{
		echo 'Bitte ein Passwort angeben<br>';
		$error = true;
	}
	if($passwort != $passwort2) 
	{
		echo 'Die Passwörter müssen übereinstimmen<br>';
		$error = true;
	}
	
	if(!$error) 
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
				die("Dein Passwort wurde erfolgreich geändert.<br>Weiter zum <a href='../intern'>Homescreen<a>");
			} 
			else 
			{
				echo 'Beim aendern ist leider ein Fehler aufgetreten. Bitte wende dich an sporttrampen(a)gmail.com.<br>';
			}
		} 
		else 
		{
			echo "Die alten Passworter stimmen nicht ueberein.<br>";
		}
	}
}

$dbConnection->close();
if($showFormular) {
?>

<form action="?changePassword=1" method="post">

Altes Passwort:<br>
<input type="password" size="40"  maxlength="250" name="oldpasswort"><br>
 
Neues Passwort:<br>
<input type="password" size="40"  maxlength="250" name="passwort"><br>
 
Passwort wiederholen:<br>
<input type="password" size="40" maxlength="250" name="passwort2"><br><br>

 
<input type="submit" value="Abschicken">
</form>
 
<?php
} //Ende von if($showFormular)

?>
 
</body>
</html>
