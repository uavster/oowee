<?php

interface DB_IConnection {
	public function __construct($connectionString);
	public function isConnected();
	public function selectDatabase($database);
	public function query($query);
	public function fetchArray($resource);
	public function getLastInsertedId();
	public function escapeString($str);
	public function getLastError();
}

?>
