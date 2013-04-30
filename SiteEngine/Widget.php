<?php
require_once dirname(__FILE__).'/../Base/ClassLoader.php';
require_once dirname(__FILE__).'/../DefaultSite/widgets/CMS/CMS.php';

class SiteEngine_Widget {
	const WIDGET_CLASS_PREFIX = 'Widget_';
	
	const SERIALCALL_PARAMS_START_MARK = '(';
	const SERIALCALL_PARAMS_END_MARK = ')';
	const SERIALPARAMS_PARAM_SEPARATOR = ',';
	const SERIALPARAMS_NAME_VALUE_SEPARATOR = '=';
	const SERIALCALL_CONFIG_START_MARK = '[';
	const SERIALCALL_CONFIG_END_MARK = ']';

	const AJAX_LIB_REF = 'js/ajax.js';
	const URL_LIB_REF = 'js/url.js';
	const CLASS_LIB_REF = 'js/class.js';
	const FORM_DATA_COMPAT_LIB_REF = 'js/form_data.js';
	const UTILS_LIB_REF = 'js/utils.js';

	private $site;
	private $id;
	private static $addedScriptRefs = array();
	private $processOutputContentType = null;
	private $processOutputClientCacheDisabled = true;

	function __construct($site, $id) {
		$this->site = $site;
		$this->id = preg_replace('/\/\/+/', '/', $id);
	}

	protected function getSite() {
		return $this->site;
	}

	public function getId() {
		return $this->id;
	}

	public function getBaseUrl() {
		$reflector = new ReflectionClass(get_class($this));
		return $this->site->getAbsoluteUrlForPath(dirname($reflector->getFileName()));
	}

	public function setProcessOutputContentType($type) {
		$this->processOutputContentType = $type;
	}

	public function getProcessOutputContentType() {
		return $this->processOutputContentType;
	}

	public function enableProcessOutputClientCache($e = true) {
		$this->processOutputClientCacheDisabled = !$e;
	}

	public function disableProcessOutputClientCache($e = true) {
		$this->processOutputClientCacheDisabled = $e;
	}

	public function isProcessOutputClientCacheDisabled() {
		return $this->processOutputClientCacheDisabled;
	}

	private static function findConfigRef($str) {
		$parts = explode(self::SERIALCALL_CONFIG_START_MARK, $str);
		if (count($parts) == 1) { 
			$output['start'] = trim($str); 
			$output['config'] = ''; 
		} else { 
			$confStr = $parts[1];
			if ($confStr[strlen($confStr) - 1] == self::SERIALCALL_CONFIG_END_MARK) {
				$output['start'] = trim($parts[0]);
				$output['config'] = trim(substr($confStr, 0, strlen($confStr) - 1));
			} else {
				$output['start'] = trim($str);
				$output['config'] = '';
			}
		}
		return $output;
	}

	public static function deserializeCall($callString) {
		// Separate widget name from parameters
		$nameEnd = strpos($callString, self::SERIALCALL_PARAMS_START_MARK);
		if ($nameEnd === false) {
			$nameString = trim($callString);
			$paramString = '';
		}
		else {
			$nameString = trim(substr($callString, 0, $nameEnd));
			$paramsStart = $nameEnd + strlen(self::SERIALCALL_PARAMS_START_MARK);
			$paramString = trim(substr($callString, $paramsStart, strlen($callString) - $paramsStart));
			if ($paramString[strlen($paramString) - 1] == self::SERIALCALL_PARAMS_END_MARK)
				$paramString = substr($paramString, 0, strlen($paramString) - 1);
		}
		// Search configuration file in name and parameters
		$confInName = self::findConfigRef($nameString);
		$confInParams = self::findConfigRef($paramString);
		if ($confInName['config'] != '' && $confInParams['config'] != '') return false;
		if ($confInName['config'] != '') {
			$output['configFile'] = $confInName['config'];
			$nameString = $confInName['start'];
		} else if ($confInParams['config'] != '') {
			$paramString = $confInParams['start'];
			if ($paramString[strlen($paramString) - 1] == self::SERIALCALL_PARAMS_END_MARK)
				$paramString = substr($paramString, 0, strlen($paramString) - 1);
			$output['configFile'] = $confInParams['config'];			
		} else $output['configFile'] = '';
		$output['name'] = $nameString;
		$output['params'] = Helpers_String::varStringToAssocArray($paramString, self::SERIALPARAMS_PARAM_SEPARATOR, self::SERIALPARAMS_NAME_VALUE_SEPARATOR);
		return $output;
	}
	
