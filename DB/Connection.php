<?php
require_once dirname(__FILE__).'/../Base/ClassLoader.php';

class DB_Connection {

	public static function connStringToAssocArray($connString) {
		return Helpers_String::varStringToAssocArray($connString, ';', '=');
	}
	
}

?>