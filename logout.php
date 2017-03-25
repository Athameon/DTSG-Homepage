<?php
session_start();
session_destroy();
 
echo "Logout erfolgreich<br>";
echo "Weiter zum <a href='../login'>login</a>";
?>