	private $movePending = array();

	public function addMovePending($mp) {
		$this->movePending = array_merge($this->movePending, $mp);
	}

	public function getMovePending() {
		return $this->movePending;
	}

	public static function classNameToWidgetName($className) {
		if (strpos($className, self::WIDGET_CLASS_PREFIX) === 0) return substr($className, strlen(self::WIDGET_CLASS_PREFIX));
		else return false;
	}

	public function getName() {
		return self::classNameToWidgetName($this);
	}

	public function render($templateName, $labelHandlerOrArray = 'onTemplateLabel', $optArgs = NULL) {
		$templatePath = $this->getSite()->findPathToTemplate($templateName);
		if ($templatePath === false) return "Unable to find full path for template \"$templateName\" in widget";
		$template = new SiteEngine_Template($templatePath);
		if ($template->isLoaded()) {
			$output = $template->render($labelHandlerOrArray, $this, $optArgs);
			$this->addMovePending($template->getMovePending());
			if ($output === false) return $template->getLastError();
			else return $output;
		} else {
			return "Unable to load template file \"$templatePath\" in widget";
		}
	}

	public function loadConfig($configFileName) {
		$configPath = $this->getSite()->getPathToFile($configFileName);
		if (!file_exists($configPath)) return false;
		$result = (include $configPath) == 1;
		if (!$result || !isset($config)) return false;
		return $config;
	}

	protected static function xmlTemplate($error, $content) {
		$isError = $error !== false ? '1' : '0';
		$error = $error !== false ? $error : 'No error';
		return ''
			. '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
			. "<root>\n"
			. "    <error>$isError</error>\n"
			. "    <errorstring>$error</errorstring>\n"
			. "    <data>\n"
			. $content
			. "    </data>\n"
			. "</root>"
			;
	}

	public static function outputToXml($error, $output) {
		$xmlData = '';
		if ($error === false) {
			if (is_array($output)) {
				$xmlData = Helpers_Xml::arrayToXml($output, 2);
				$error = false;
			} else {
				$xmlData = '';
				$error = 'Widget output is not an array';
			}
		}
		return self::xmlTemplate($error, $xmlData);
	}

	private $content = '';

	public function getContent() {
		return $this->content;
	}

	public function outputHtml($html) {
		$this->content .= $html;
	}

	public function outputText($text) {
		$this->outputHtml($this->site->encodeOutput($text));
	}

	public function outputTemplate($templateName, $arrayOrLabelHandler = array(), $optArgs = NULL) {
		$this->outputHtml($this->render($templateName, $arrayOrLabelHandler, $optArgs));
	}

	protected function addToHead($content) {
		$this->addMovePending(array(array('destination' => 'head', 'content' => $content)));
	}

	public function outputScriptRef($ref, $lang = 'javascript') {
		$classRef = self::CLASS_LIB_REF;
		if (!Helpers_Url::isAbsolute($classRef)) {
			$realPath = $this->site->virtualPathToReal($classRef);
			if ($realPath === false) throw new Exception("Path to '$classRef' not found");
			$classRef = $this->site->getAbsoluteUrlForPath($realPath, true);
		}
		if (!array_key_exists($classRef, self::$addedScriptRefs)) {
			$this->addToHead("<script type=\"text/$lang\" src=\"$classRef\"></script>\n");
			self::$addedScriptRefs[$classRef] = 0;
		}
		if (!Helpers_Url::isAbsolute($ref)) {
			$realPath = $this->site->virtualPathToReal($ref);
			if ($realPath === false) throw new Exception("Path to '$ref' not found");
			$ref = $this->site->getAbsoluteUrlForPath($realPath, true);
		}
		if (!array_key_exists($ref, self::$addedScriptRefs)) {
			$this->addToHead("<script type=\"text/$lang\" src=\"$ref\"></script>\n");
			self::$addedScriptRefs[$ref] = 0;
		}
	}

	private $functionIndex = 0;

	public function outputScriptCode($code, $codeInFunction = false, $lang = 'javascript') {
		$this->outputScriptRef(self::CLASS_LIB_REF);
		$output = '';
		$output .= "<script type=\"text/$lang\">\n";
		if ($codeInFunction !== false) {
			if ($codeInFunction === true) {
				$functionCall = '__' . str_replace('/', '_', $this->id) . '_autofunc' . ($this->functionIndex++);
			} else {
				$functionCall = $codeInFunction;
			}
			$paramsStart = strpos($functionCall, '(');
			$paramsEnd = strrpos($functionCall, ')');
			if ($paramsStart === false && $paramsEnd === false) {
				$functionName = $functionCall;
				$functionCall .= '()';
			} else {
				$functionName = substr($functionCall, 0, $paramsStart);
			}
			
			$output .= "function $functionCall {\n";
		}
		$output .= $code;
		if ($codeInFunction !== false) {
			$output .= "\n}";
		}
		$output .= "\n</script>\n";

		$this->addToHead($output);

		return $codeInFunction !== false ? trim($functionName) : false;
	}

