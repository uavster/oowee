<?php
require_once dirname(__FILE__).'/../Base/ClassLoader.php';
require_once dirname(__FILE__).'/../OoweeConfig.php';
require_once $GLOBALS['ooweeConfig']['sitesPath'].'/SitesConfig.php';

class SiteEngine_Site {
	const SITE_MAP_REF_MARK = 'ref:';
	
	private $name;
	private $baseUrl;
	private $config;
	private $map;
	private $loadError;
	private $lastError = 'No error';
	private $logFile = false;
	private $logContext;
	private $requestType;
	private $query;
	private $pageInfo;
	
	public function getName() {
		return $this->name;
	}

	protected function siteConfigExists($siteName) {
		global $Oowee_sitesConfig;
		return array_key_exists($siteName, $Oowee_sitesConfig);
	}
	
	protected function isAssocArray($arr) {
	    return array_keys($arr) !== range(0, count($arr) - 1);
	}

	protected function arrayReplaceRecursiveControl($a1, $a2) {
		$temp = array_replace_recursive($a1, $a2);
		$output = array();
		foreach($a2 as $key => $value) {
			if (	(is_array($value) && !$this->isAssocArray($value)) || 
				(array_key_exists($key, $a1) && is_array($a1[$key]) && !$this->isAssocArray($a1[$key]))
				) {
				if (!is_array($value)) $value = array($value);
				if (array_key_exists($key, $a1)) {
					if (!is_array($a1[$key])) $value1 = array($a1[$key]);
					else $value1 = $a1[$key];
				} else $value1 = array();
				$field = array();
				if (count($value) > 0 && $value[0] == '__replace__') $merge = false;
				else $merge = true;
				if (count($value) > 0 && ($value[0] == '__replace__' || $value[0] == '__merge__')) 
					$trimmedValue = array_slice($value, 1);
				else 
					$trimmedValue = $value;
				if ($merge)
					$temp[$key] = array_unique(array_merge($trimmedValue, $value1));
				else
					$temp[$key] = $trimmedValue;
			}
		}
		return $temp;
	}

	public function __construct($siteName, $baseUrl) {
		global $Oowee_sitesConfig;
		global $ooweeConfig;
		$this->name = $siteName;
		$this->baseUrl = Helpers_Url::setProtocol($baseUrl, Helpers_Url::getQueryProtocol());
		if ($this->baseUrl[strlen($this->baseUrl) - 1] != '/') $this->baseUrl .= '/';
		if ($this->siteConfigExists($siteName)) {
			$this->config = $this->arrayReplaceRecursiveControl($ooweeConfig['defaultSiteParams'], $Oowee_sitesConfig[$siteName]);
			$this->loadError = !$this->loadSiteMap();
		} else $this->loadError = true;
		$this->logContext = get_class($this);
	}

	public function __destruct() {
		if ($this->logFile !== false) fclose($this->logFile);
	}
	
	public function isLoaded() {
		return !$this->loadError;
	}
	
	public function getConfig($varName) {
		if (!isset($varName)) return $this->config;
		else if (array_key_exists($varName, $this->config)) return $this->config[$varName];
		else return false;
	}

	public function getBaseUrl() {
		return $this->baseUrl;
	}
 
	protected function normalizePath($path) {
		$lastIndex = strlen($path) - 1;
		if ($path[$lastIndex] != '/' && $path[$lastIndex] != "\\")
			$path .= DIRECTORY_SEPARATOR;
		return $path;
	}
	
	protected function getSitesBaseDirectory() {
		global $ooweeConfig;
		return $this->normalizePath($ooweeConfig['sitesPath']);
	}
	
	public function getBasePath() {
		return self::getSitesBaseDirectory() . $this->normalizePath($this->config['directory']);
	}

	public function getEncoding() {
    global $ooweeConfig;
		return array_key_exists('encoding', $this->map) ? $this->map['encoding'] : $ooweeConfig['sitesConfigEncoding'];
	}

	public function getPathToFile($fileName) {
		return $this->getBasePath() . $fileName;
	}
	
