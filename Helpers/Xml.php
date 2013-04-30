<?php

class Helpers_Xml {

	const TAB_SIZE = 4;

	public static function arrayToXml($data, $indent = 0, $result = '') {
		$tabs = str_pad('', $indent * self::TAB_SIZE);
		$result = '';
		foreach($data as $key => $value) {
			$textKey = is_int($key) ? "__i__$key" : $key;
			if (!is_array($value)) {
				$result .= $tabs . "<$textKey>".htmlspecialchars($value)."</$textKey>\n";
			} else {
				$result .= $tabs . "<$textKey>\n" . self::arrayToXml($value, $indent + 1, $result) . $tabs . "</$textKey>\n";
			}
		}
		return $result;
	}

}

?>
