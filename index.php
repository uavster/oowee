<?php
require_once dirname(__FILE__).'/Base/ClassLoader.php';
require_once dirname(__FILE__).'/OoweeConfig.php';

// Global functions

function error($str, $logContext = false) {
	global $site;
	$site->error($str, $logContext);
}

function warning($str, $logContext = false) {
	global $site;
	$site->warning($str, $logContext);
}

function info($str, $logContext = false) {
	global $site;
	$site->info($str, $logContext);
}

function getSite() {
	global $site;
	return $site;
}

function getQuery() {
	return $_REQUEST['q'];
}

function getOoweeConfig($param = null) {
	global $ooweeConfig;
	return $param === null ? $ooweeConfig : (array_key_exists($param, $ooweeConfig) ? $ooweeConfig[$param] : null);
}

try {

	// Disable magic quotes
	if (get_magic_quotes_gpc()) {
		if (function_exists('array_walk_recursive')) {
			function stripslashes_gpc(&$value)
			{
				$value = stripslashes($value);
			}
			array_walk_recursive($_GET, 'stripslashes_gpc');
			array_walk_recursive($_POST, 'stripslashes_gpc');
			array_walk_recursive($_COOKIE, 'stripslashes_gpc');
			array_walk_recursive($_REQUEST, 'stripslashes_gpc');		
		} else {
			$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
			while (list($key, $val) = each($process)) {
				foreach ($val as $k => $v) {
					unset($process[$key][$k]);
					if (is_array($v)) {
						$process[$key][stripslashes($k)] = $v;
						$process[] = &$process[$key][stripslashes($k)];
					} else {
						$process[$key][stripslashes($k)] = stripslashes($v);
					}
				}
			}
			unset($process);
		}
	}

	// The non existing URLs have been redirected 
	$url = Helpers_Url::getQueryUrl($ooweeConfig['sitesConfigEncoding']);
	$site = SiteEngine_SiteManager::getSiteFromUrl($url);
	if ($site !== false) {
		// Check if RedBean should be enabled
		if ($site->getConfig('useRedBean')) {
			require dirname(__FILE__).'/RedBean/rb.php';
			$rbConnStr = $site->getConfig('redBeanConnString');
			if ($rbConnStr !== false) $dbConnString = $rbConnStr;
			else {
				$dbConnString = $site->getConfig('dbConnString');
				if ($dbConnString === false) throw new Exception('No connection string is defined for RedBean');
				if ($site->getConfig('dbName') === false) throw new Exception('No database name is defined for RedBean');
				$dbConnString .= '; dbname=' . $site->getConfig('dbName');
			}
			$conn = DB_Connection::connStringToAssocArray($dbConnString);
			if ($conn === false) throw new Exception('Bad connection string for RedBean');
			if (!array_key_exists('driver', $conn)) throw new Exception('No database driver specified for RedBean');
			$connString = '';
			if ($conn['driver'] == 'sqlite') {
				if (!array_key_exists('server', $conn)) $connString = null;
				else $connString = 'sqlite:' . $conn['server'];
			} else $connString = $conn['driver'] . ':host=' . $conn['server'] . ';dbname=' . $conn['dbname'];
			$user = array_key_exists('user', $conn) ? $conn['user'] : null;
			$pass = array_key_exists('password', $conn) ? $conn['password'] : null;
			R::setup($connString, $user, $pass);

			if ($site->getConfig('redBeanFreeze')) {
				R::freeze(true);
			} 

			unset($rbConnStr); unset($dbConnString); unset($conn); unset($connString); unset($user); unset($pass);
		}

		// The site dispatches the query. The query is URL-decoded and utf8 encoded.
		$site->dispatchQuery($_REQUEST['q'], strtolower($_SERVER['REQUEST_METHOD']));

		// Close RedBean if needed
		if ($site->getConfig('useRedBean')) {
			R::close();
		}
	} else {
		header('HTTP/1.0 404 Not Found');
		include SiteEngine_SiteManager::getDefaultUnknownSiteDoc();
	}

} catch(Exception $e) {
	// Log and output any error
	error($e->getMessage());
	$ooweeError = $e->getMessage();
	include $ooweeConfig['defaultSiteParams']['ooweeErrorPage'];
	unset($ooweeError);
}

?>