	protected function getPathToDefaultSiteFile($fileName) {
		global $ooweeConfig;
		return $this->normalizePath($ooweeConfig['defaultSiteDirectory']) . $fileName;
	}

	public function findPathToWidget($name) {
		global $ooweeConfig;
		$localWidget = $this->getPathToFile($this->normalizePath($this->config['siteWidgetDirectory']) . $name);
		if (is_dir($localWidget)) return $this->normalizePath($localWidget);
		else {
			$globalWidget = $this->getPathToDefaultSiteFile($this->normalizePath($ooweeConfig['defaultSiteParams']['siteWidgetDirectory']) . $name);
			if (is_dir($globalWidget)) return $this->normalizePath($globalWidget);
			else return false;
		}
	}

	public function getWidgetPathsInSiteMapByName($name, &$widgetParams = false) {
		if ($widgetParams !== false) $widgetParams = array();
		$widgetPaths = array();
		foreach($this->map['pages'] as $page => $config) {
			if ((!is_array($config)) || (!array_key_exists('labels', $config))) continue;

			$labels = $config['labels'];
			foreach($labels as $label => $content) {
				$labelInfo = $this->decodeSiteMapLabel($content);
				if ($labelInfo['type'] == 'widget') {
					$widgetInfo = SiteEngine_Widget::deserializeCall($labelInfo['content']);
					if ($widgetInfo['name'] == $name) {
						$widgetPaths[] = $page . '/' . $label;
						if (is_array($widgetParams)) {
							$widgetParams[] = $widgetInfo['params'];
						}
					}
				}
			}
		}
		return $widgetPaths;
	}

	public function findPathToTemplate($name) {
		global $ooweeConfig;
		$localTemplate = $this->getPathToFile($this->normalizePath($this->config['siteTemplateDirectory']) . $name);
		if (file_exists($localTemplate)) return $localTemplate;
		else {
			$globalTemplate = $this->getPathToDefaultSiteFile($this->normalizePath($ooweeConfig['defaultSiteParams']['siteTemplateDirectory']) . $name);
			if (file_exists($globalTemplate)) return $globalTemplate;
			else return false;
		}
	}

	public function getRelativeUrlForPath($path, $isFile = false) {
		$path = realpath($path);
		if (!$isFile) $path = $this->normalizePath($path);
		$basePath = $this->normalizePath(realpath($this->getBasePath()));		
		if (strpos($path, $basePath) === 0) {
			return substr($path, strlen($basePath));
		} else {
			$basePath = $this->normalizePath(realpath($this->getPathToDefaultSiteFile('')));
			if (strpos($path, $basePath) === 0) return substr($path, strlen($basePath));
			else return false;
		}
	}

	public function getAbsoluteUrlForPath($path, $isFile = false) {
		return $this->getBaseUrl() . $this->getRelativeUrlForPath($path, $isFile);
	}

	public function isFileAccessAllowed($filePath, $basePath = false) {
		if ($basePath === false) $basePath = $this->getBasePath();
		else $basePath = $this->normalizePath($basePath);
		// Check if extension is forbidden
		$exts = $this->getConfig('privateFileExtensions');
		if ($exts !== false) {
			$fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
			if ($fileExt === NULL) $fileExt = '';
			foreach($exts as $ext) {
				if ($fileExt == $ext) return false;
			}
		}
		// Check if path is forbidden
		$dirs = $this->getConfig('privateDirectories');
		if ($dirs !== false) {
			if (count($dirs) > 0) {
				$filePath = realpath($filePath);
				if ($filePath === false) return false;
				$filePath = pathinfo($filePath, PATHINFO_DIRNAME); 
				foreach($dirs as $dir) {
					if ($filePath == realpath($basePath . $dir)) return false;
				}
			}
		}
		return (fileperms($filePath) & 0x0004) ? true : false;
	}

