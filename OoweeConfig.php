<?php
$GLOBALS['ooweeConfig'] = array(
	'sitesPath' => dirname(__FILE__).'/../sites',
	'sitesConfigEncoding' => 'UTF-8',	//'cp1252',
	'defaultSiteDirectory' => dirname(__FILE__).'/DefaultSite',	// Absolute path
	'logicQueryMarker' => '!!',

	// Default configuration for all sites.
	// This configuration is merged with the site's specific configuration. The site configuration variables override these ones if equally named.
	'defaultSiteParams' => array(
		'siteMapFileName' => 'SiteMap.php',			// Relative to the site's directory
		// Global templates are searched in this subdirectory of the default site
		'siteTemplateDirectory' => 'templates',			// Relative to the site's directory
		// Global widgets are searched in this subdirectory of the default site
		'siteWidgetDirectory' => 'widgets',			// Relative to the site's directory
		'error404Page' => dirname(__FILE__).'/Docs/error404.html',	// Absolute path
		'ooweeErrorPage' => dirname(__FILE__).'/Docs/ooweeError.php',	// Absolute path
		// Files in these paths will not be accessible by the browser. Paths are relative to the site directory (current or default).
		'privateDirectories' => array(),
		// Files with these extensions will not be accessible by the browser. Files may be either in the current or default site.
		'privateFileExtensions' => array('tpl', 'cfg'),
		// If true, the RedBean library is included 
		'useRedBean' => false,
		// If false: RedBean uses the library database configuration from the site's configuration, if defined,
		// or from the default's site configuration (this array), otherwise.
		// If a connection string is defined: RedBean uses the connection parameters in the string. Supported drivers are: mysql, sqlite, pgsql, cubrid and oracle.
		'redBeanConnString' => 'driver=sqlite',
		// If true, RedBean is freezed and no further changes to the database schema will be allowed
		'redBeanFreeze' => false,
		// If set to true and the user is not logged as admin, the CMS page will show as 404 document not found. Otherwise, the login page will be displayed.
		'cmsHideIfNotAdmin' => true,
	)
);
?>
