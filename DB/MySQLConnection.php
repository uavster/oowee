<?php

require_once dirname(__FILE__) . '/../Base/ClassLoader.php';

class DB_MySQLConnection extends DB_Connection implements DB_IConnection {
	
	private $link = FALSE;
	
	function connect($host, $userName, $password) {
		$this->link = mysql_connect($host, $userName, $password);
	}
	
	function __construct($connectionString) {
		$connParams = $this->connStringToAssocArray($connectionString);
		if ($connParams !== FALSE && 
			array_key_exists('server', $connParams) && array_key_exists('user', $connParams) && array_key_exists('password', $connParams)) {
			$this->connect($connParams['server'], $connParams['user'], $connParams['password']);
		}
	}
	
	function __destruct() {
		mysql_close($this->link);
	}

	public function isConnected() {
		return $this->link !== FALSE;
	}
	
	public function selectDatabase($database) {
		return mysql_select_db($database, $this->link);
	}
	
	public function query($query) {
		return mysql_query($query, $this->link);
	}

	public function fetchArray($resource) {
		return mysql_fetch_array($resource);
	}
	
	public function getLastInsertedId() {
		return mysql_insert_id($link);
	}

	public function escapeString($str) {
		return mysql_real_escape_string($str, $this->link);
	}

	public function getLastError() {
		return mysql_error();
	}
}

?>