	protected function loadSiteMap() {
		global $siteMap;
		$ret = (include $this->getPathToFile($this->config['siteMapFileName']));
		if ($ret != 1) return false;
		$this->map = $siteMap;
		$this->map['pages']['cms/*'] = 'ref:cms';
		$cmsHide = ($this->getConfig('cmsHideIfNotAdmin') ? 'yes' : 'no');
		$this->map['pages']['cms'] = array('template' => 'CMS/cms.tpl', 'labels' => array('__default' => 'widget:CMS', 'content' => "widget:CMS(hideIfNotAdmin=$cmsHide)"));
		return true;
	}
	
	// This method encodes an UTF8-encoded query to the site config file encoding, so the queried page is found in the configuration array
	protected function transcodeUtf8Query($query) {
		return iconv('UTF-8', $this->getEncoding(), $query);
	}
	
	public function docExists($query) {
		$query = $this->transcodeUtf8Query($query);
		return $this->findPageForQuery($query) !== false;
	}
	
	private function startsWith($str, $start) {
		return substr($str, 0, strlen($start)) === $start;
	}
	
	protected function findPageForQuery($query) {
		foreach($this->map['pages'] as $key => $value) {
			$reg = str_replace('/', '\/', "^$key\$");
			$reg = str_replace('*', '.*', "/$reg/");
			if (preg_match($reg, $query)) return $key;
		}
		return false;
	}

	protected function compilePageInfo($query) {
		// Get template
		$mapPages = $this->map['pages'];
		$page = $this->findPageForQuery($query);
		if ($page === false) return false;
		$pageInfo = $mapPages[$page];

		// Solve chained references to other pages
		while (is_string($pageInfo) && $this->startsWith($pageInfo, self::SITE_MAP_REF_MARK)) {
			$refPage = substr($pageInfo, strlen(self::SITE_MAP_REF_MARK));
			if (!array_key_exists($refPage, $mapPages)) {
				$this->setLastError("Unable to reference page \"$refPage\" when loading \"$query\"");
				return false;
			}
			$pageInfo = $mapPages[$refPage];
		}
		if (is_string($pageInfo) && !$this->startsWith($pageInfo, self::SITE_MAP_REF_MARK)) {
			$this->setLastError("Bad profile for page \"$query\"");
			return false;
		}

		$pageInfo['labels'] = array_merge($pageInfo['labels'], $this->getPublicLabels(true));

		// Solve chained references to labels from other page
		foreach($pageInfo as $key => $value) {
			if (is_string($value)) {
				$newValue = $value;
				while (is_string($newValue) && $this->startsWith($newValue, self::SITE_MAP_REF_MARK)) {
					$refPage = substr($newValue, strlen(self::SITE_MAP_REF_MARK));
					if (!array_key_exists($refPage, $mapPages) || !array_key_exists($key, $mapPages[$refPage])) {
						$this->setLastError("Unable to reference page \"$refPage\" when loading key \"$key\" for \"$query\"");
						return false;
					}
					$newValue = $mapPages[$refPage][$key];
				}
				$pageInfo[$key] = $newValue;
			} else if (is_array($value)) {
				$mergedProp = $pageInfo[$key];
				while(array_key_exists(self::SITE_MAP_REF_MARK, $mergedProp)) {
					$refPage = $mergedProp[self::SITE_MAP_REF_MARK];
					if (!array_key_exists($refPage, $mapPages) || !array_key_exists($key, $mapPages[$refPage])) {
						$this->setLastError("Unable to reference page \"$refPage\" when loading key \"$key\" for \"$query\"");
						return false;
					}
					unset($mergedProp[self::SITE_MAP_REF_MARK]);
					$mergedProp = array_merge($mapPages[$refPage][$key], $mergedProp);
				}
				$pageInfo[$key] = $mergedProp;
			} else {
				$this->setLastError("Unknown type for property \"$key\" in \"$query\"");
				return false;
			}
		}
		return $pageInfo;
	}
	
	// Some error strings in this file are encoded with this method. Ideally, the encoding of this file should
	// be the same as the encoding of the site map file. However, the error messages use standard English characters
	// that are equal in the most common encodings (at least in ANSI and UTF-8).
	public function encodeOutput($string) {
		return htmlentities($string, ENT_QUOTES, $this->getEncoding());
	}
	
