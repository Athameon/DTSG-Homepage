<?php

class DBConnector
{
	public function createDbConnection()
	{
		$servername = "localhost";
		$username = "<username>";
		$password = "<password>";
		$dbname = "<name of database>";
		
		// Create connection
		$dbConnection = new mysqli($servername, $username, $password, $dbname);

		// Check connection
		if ($dbConnection->connect_error) {
				die("Connection failed: " . $dbConnection->connect_error);
		}
		return $dbConnection;
	}
}
?>
