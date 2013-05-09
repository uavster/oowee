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

	public static function getTagAttributes($tagXml, &$attrOffsets = false) {
		if (preg_match('/^\s*<[^\s"<>=]+(.*)>\s*$/', $tagXml, $attrStr, PREG_OFFSET_CAPTURE) === false) return false;
		if (count($attrStr) < 2) return array();		
		if (preg_match_all('/([^\s"<>=]+)\s*=\s*"([^"]*)(")/i', $tagXml, $attributes, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, $attrStr[1][1]) === false) return false;
		$output = array();
		if ($attrOffsets !== false) $attrOffsets = array();
		foreach($attributes as $attr) {
			$name = strtolower($attr[1][0]);
			if (array_key_exists($name, $output)) return false;
			$output[$name] = $attr[2][0];
			if (is_array($attrOffsets)) $attrOffsets[$name] = array('contentStart' => $attr[2][1], 'contentEnd' => $attr[3][1]);
		}
		return $output;
	}

	protected static function findTagsInfo($xml, $tagInfo, $offset = 0, $limit = false) {
		$output = array();
		$tagName = strtolower($tagInfo['name']);
		$tagStartStr = '<' . $tagName;
		$tagCloseStr = '</' . $tagName . '>';
		$index = 0;
		$xml = substr($xml, $offset);
		$xmlLength = strlen($xml);
		$attrName = $tagInfo['attrName'] !== null ? strtolower($tagInfo['attrName']) : null;
		$attrValue = $tagInfo['attrValue'];

		while($index < $xmlLength && ($tagStart = stripos($xml, $tagStartStr, $index)) !== false) {
			$tagStartEnd = strpos($xml, '>', $tagStart + strlen($tagStartStr));
			if ($tagStartEnd === false) return false;

			$attributes = self::getTagAttributes(substr($xml, $tagStart, $tagStartEnd - $tagStart + 1));
			if ($attributes === false) return false;

			if ($attrName === null || (array_key_exists($attrName, $attributes) && ($attrValue === null || $attributes[$attrName] == $attrValue))) {
				$contentStart = $tagStartEnd + 1;
				if ($contentStart >= $xmlLength) $contentStart = false;

				// Find closing tag
				$contentEnd = $tagEnd = false;
				if ($contentStart !== false) {
					$nextClosingTag = false;
					$depth = 1;
					$index2 = $contentStart;
					$foundClosure = false;

					while($index2 < $xmlLength) {
						$nextStartTag = stripos($xml, $tagStartStr, $index2);
						$nextClosingTag = stripos($xml, $tagCloseStr, $index2);
						if ($nextClosingTag === false) break;

						if ($nextStartTag === false || $nextClosingTag <= $nextStartTag) {
							$index2 = $nextClosingTag + strlen($tagStartStr);
							$depth--;
						} else {
							$index2 = strpos($xml, '>', $nextStartTag + strlen($tagStartStr));
							if ($index2 === false) break;
							$depth++;
						}

						if ($depth == 0) {
							$foundClosure = $nextClosingTag;
							break;
						}
					}

					if ($foundClosure !== false) {
						$contentEnd = $foundClosure;
						$tagEnd = $foundClosure + strlen($tagCloseStr);
					}
				}

				$output[] = array('elementStart' => $tagStart + $offset, 'contentStart' => ($contentStart !== false ? $contentStart + $offset : false), 'contentEnd' => ($contentEnd !== false ? $contentEnd + $offset : false), 'elementEnd' => ($tagEnd !== false ? $tagEnd + $offset : false));
				if ($limit !== false && count($output) >= $limit) break;
			}

			$index = $tagStartEnd + 1;
		}

		return $output;
	}

	/**
	 * It returns information about the specified tags in the XML document.
	 *
	 * Parameters:
	 *	$xml - Array with XML document
	 *	$tagDescriptor - Tag descriptor with format: tag_name[attribute=value] (The attribute is optional)
	 */
	public static function findTags($xml, $tagDescriptor, $offset = 0, $limit = false) {
		if (preg_match('/^\s*([^\[=\]\/]+)\s*(\[\s*([^\[=\]\/]+)\s*=\s*([^\[=\]\/]*)\s*\])?/i', $tagDescriptor, $matches) === false) return false;
		if (count($matches) < 2) return false;
		$tag = $matches[1];
		$attribute = (count($matches) >= 5 ? $matches[3] : null);
		$value = (count($matches) >= 5 ? ($matches[4] != '*' ? $matches[4] : null) : null);

		return self::findTagsInfo($xml, array('name' => $tag, 'attrName' => $attribute, 'attrValue' => $value), $offset, $limit);
	}
}

?>