	private $widgetsByName = array();

	public function getWidgetInstancesByName($name) {
		if (isset($this->widgetsByName[$name])) return $this->widgetsByName[$name];
		else return null;
	}

	public function loadWidgetClass($widgetName) {
		// The widget is first searched in the local site path. If not found, in the global widget directory.
		$widgetPath = $this->findPathToWidget($widgetName);
		if ($widgetPath === false) throw new Exception('Unable to locate widget "' . $widgetName . '"');

		$widgetFilePath = $widgetPath . $widgetName . '.php';
		if (!file_exists($widgetFilePath)) throw new Exception('Unable to load widget "' . $widgetName . '"');

		if (!require_once($widgetFilePath)) throw new Exception('Error loading class file for widget "' . $widgetName . '"');

		return SiteEngine_Widget::WIDGET_CLASS_PREFIX . $widgetName;
	}

	protected function decodeWidgetCall($callStr, $id) {
		$output = array('className' => '', 'instance' => NULL, 'params' => NULL);
		$widgetInfo = SiteEngine_Widget::deserializeCall($callStr);
		if ($widgetInfo === false) return 'Unable to interpret call to widget: ' . $callStr;

		try {
			$widgetClassName = $this->loadWidgetClass($widgetInfo['name']);
		} catch(Exception $e) {
			return $e->getMessage() . '. Call: ' . $callStr;
		}

		$widget = new $widgetClassName($this, $id);

		// Index widgets by name so they are searchable
		$name = $widgetInfo['name'];
		if (isset($this->widgetsByName[$name])) $this->widgetsByName[$name][] = $widget;
		else $this->widgetsByName[$name] = array($widget);

		$output['instance'] = $widget;
		$output['className'] = $widgetClassName;
		// If the widget is called with a config file, load it and merge the params in the file with the specified params
		if ($widgetInfo['configFile'] != '') {
			$widgetConfig = $widget->loadConfig($widgetInfo['configFile']);
			if ($widgetConfig === false) 
				return 'Cannot load configuration file "' . $widgetInfo['configFile'] . '" for widget "' . $widgetInfo['name'] . '"';
			else
				$widgetInfo['params'] = array_merge($widgetConfig, $widgetInfo['params']);
		}
		$output['params'] = $widgetInfo['params'];
		return $output;
	}

	protected function decodeSiteMapLabel($label) {
		$typeSep = strpos($label, ':');
		if ($typeSep === false) { $labelType = 'text'; $typeSep = -1; }
		else $labelType = substr($label, 0, $typeSep);
		$labelContent = substr($label, $typeSep + 1);
		return array('type' => $labelType, 'content' => $labelContent);
	}

	public function labelHandler($label, $labelInfo) {
		// The label format is [type:]content, where type can be 'text', 'link', 'tpl' or 'widget'
		// Decode labels from the HTML template as they should be HTML-encoded.
		$originalLabel = html_entity_decode($label, ENT_QUOTES, $this->getEncoding());
		$label = SiteEngine_Template::getLabelValueFromArray($labelInfo, $originalLabel);
		if ($label === null) return '';
		$labelInfo = $this->decodeSiteMapLabel($label);
		$labelContent = $labelInfo['content'];
		$output = '';
		switch($labelInfo['type']) {
			default:
			case 'text': $output = $this->encodeOutput($labelContent); break;
			case 'html': $output = $labelContent; break;
			case 'link': $output = $this->encodeOutput($labelContent); break;
			case 'tpl': 
				// The template is first searched in the local site path. If not found, in the global template directory.
				$templatePath = $this->findPathToTemplate($labelContent);
				if ($templatePath !== false) {
					$template = new SiteEngine_Template($templatePath);
					if ($template->isLoaded()) {
						$output = $template->render('labelHandler', $this, $this->pageInfo['labels'], false);
						if ($output === false)
							$output = $this->encodeOutput("Error in template file '$templatePath': " . $template->getLastError());
						$this->currentTemplate->addMovePending($template->getMovePending());
					} else $output = $this->encodeOutput("Unable to load template file \"$templatePath\"");
				} else $output = $this->encodeOutput("Unable to locate template \"$labelContent\"");
				break;
			case 'widget':
				$this->logContext = isset($callInfo['className']) ? $callInfo['className'] : "";
				try {
					$callInfo = $this->decodeWidgetCall($labelContent, $this->query . '/' . $originalLabel);
					if (is_array($callInfo)) {
						$widget = $callInfo['instance'];
						$tmp = $widget->draw($callInfo['params'], $this->requestType, $_REQUEST);
						$this->currentTemplate->addMovePending($widget->getMovePending());
						$output = $tmp !== NULL ? $tmp . $widget->getContent() : $widget->getContent();
					} else {
						$output = $this->encodeOutput($callInfo);
					}
				} catch(Exception $e) {
					error($e->getMessage());
					$output = $e->getMessage();
				}
				$this->logContext = get_class($this);
				break;
		}
		return $output;
	}
	
