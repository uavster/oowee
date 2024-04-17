<?php

require_once dirname(__FILE__) . '/../Base/ClassLoader.php';

class DB_MySQLConnection extends DB_Connection implements DB_IConnection {
	
	private $link = FALSE;
	
	function connect($host, $userName, $password) {
    $this->link = new mysqli($host, $userName, $password);	
  }
	
  function __construct($connectionString) {
		$connParams = $this->connStringToAssocArray($connectionString);
		if ($connParams !== FALSE && 
			array_key_exists('server', $connParams) && array_key_exists('user', $connParams) && array_key_exists('password', $connParams)) {
			$this->connect($connParams['server'], $connParams['user'], $connParams['password']);
		}
	}
	
	function __destruct() {
		if ($this->isConnected()) {
      unset($this->link);
    }
	}

	public function isConnected() {
		return $this->link !== FALSE;
	}
	
	public function selectDatabase($database) {
		return $this->link->select_db($database);
	}
	
	public function query($query) {
		return $this->link->query($query);
	}

	public function fetchArray($resource) {
		return $resource->fetch_array();
	}
	
	public function getLastInsertedId() {
		return $this->link->insert_id();
	}

	public function escapeString($str) {
		return $this->link->real_escape_string($str);
	}

	public function getLastError() {
		return $this->link->error();
	}
}

?>
