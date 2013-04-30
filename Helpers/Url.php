<?php

require '3rdparty/url_to_absolute.php';

class Helpers_Url {

	public static function getQueryProtocol() {
		$proto = 'http';
		if ($_SERVER['HTTPS'] == 'on') $proto .= 's';
		return $proto;
	}

	public static function getQueryUrl($encoding = 'UTF-8') {
		$pageURL = self::getQueryProtocol() . '://';
		if ($_SERVER['SERVER_PORT'] != "80") {
			$pageURL .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		// Output decoded URL as with $_GET or $_REQUEST. The final encoding is as requested.
		return iconv('UTF-8', $encoding, urldecode($pageURL));
	}

	public static function arrayToUrlParams($array) {
		$output = '';
		foreach($array as $key => $value) {
			if ($output != '') $output .= '&';
			$output .= urlencode($key).'='.urlencode($value);
		}
		return $output;
	}

	public static function setProtocol($url, $proto) {
		$protoSep = strpos($url, '://');
		if ($protoSep === false) $url = $proto . '://' . $url;
		else $url = $proto . '://' . substr($url, $protoSep + 3);
		return $url;
	}

	public static function toAbsolute($baseUrl, $relativeUrl) {
		return url_to_absolute($baseUrl, $relativeUrl);
	}

	public static function isAbsolute($url) {
		return $url[0] == '/' || strpos($url, '://') !== false;
	}

	public static function stringToRef($str) {
		$pregs = array(
				'/á|à|ä|â/', '/é|è|ë|ê/', '/í|ì|ï|î/', '/ó|ò|ö|ô/', '/ú|ù|ü|û/',
				'/Á|À|Ä|Â/', '/É|È|Ë|Ê/', '/Í|Ì|Ï|Î/', '/Ó|Ò|Ö|Ô/', '/Ú|Ù|Ü|Û/',
				'/[\/,\s,\?,¿,:,\\\]+/', '/&+/',
				);
		$reps = array(
				'a', 'e', 'i', 'o', 'u',
				'A', 'E', 'I', 'O', 'U',
				'-', '-and-',
				);
		return trim(preg_replace('/-+/', '-', preg_replace($pregs, $reps, $str)), '-');
	}

	protected static function addParam($url, $param_name, $param_value) {
		$result = $url;
		$param_separator_pos = strpos($url, "?");
		if (is_bool($param_separator_pos)) $result .= "?";
		else if (trim(substr($url, $param_separator_pos)) != "?") $result .= "&";
		$result .= $param_name . "=" . urlencode($param_value);
		return $result;
	}

	public static function getParam($url, $param_name) {
		$url_parts = parse_url($url);
		if (is_bool($url_parts)) return;
		$parameters = explode("&", urldecode($url_parts['query']));
		$param_name = strtolower($param_name);
		foreach($parameters as $parameter) {
			$param_parts = explode("=", $parameter);
			if (strcmp(strtolower($param_parts[0]), $param_name) == 0) {
				$result = array();
				if (!is_bool(strpos($url, "&".$parameter)))
					$result['separator'] = "&";
				else if (!is_bool(strpos($url, "?".$parameter)))
					$result['separator'] = "?";
				$result['string'] = $parameter;
				$result['name'] = $param_parts[0];
				$result['value'] = $param_parts[1];
				return $result;
			}
		}
		return NULL;
	}

	public static function setParam($url, $param_name, $param_value) {
		$found_param = self::getParam($url, $param_name);
		if ($found_param == NULL) return self::addParam($url, $param_name, $param_value);
		else {
			return str_replace($found_param['separator'].$found_param['string'], $found_param['separator'].$found_param['name'] . "=" . urlencode($param_value), $url);
		}
	}

	public static function delParam($url, $param_name) {
		$found_param = self::getParam($url, $param_name);
		if ($found_param != NULL) {
			$url = str_replace($found_param['separator'].$found_param['string'], "", $url);
			if ($found_param['separator'] == "?") {
				$pos = strpos($url, "&");
				if (!is_bool($pos)) $url[$pos] = "?";
			}
			return $url;
		}
		else return $url;
	}

}

?>