	public function getLastError() {
		return $this->lastError;
	}

	public function setLastError($error) {
		$this->lastError = $this->encodeOutput($error);
	}
	
	public function getPublicLabels($withPrefixes = false) {
		return array(
				'queryUrl' => ($withPrefixes ? 'link:' : '') . Helpers_Url::getQueryUrl($this->getEncoding()),
				'siteName' => ($withPrefixes ? 'text:' : '') . $this->getName(),
				'baseUrl' => ($withPrefixes ? 'link:' : '') . $this->getBaseUrl(),
				);
	}

	private $currentTemplate;

	public function getCurrentTemplate() {
		return $this->currentTemplate;
	}

	public function render($query) {
		$query = $this->transcodeUtf8Query($query);
		$pageInfo = $this->compilePageInfo($query);
		if ($pageInfo === false) return false;
		$this->pageInfo = $pageInfo;
		$templatePath = $this->findPathToTemplate($pageInfo['template']);
		if ($templatePath !== false) {
			$template = new SiteEngine_Template($templatePath);
			if ($template->isLoaded()) {
				$this->currentTemplate = &$template;
				$output = $template->render('labelHandler', $this, $pageInfo['labels'], true);
				if ($output === false) {
					$this->setLastError("Error in template '$templatePath': " . $template->getLastError());
					return false;
				}
				else {
					return $output; 
				}
			} else {
				$this->setLastError("Unable to load template file \"$templatePath\"");
				return false;
			}
		} else {
			$this->setLastError("Unable to locate template \"" . $pageInfo['template'] . "\"");
			return false;
		}
	}

	public function log($logString, $logCategory, $logContext = false) {
		if ($logContext === false) $logContext = $this->logContext;
		if ($this->logFile === false) $this->logFile = fopen($this->getPathToFile('log.txt'), 'a');
		if ($this->logFile !== false) {
			if ($logContext != '') $formatStr = "%s: %s [%s] %s\n";
			else $formatStr = "%s: %s %s%s\n";
			fprintf($this->logFile, $formatStr, $logCategory, date('d/m/Y H:i:s'), $logContext, $logString);
		}
	}

	public function error($errorString, $logContext = false) {
		$this->log($errorString, 'ERROR', $logContext);
	}

	public function warning($warningString, $logContext = false) {
		$this->log($warningString, 'Warning', $logContext);
	}

	public function info($infoString, $logContext = false) {
		$this->log($infoString, 'Info', $logContext);
	}

