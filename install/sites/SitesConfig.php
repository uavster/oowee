<?php
// Associate each entry domain with a site
$GLOBALS['Oowee_siteAliases'] = array(
	'example.com' => 'example',
	'example.org' => 'example',
	'instance.net' => 'instance',
	'www.instance.net' => 'instance',
);

// Each site has its own configuration. 
// The default site configuration is specified in ooweeConfig['defaultSiteParams']. Every parameter here
// is added to the site configuration or it overrides any existing parameter that is equally named.
$GLOBALS['Oowee_sitesConfig'] = array(
	'example' => array(
		// This path is relative to the sites directory of the oowee configuration
		'directory' => 'example',

		// Local widgets are searched in this subdirectory of the default site
		'siteWidgetDirectory' => 'widgets',	// Relative to the site's directory
		// Local templates are searched in this subdirectory of the default site
		'siteTemplateDirectory' => 'templates',			// Relative to the site's directory

		// Database parameters
		'dbConnString' => 'driver=mysql; server=localhost; user="your user name"; password="your password"',
		'dbName' => 'your database name',

		// Files in these paths will not be accessible by the browser. Paths are relative to the site directory (current or default).
		// If the first element is '__merge__', the default site private paths are merged with these ones.
		// If the first element is '__replace__', the default site private paths are replaced by these ones.
		// If the first parameter is none of the above, the behavior is like with '__merge__'.
		'privateDirectories' => array(),
		// Files with these extensions will not be accessible by the browser. Files may be either in the current or default site.
		// If the first element is '__merge__', the default site extensions are merged with these ones.
		// If the first element is '__replace__', the default site extensions are replaced by these ones.
		// If the first parameter is none of the above, the behavior is like with '__merge__'.
		'privateFileExtensions' => array(),

		// If true, the RedBean library is included
		'useRedBean' => true,
		// If true, RedBean is freezed and no further changes to the database schema will be allowed
		'redBeanFreeze' => false,
		// If false: RedBean uses the library database configuration from the site's configuration (this array), if defined,
		// or from the default's site configuration, otherwise.
		// If a connection string is defined: RedBean uses the connection parameters in the string. Supported drivers are: mysql, sqlite, pgsql, cubrid and oracle.
		'redBeanConnString' => 'driver=mysql; server=localhost; user="your user name"; password="your password"; dbname="your database name"',
	),

	'instance' => array(
		// This path is relative to the sites directory of the oowee configuration
		'directory' => 'instance',

		// Local widgets are searched in this subdirectory of the default site
		'siteWidgetDirectory' => 'widgets',	// Relative to the site's directory
		// Local templates are searched in this subdirectory of the default site
		'siteTemplateDirectory' => 'templates',			// Relative to the site's directory

		// Database parameters
		'dbConnString' => 'driver=mysql; server=localhost; user="your user name"; password="your password"',
		'dbName' => 'your database name',

		// Files in these paths will not be accessible by the browser. Paths are relative to the site directory (current or default).
		// If the first element is '__merge__', the default site private paths are merged with these ones.
		// If the first element is '__replace__', the default site private paths are replaced by these ones.
		// If the first parameter is none of the above, the behavior is like with '__merge__'.
		'privateDirectories' => array(),
		// Files with these extensions will not be accessible by the browser. Files may be either in the current or default site.
		// If the first element is '__merge__', the default site extensions are merged with these ones.
		// If the first element is '__replace__', the default site extensions are replaced by these ones.
		// If the first parameter is none of the above, the behavior is like with '__merge__'.
		'privateFileExtensions' => array(),

		// If true, the RedBean library is included
		'useRedBean' => true,
		// If true, RedBean is freezed and no further changes to the database schema will be allowed
		'redBeanFreeze' => false,
		// If false: RedBean uses the library database configuration from the site's configuration (this array), if defined,
		// or from the default's site configuration, otherwise.
		// If a connection string is defined: RedBean uses the connection parameters in the string. Supported drivers are: mysql, sqlite, pgsql, cubrid and oracle.
		'redBeanConnString' => 'driver=mysql; server=localhost; user="your user name"; password="your password"; dbname="your database name"',
	)

);

?>
