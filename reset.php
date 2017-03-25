<?php 
require 'wp-includes/class.DBConnector.php';
require 'wp-includes/pluggable.php';

$dbConnection = (new DBConnector)->createDbConnection();
 
if(isset($_GET['reset'])) {
	sendMailToResetPassword($dbConnection);
}

if(isset($_GET['code'])) {
	resetPasswordAfterMailConfirmation($dbConnection);
}


?>
<!DOCTYPE html> 
<html> 
<body>
 
<?php 
if(isset($errorMessage)) {
	echo $errorMessage;
}

function sendMailToResetPassword($dbConnection)
{
	$email = $_POST['email'];
	
	$query = "SELECT * FROM teilnehmer WHERE email = '$email'";
	$statement = $dbConnection->query($query);
	$user = $statement->fetch_assoc();
	
	if ($user !== false) {
		$verificationscode = $user['securitycode'];
		$name = $user['name'];
		$email = $user['email'];

		sendMail($verificationscode, $name, $email);
		die("Deine E-Mail addresse '$email' wurde erfolgreich gefunden. Zum Zurücksetzen deines Passworts haben wir dir eben eine Mail geschickt.");
	} else {
		$errorMessage = "Die angegebene E-Mail wurde in unserer Datenbank nicht gefunden</a>";
	}
}

$dbConnection->close();

function sendMail($verificationscode, $name, $email)
{
	$subject = "[DTSG] Passwort zuruecksetzen";
	
	$msg = "Hallo $name,
														
du empfängst diese mail, weil auf der DTSG Homepage angegeben hast, dass du dein Passwort zuruecksetzen willst
Zum Zuruecksetzen folge bitte folgenem link: http://sporttrampen.de/reset/?code=$verificationscode&email=$email
					
Solltest du nicht den Auftrag zum Zuruecksetzen des passworts gegeben haben, so ignoriere einfach diese Mail
					
Liebe Gruesse
Deine DTSG";

	add_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
	wp_mail($email, $subject, $msg);
	remove_filter( 'wp_mail_content_type', 'wpdocs_set_html_mail_content_type' );
}

function resetPasswordAfterMailConfirmation($dbConnection)
{
	$email = $_GET['email'];
	$code = $_GET['code'];

	$query = "SELECT * FROM teilnehmer WHERE email = '$email'";
	$statement = $dbConnection->query($query);
	$user = $statement->fetch_assoc();
	
	if ($user !== false && $user['securitycode'] == $code) {
		$newcode = generateRandomString();
		$temppassword = generateRandomString();
		$passwort_hash = password_hash($temppassword, PASSWORD_DEFAULT);
		
		$query = "UPDATE teilnehmer 
							Set passwort='$passwort_hash', securitycode='$newcode'
							WHERE email = '$email'";
		$statement = $dbConnection->query($query);
		
		if($statement) 
		{		
			die("Dein Passwort wurde erfolgreich auf <br><br><h2>$temppassword</h2><br><br> zurueckgesetzt.<br>
					 Bitte merke dir das Passwort und <a href='login.php'>logge</a> dich damit ein.<br>Zum ändern des Passworts benoetigst du dieses Passwort.");
		} 
		else 
		{
			echo 'Beim ändern ist leider ein Fehler aufgetreten. Bitte wende dich an sporttrampen(a)gmail.com.<br>';
		}

		
	} else {
		die("Error: Der Sicherheitscode stimmt nicht mit der E-Mail-Adresse ueberein.</a>");
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
?>
<p>Um dein Passwort zurückzusetzen gib bitte deine E-Mail-Adresse ein.<br>
Anschließend wird dir ein Link zum Zurücksetzen des Passworts geschickt.</p>
<form action="?reset=1" method="post">
E-Mail:<br>
<input type="email" size="40" maxlength="250" name="email"><br><br>
 
<input type="submit" value="Abschicken">
</form> 
</body>
</html>