	public function virtualPathToReal($vPath) {
		$realPath = $this->getBasePath() . $vPath;
		$fileNotFound1 = !file_exists($realPath);
		$accessDenied1 = $fileNotFound1 ? false : !$this->isFileAccessAllowed($realPath);
		if ($fileNotFound1 || $accessDenied1) {
			// If the file is not in the site directory or access is denied, 
			// try to search in the default site directory
			global $ooweeConfig;
			$baseDir = $ooweeConfig['defaultSiteDirectory'];
			if ($baseDir[strlen($baseDir) - 1] != '/') $baseDir .= '/';
			$realPath = $baseDir . $vPath;
			$fileNotFound2 = !file_exists($realPath);
			$accessDenied2 = $fileNotFound2 ? false : !$this->isFileAccessAllowed($realPath, $ooweeConfig['defaultSiteDirectory']);
			if ($fileNotFound2 || $accessDenied2) {
				global $url;
				if ($accessDenied1 || $accessDenied2) {
					$fileName = basename($realPath);
					warning("Denied access attempt to private file '$fileName'. Query: $url", '');
				}
				$realPath = false;
			}
		}
		return $realPath;
	}

	public function cacheControl($lastChangeTime) {
		$modified = false;
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			$modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
		} else if (function_exists("apache_request_headers")) {
			$headers = apache_request_headers();
			if ($headers !== false) {
				$modified = $headers['If-Modified-Since'];
			}
		}
		if ($modified === false) return true;

