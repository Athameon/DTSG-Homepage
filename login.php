<?php
require 'wp-includes/class.DBConnector.php';
session_start();

$dbConnection = (new DBConnector)->createDbConnection();

 
if(isset($_GET['login'])) 
{
	$email = $_POST['email'];
	$passwort = $_POST['passwort'];
	
	$query = "SELECT * FROM teilnehmer WHERE email = '$email'";
	$statement = $dbConnection->query($query);
	$user = $statement->fetch_assoc();
	
	//Überprüfung des Passworts
	if ($user !== false && password_verify($passwort, $user['passwort'])) {
		$_SESSION['userid'] = $user['ID'];
		$prepage = $_GET['pre'];
		if($_GET['pre'] != ""){
			header("Location: ".$prepage);
		}
		else{
			header("Location: intern");
		}
	} else {
		$errorMessage = "E-Mail oder Passwort war ungültig<br> Zum Zurücksetzen klicke bitte <a href='reset>hier</a>";
	}
}
$dbConnection->close();
 
if(isset($errorMessage)) {
	echo $errorMessage;
}
$prepage = $_GET['pre'];
echo '<form action="?login=1&pre='.$prepage.'" method="post">';
?>

E-Mail:<br>
<input type="email" size="40" maxlength="250" name="email"><br><br>
 
Dein Passwort:<br>
<input type="password" size="40"  maxlength="250" name="passwort"><br>
 
<input type="submit" value="Abschicken">
<p>Noch kein Account und noch nie bei einem Rennen mitgemacht? Dann registriere dich bitte <a href=<?php echo 'registry?pre='.$_GET['pre']?>>hier</a></p>
<p>Passwort vergessen oder nie eins bekommen? <a href='reset'>Hier zurücksetzen</a><p>
</form> 
