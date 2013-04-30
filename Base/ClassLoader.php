<?php

class Base_ClassLoader {
	static function loadClass($className) {
		$classPath = str_replace('_', DIRECTORY_SEPARATOR, $className);
		$filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $classPath . '.php';
		if (file_exists($filePath)) require_once $filePath;
	}
	
	static function register() {
		spl_autoload_register('Base_ClassLoader::loadClass');
	}
	
	static function unregister() {
		spl_autoload_unregister('Base_ClassLoader::loadClass');
	}
}

Base_ClassLoader::register();

?>
