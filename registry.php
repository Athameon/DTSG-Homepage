<?php 
require 'wp-includes/class.DBConnector.php';
session_start();

$dbConnection = (new DBConnector)->createDbConnection();

$showFormular = true; //Variable ob das Registrierungsformular anezeigt werden soll
 
if(isset($_GET['register'])) {
	$error = false;
	$email = $_POST['email'];
	$passwort = $_POST['passwort'];
	$passwort2 = $_POST['passwort2'];
	$name = $_POST['firstname'];
	$lastname = $_POST['lastname'];
	$mobilenumber = $_POST['mobilenumber'];
  
	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo 'Bitte eine gültige E-Mail-Adresse eingeben<br>';
		$error = true;
	} 	
	if(strlen($passwort) == 0) {
		echo 'Bitte ein Passwort angeben<br>';
		$error = true;
	}
	if($passwort != $passwort2) {
		echo 'Die Passwörter müssen übereinstimmen<br>';
		$error = true;
	}
	
	//Überprüfe, dass die E-Mail-Adresse noch nicht registriert wurde
	if(!$error) {
		$query = "SELECT * FROM teilnehmer WHERE email = '$email'";
		$statement = $dbConnection->query($query);
		if ($statement->num_rows > 0) 
		{
			die('Diese E-Mail-Adresse ist bereits vergeben. Bitte melde dich <a href="../login">hier an</a><br>');
		} 
	}
	
	//Keine Fehler, wir können den Nutzer registrieren
	if(!$error) {	
		$passwort_hash = password_hash($passwort, PASSWORD_DEFAULT);
		$securitycode = generateRandomString();
		
		$query = "INSERT INTO teilnehmer (email, passwort, name, lastname, mobilenumber, securitycode) VALUES ('$email', '$passwort_hash', '$name', '$lastname', '$mobilenumber', '$securitycode')";
		$statement = $dbConnection->query($query);//
		
		if($statement) {
			echo 'Du wurdest erfolgreich registriert. <a href="../login/?pre='.$_GET['pre'].'">Zum Login</a>';
			$showFormular = false;
		} else {
			echo 'Beim Abspeichern ist leider ein Fehler aufgetreten<br>';
		}
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

$dbConnection->close();
if($showFormular) {
?>
<p>Hallo. Schoen, dass du dich registrieren willst. Wenn du bereits zuvor bei einem Wettkampf der DTSG mitgemacht hast, dann schick uns bitte eine Mail mit deiner E-Mail-Adresse, deinem Namen und deiner Handynummer an sporttrampen(a)gmail.com. Wir werden dein bisheriges Profil erweitern und dir eine Mail zur Registrierung schicken</p>
<?php
$prepage = $_GET['pre'];
echo '<form action="?register=1&pre='.$prepage.'" method="post">';
?>
E-Mail:<br>
<input type="email" size="40" maxlength="50" name="email"><br><br>

Vorname:<br>
<input type="text" size="40" maxlength="30" name="firstname"><br><br>

Nachname:<br>
<input type="text" size="40" maxlength="30" name="lastname"><br><br>
 
Dein Passwort:<br>
<input type="password" size="40"  maxlength="250" name="passwort"><br>
 
Passwort wiederholen:<br>
<input type="password" size="40" maxlength="250" name="passwort2"><br><br>

Handynummer:<br>
<input type="text" size="40" maxlength="20" name="mobilenumber"><br><br>
 
<input type="submit" value="Abschicken">
</form>
 
<?php
} //Ende von if($showFormular)

?>
 
</body>
</html>
