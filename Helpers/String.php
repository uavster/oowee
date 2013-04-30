<?php
require_once dirname(__FILE__).'/../Base/ClassLoader.php';

class Helpers_String {

	public static function tokenizeQuoted($string, $separator)
	{
		$i = 0;
		$len = strlen($string);
		$output = array();
		$j = 0;
		$last_word_start = 0;
		$last_word_end = -1;
		$in_quotes = false;
		while($i < $len) {
			if ($in_quotes) {
				if ($string[$i] == '"') $in_quotes = false;
			} else {
				if ($string[$i] == '"') {
					$in_quotes = true;
				} else {
					if ($string[$i] == $separator) $last_word_end = $i;
				}
			}
			$i++;
			if ($i >= $len) $last_word_end = $i;
			if ($last_word_end >= 0) {
				$output[$j++] = substr($string, $last_word_start, $last_word_end - $last_word_start);
				$last_word_start = $last_word_end + 1;
				$last_word_end = -1;
			}
		}
		return $output;
	}
	
	public static function varStringToAssocArray($string, $varSeparator, $nameValueSeparator) {
		$output = array();
		$parts = self::tokenizeQuoted($string, $varSeparator);		
		foreach($parts as $part) {
			$pair = explode($nameValueSeparator, str_replace('"', '', $part));
			if (count($pair) != 2) return FALSE;
			$varName = trim($pair[0]);
			$output[$varName] = $pair[1];
		}
		return $output;
	}

}

?>