		// Inspired on http://www.php.net/manual/en/function.header.php#61903
		// Checking if the client is validating his cache and if it is current.
		if (strtotime($modified) == $lastChangeTime) {
			// Client's cache IS current, so we just respond '304 Not Modified'.
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastChangeTime) . ' GMT', true, 304);
			return false;
		} else {
			// File not cached or cache outdated, we respond '200 OK' and output the data.
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastChangeTime) . ' GMT', true, 200);
			return true;
		}	
	}

	protected function processUIQuery($query) {
		// The query URL-decoded and utf8 encoded
		if ($this->docExists($query)) {
			header('Content-Type: text/html; charset='.$this->getEncoding());
      $output = $this->render($query);
			if ($output !== false) {
        header("Content-Length: ".strlen($output)); // strlen() returns the length of the string in bytes.
        echo($output);
      } else {
				global $ooweeConfig, $ooweeError;
				$ooweeError = $this->getLastError();
        header("Content-Length: ".filesize($ooweeConfig['defaultSiteParams']['ooweeErrorPage']));
				include $ooweeConfig['defaultSiteParams']['ooweeErrorPage'];
			}
		} else {
			// Virtual directory management
			// We serve files taking the site directory as the root
			$realPath = $this->virtualPathToReal($query);
			if ($realPath !== false) {
				$ext = pathinfo($realPath, PATHINFO_EXTENSION);
				if ($ext == 'php') include($realPath);
				else {
					header('Content-Type: ' . Helpers_Mime::fileExtensionToMimeType($ext, $this->getEncoding()));
					header('Content-Length: '. filesize($realPath));
					// Only send file if it's not in client's cache or it's not up to date there
					if ($this->cacheControl(filemtime($realPath))) {
						readfile($realPath);
					}
				}
			} else {
				$this->outputDocNotFound();
			}
		}
	}

	public function outputDocNotFound() {
		header('HTTP/1.0 404 Not Found');
		include SiteEngine_SiteManager::getDefaultUnknownSiteDoc();
	}

	public static function makeLogicQuery($query) {
		global $ooweeConfig;
		return getSite()->getBaseUrl() . $ooweeConfig['logicQueryMarker'] . $query;
	}

	protected function processLogicQuery($query) {
		$error = false; $output = array();
		try {

			// The query is URL-decoded and utf8 encoded. Convert it to the site map encoding.
			$query = $this->transcodeUtf8Query($query);
			// Find doc/label in site map
			$docAndLabelBoundary = strrpos($query, '/');
			if ($docAndLabelBoundary === false) throw new Exception('Bad query format. No slash separator found.');
			$doc = substr($query, 0, $docAndLabelBoundary);
			$label = substr($query, $docAndLabelBoundary + 1);
			$pageInfo = $this->compilePageInfo($doc);
			if ($pageInfo === false) throw new Exception("The document '$doc' does not exist in the site map");
			$labelValue = SiteEngine_Template::getLabelValueFromArray($pageInfo['labels'], (string) $label);
			if ($labelValue === null) throw new Exception("The label '$label' does not exist in the site map");
			// Decode label and check if it is a widget
			$labelInfo = $this->decodeSiteMapLabel($labelValue);
			if ($labelInfo['type'] != 'widget') throw new Exception('The label does not point to a widget');
			// Decode widget call
			$callInfo = $this->decodeWidgetCall($labelInfo['content'], $query);
			if (!is_array($callInfo)) throw new Exception('Bad widget call. Reason: ' . $callInfo);
			// Run widget logic
			$this->logContext = $callInfo['className'];
			$reqParams = array_merge($_REQUEST, array('OOWEE_DOCUMENT' => $doc, 'OOWEE_LABEL' => $label));
			$widget = $callInfo['instance'];
			$output = $widget->process($callInfo['params'], $this->requestType, $reqParams);
			$this->logContext = get_class($this);

		} catch (Exception $e) {
			error($e->getMessage());
			$error = $e->getMessage();
			$this->logContext = get_class($this);
		}

		if ($error || $widget->getProcessOutputContentType() === null) {
			// Transform output to XML
			$output = SiteEngine_Widget::outputToXml($error, $output);
			// Output headers
      // The XML template is at oowee, so the encoding is not that of the site.
      global $ooweeConfig;
			header('Content-Type: ' . Helpers_Mime::fileExtensionToMimeType('xml', $ooweeConfig['sitesConfigEncoding']));
		} else {
			header('Content-Type: ' . $widget->getProcessOutputContentType());
		}

		// TODO: check why adding content-length we get a "corrupt content" error in old versions of firefox
//		header('Content-Length: ', strlen($xmlOutput));		// strlen() returns the number of bytes (not characters)
		if ($error || $widget->isProcessOutputClientCacheDisabled()) {
			header("Expires: 0");
			header("Pragma: no-cache");
			header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
			echo $output;
		} else {
			$modTime = $widget->getProcessOutputModificationTime();
			if ($modTime === false || $this->cacheControl($modTime)) {
				// Send the info, as no modification time was specified or the client cache is out of date
				echo $output;
			}
		}

	}

	private $uiQuery;

	public function isUIQuery() {
		return $this->uiQuery;
	}

	public function addToSection($section, $content) {
		$this->currentTemplate->addMovePending(array(array('destination' => $section, 'content' => $content)));
	}

	public function dispatchQuery($unresolvedQuery, $requestType) {
		if ($this->resolveQueryAlias($unresolvedQuery, $query)) {
			$params = Helpers_Url::urlParamsToArray($query);
			$_REQUEST = array_merge($_REQUEST, $params);
			$_GET = array_merge($_GET, $params);
			// Remove parameters in resolved query and add them to $_REQUEST and $_GET
			$query = str_replace('?'.parse_url($query, PHP_URL_QUERY), '', $query);
		}

		$this->requestType = $requestType;
		$this->query = $query;
		global $ooweeConfig;
		$logicMarker = $ooweeConfig['logicQueryMarker'];
		$this->uiQuery = strpos($query, $logicMarker) !== 0;
		if ($this->uiQuery) {
			$this->processUIQuery($query);
		} else { 
			$this->processLogicQuery(substr($query, strlen($logicMarker))); 
		}
	}

	protected function resolveQueryAlias($query, &$resolvedQuery) {
		$resolvedQuery = $query;
		if (!isset($this->map['queryAliases'])) return false;
		$queryAliases = $this->map['queryAliases'];
		
    // Firts, try resolving the query as is.
		if (isset($queryAliases[$query])) {
      $resolvedQuery = $queryAliases[$query];
      return true;
    } else {
      // If unsuccessful, try removing the last slash, if any.
      $len = strlen($query);
      if ($len > 0 && $query[$len - 1] == '/') {
        $query = substr($query, 0, $len - 1);
        if (isset($queryAliases[$query])) {
          $resolvedQuery = $queryAliases[$query];
          return true;				
        }
      }
    }
		return false;
	}
}

?>
