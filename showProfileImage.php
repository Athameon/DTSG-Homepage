<?php
header("Content-type: image/jpeg");
require 'wp-includes/class.DBConnector.php';

$dbConnection = (new DBConnector)->createDbConnection();

if(isset($_GET['id']))
{
	$userid = $_GET['id'];
	$query = "SELECT * FROM teilnehmer WHERE ID = '$userid'";
	$statement = $dbConnection->query($query);
	$user = $statement->fetch_assoc();
	
	echo $user["image"];
}
else
{
	echo "Error";
}
?>


