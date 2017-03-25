The files in this repository are written to be used in a wordPress homepage.

A PHP plugin must be installed (eg PHP Code For Posts).
Javascript must be activated.
The plugin "WP-Mail-SMTP" must be installed.

The file class.DBConnector.php must be modified with the logindata and copied to ~/html/wp-includes/
The DB on the WP server must contain following tables:
- event
- eventtype
- registrations
- result
- teilnehmer
--> The structure of the tables can be seen in the folder "Database"


In this first version it is necessary to copy the content of the php files into single text-pages (not visuell) of WP.
- Create a new page and copy the content.