	public function outputOnLoadScriptCode($code) {
		$this->outputScriptRef(self::UTILS_LIB_REF);
		$this->outputScriptCode('addOnLoadHandler(function() { ' . $code . ' })');
	}

	public function outputScriptFile($file) {
		$code = file_get_contents($file);
		if ($code !== false)
			$this->outputScriptCode("\n".$code);
	}

	public function outputAsyncProcessCall($responseHandler, $requestParamsArray = false, $codeInFunction = false, $formIdInClient = false, $extraUrl = '', $outputFormatInClient = 'array') {
		$this->outputScriptRef(self::CLASS_LIB_REF);
		$this->outputScriptRef(self::AJAX_LIB_REF);
		$this->outputScriptRef(self::FORM_DATA_COMPAT_LIB_REF);
		$baseUrl = getSite()->getBaseUrl();
		if ($requestParamsArray !== false) {
			$paramStr = Helpers_Url::arrayToUrlParams($requestParamsArray);
			if ($paramStr != '') $cmd = $this->id . $extraUrl . '&' . $paramStr;
			else $cmd = $this->id . $extraUrl;
		} else $cmd = $this->id . $extraUrl;
		global $ooweeConfig;
		$marker = $ooweeConfig['logicQueryMarker'];
		$handlerCode = $outputFormatInClient != 'array' ? $responseHandler : "function(response, ajax) { return $responseHandler(ajax.xmlToArray(response), ajax) }";
		if ($formIdInClient === false) {
			$code = "(new Ajax()).requestAsync('$baseUrl$marker$cmd', $handlerCode);";
		} else {
			$code = "(new Ajax()).postFormAsync('$baseUrl$marker$cmd', $handlerCode, null, '$formIdInClient')";
		}
		return $this->outputScriptCode($code, $codeInFunction);
	}

	/**
	 * Builds a hidden input tag
	 * Input:
	 *	$inputs: Associative array with (key => value) pairs, where 'key' is the field name and 'value' is the initial value.
	 */
	public function outputHiddenInputs($inputs) {
		$linear = $this->arrayNestedToLinear($inputs);
		foreach($linear as $name => $value) {
			$defaultValue = htmlentities($value);
			$this->outputHtml("<input type=\"hidden\" name=\"$name\" id=\"$name\" value=\"$defaultValue\">\n");
		}		
	}

	/**
	 * Builds a form with hidden inputs. Browser scripts can copy data to the hidden fields and then start an asynchronous request.
	 * Input:
	 * 	$formName : Name and ID of the form. Client scripts can access the form with document.getElementById(formName) or document.formName
	 *	$fields : Associative array with (key => value) pairs, where 'key' is the field name and 'value' is the initial value.
	 *	$acceptCharset : If not NULL, it defines the 'accept-charset' parameter of the form. If NULL, the parameter is not included.
	 *	$action : 'action' parameter of the form
	 *	$method : 'method' parameter of the form
	 */
	public function outputHiddenForm($formName, $fields = array(), $acceptCharset = 'UTF-8', $action = '', $method = 'post') {
		$acceptCode = $acceptCharset === NULL ? '' : " accept-charset=\"$acceptCharset\"";
		$this->outputHtml("<form name=\"$formName\" id=\"$formName\" action=\"$action\" method=\"$method\"$acceptCode>\n");
		$this->outputHiddenInputs($fields);
		$this->outputHtml("</form>\n");
	}

	protected function arrayToScript($name, $array) {
		$code = "var $name = [";
		$values = '';
		foreach($array as $element) {
			if ($values != '') $values .= ',';
			$values .= "'" . addslashes($element) . "'";
		}
		$code .= $values . "];\n";
		return $code;
	}

	protected function assocArrayToScript($name, $array) {
		$code .= "var $name = {";
		$values = '';
		foreach($array as $key => $element) {
			if ($values != '') $values .= ',';
			$values .= "'" . $key . "':'" . addslashes($element) . "'";
		}
		$code .= $values . "};\n";
		return $code;
	}

