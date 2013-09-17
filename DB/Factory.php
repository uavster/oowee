<?php
require_once dirname(__FILE__).'/../Base/ClassLoader.php';

class DB_Factory {

	public static function getDbConnection($connectionString) {
		$connParams = DB_Connection::connStringToAssocArray($connectionString);
		if ($connParams === FALSE) throw new Exception('Connection string cannot be decoded');
		if (!array_key_exists('driver', $connParams)) throw new Exception('No specified driver in connection string');
		else $driverName = $connParams['driver'];
		// Each driver creates a different object type
		if (strcmp($driverName, 'mysql') == 0) {
			return new DB_MySQLConnection($connectionString);
		}
		// Add other driver types here
		else throw new Exception("Unknown driver '$driverName'");
	}
	
}

?>