	protected function outputFieldOpFunction($startCode, $kernelCode, $endCode, $sourceIds, $destIds, $readMethods, $functionName) {
		$code = '';
		$code .= $this->arrayToScript('s', $sourceIds);
		$code .= $this->arrayToScript('d', $destIds);
		$code .= $this->assocArrayToScript('m', $readMethods);

		$code .= ($startCode != '' ? "$startCode;\n" : '');
		$code .= "for (var i = 0; i < s.length; i++) {\n";
		$code .= "	var c = document.getElementById(s[i]);\n";
		$code .= "	var sv;\n";
		$code .= " 	if (!(s[i] in m)) sv = c.value ? c.value : c.innerHTML;\n";
		$code .= "	else sv = eval(m[s[i]]);\n";
		$code .= "	var dc = document.getElementById(d[i]);\n";
		$code .= "	$kernelCode;\n";
		$code .= "}";
		$code .= ($endCode != '' ? "\n$endCode;" : '');
		$this->outputScriptCode($code, $functionName);
	}

	/**
	 * Builds code to copy the content of DOM elements to a set of input fields
	 * Input:
	 * 	$sourceIds : Array with the source DOM element ids.
	 *	$destIds : Array with the destination input ids.
	 *	$readMethods : 	Associative array where keys are source DOM element ids and values are script code to execute
	 *			to read the element's content. If the read method is not specified for an element, the default
	 *			behavior tries c.value for inputs and c.innerHTML for the rest, where c is the element.
	 *	$functionName : Name of the function. If not specified, it will be automatically picked.
	 * Output:
	 *	Name of the function
	 */
	public function outputFieldCopyFunction($sourceIds, $destIds, $readMethods = array(), $functionName = false) {
		$this->outputFieldOpFunction('', 'dc.value = sv', '', $sourceIds, $destIds, $readMethods, $functionName);
	}

	/**
	 * Builds code to compare the content of DOM elements to corresponding input fields in a set. The built function returns
	 * an array where each element is the result (true or false) of a one-to-one comparison.
	 * Input:
	 * 	$sourceIds : Array with the DOM element ids.
	 *	$destIds : Array with the input ids.
	 *	$readMethods : 	Associative array where keys are source DOM element ids and values are script code to execute
	 *			to read the element's content. If the read method is not specified for an element, the default
	 *			behavior tries c.value for inputs and c.innerHTML for the rest, where c is the element.
	 *	$compareMethods : Associative array where keys are source DOM element ids and values are script code to execute
	 *			to get the result of the comparison.
	 *	$functionName : Name of the function. If not specified, it will be automatically picked.
	 * Output:
	 *	Name of the function
	 */
	public function outputFieldCompareFunction($sourceIds, $destIds, $readMethods = array(), $functionName = false, $compareMethods = array()) {
		$startCode = $this->assocArrayToScript('cm', $compareMethods);
		$this->outputFieldOpFunction($startCode . 'var result = []', 'result.push((s[i] in cm) ? eval(cm[s[i]]) : dc.value == sv)', 'return result', $sourceIds, $destIds, $readMethods, $functionName);
	}

	public function arrayNestedToLinear($nested, $levelSeparator = '__', $parentCategory = '', $output = array()) {
		foreach($nested as $name => $value) {
			$fullName = $parentCategory == '' ? $name : ($parentCategory . $levelSeparator . $name);
			if (!is_array($value)) {
				$output[$fullName] = $value;
			} else {
				$output = $this->arrayNestedToLinear($value, $levelSeparator, $fullName, $output);
			}
		}
		return $output;
	}

	public function arrayLinearToNested($linear, $levelSeparator = '__') {
		$output = array();
		foreach($linear as $name => $value) {
			$levels = split($levelSeparator, $name);
			$currentArray = &$output;
			for ($i = 0; $i < count($levels); $i++) {
				$currentLevel = $levels[$i];
				if ($i == count($levels) - 1) {
					$currentArray[$currentLevel] = $value;
				} else {
					if (!array_key_exists($currentLevel, $currentArray)) {
						$currentArray[$currentLevel] = array();
					}
					$currentArray = &$currentArray[$currentLevel];
				}
			}
		}
		return $output;
	}

	public function getCMSMediaUrlByRef($ref) {
		return Widget_CMS::getMediaUrl($ref);
	}

	public function getCMSUploadMediaUrl() {
		return Widget_CMS::getMediaUrl('0', 'new');
	}

	public function getCMSBrowseMediaUrl($onSelectHandler = null, $handlerExtraParams = null) {
		return Widget_CMS::getBrowserUrl($onSelectHandler, $handlerExtraParams);
	}
}

?>
