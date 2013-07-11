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

	protected static function findTagsInfo($xml, $tagInfo, $offset = 0, $limit = false, $isHtml = false) {
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

				// Find closing tag if document is not HTML or the HTML tag is not a void element
				$contentEnd = $tagEnd = false;
				if (!$isHtml || !self::isHtmlVoidElement($tagName)) {
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
	 *	$xml - String with XML document
	 *	$tagDescriptor - Tag descriptor with format: tag_name[attribute=value] (The attribute is optional)
	 *	$offset - Index of the XML string where to start the search
	 *	$limit - Maximum number of tags to find
	 *	$isHtml - If the document is HTML, setting this to true will shorten the execution by not looking for closing tags of 
	 *		void elements
	 * Output:
	 * 	Array of tags in the document according to the specified tag descriptor. Each array entry is an associative array with the 
	 *	following keys:
	 *		[elementStart] Start index of the opening tag
	 *		[contentStart] Start index of the tag contents, or false if the opening tag is not closed with >
	 *		[contentEnd] End index of the tag contents, or false if no closing tag is found or $isHtml is true
	 *		[elementEnd] End index of the closing tag, or false if no closing tag is found or $isHtml is true
	 */
	public static function findTags($xml, $tagDescriptor, $offset = 0, $limit = false, $htmlMode = false) {
		if (preg_match('/^\s*([^\[=\]\/]+)\s*(\[\s*([^\[=\]\/]+)\s*=\s*([^\[=\]\/]*)\s*\])?/i', $tagDescriptor, $matches) === false) return false;
		if (count($matches) < 2) return false;
		$tag = $matches[1];
		$attribute = (count($matches) >= 5 ? $matches[3] : null);
		$value = (count($matches) >= 5 ? ($matches[4] != '*' ? $matches[4] : null) : null);

		return self::findTagsInfo($xml, array('name' => $tag, 'attrName' => $attribute, 'attrValue' => $value), $offset, $limit);
	}

	public static function isHtmlVoidElement($tag) {
		// Void elements in HTML 5.1 spec
		static $voidTags = array(	'area' => '', 'base' => '', 'br' => '', 'col' => '', 'embed' => '', 'hr' => '', 'img' => '', 
						'input' => '', 'keygen' => '', 'link' => '', 'menuitem' => '', 'meta' => '', 'param' => '', 
						'source' => '', 'track' => '', 'wbr' => ''
						);
		return (($tag[strlen($tag) - 1] == '/') || array_key_exists(strtolower($tag), $voidTags));
	}

	public static function isHtmlInlineElement($tag) {
		static $inlineTags = array(	'a' => '', 'i' => '', 'b' => '', 'u' => '', 'big' => '', 'small' => '', 'em' => '', 
						'strong' => '', 'span' => '', 'tt' => '', 'abbr' => '', 'acronym' => '', 'cite' => '',
						'code' => '', 'dfn' => '', 'kbd' => '', 'samp' => '', 'var' => '', 'bdo' => '', 'br' => '',
						'img' => '', 'map' => '', 'object' => '', 'q' => '', 'script' => '', 'span' => '', 'sub' => '',
						'sup' => '', 'button' => '', 'input' => '', 'label' => '', 'select' => '', 'textarea' => ''
						);
		return array_key_exists(strtolower($tag), $inlineTags);
	}

	/**
	 * This function fixes a broken piece of XML code by closing any open quotes and tags
	 *
	 * Parameters:
	 *	$xml - XML code piece
	 *	$htmlMode - If true, it does not close open tags when they are HTML empty elements like <br>, <area>, <input>, etc.
	 *		    If false, it closes any open tag.
	 * Output: Fixed XML code string
	 */
	public static function fixBrokenXml($xml, $htmlMode = false) {
		// Close open quotes
		$i = 0;
		$len = strlen($xml);
		$quotes = 0;
		while($i < $len) {
			$pos = strpos($xml, '"', $i);
			if ($pos !== false) {
				$quotes++;
				$i = $pos + 1;
			} else break;
		}
		if (intval($quotes / 2.0) != $quotes / 2.0) $xml .= '"';

		// Close unfinished tag with >
		$len = strlen($xml);
		$i = $len - 1;
		$inQuotes = false;
		while($i >= 0 && ($inQuotes || ($xml[$i] != '<' && $xml[$i] != '>'))) {
			if ($xml[$i] == '"') $inQuotes = !$inQuotes;
			$i--;
		}
		if ($i >= 0 && $xml[$i] == '<') $xml .= '>';

		// Close any open tags
		$len = strlen($xml);
		$i = 0;
		$openTags = array();
		while($i < $len) {
			$tagStart = strpos($xml, '<', $i);
			if ($tagStart === false) break;
			$i = $tagStart + 1;
			$tagEnd = strpos($xml, '>', $i);
			$i = $tagEnd + 1;
			$tagContent = substr($xml, $tagStart + 1, $tagEnd - $tagStart - 1);
			$tagContentEnd = strpos($tagContent, ' ');
			if ($tagContentEnd === false) $tagName = $tagContent;
			else $tagName = substr($tagContent, 0, $tagContentEnd);
			if (strlen($tagName) > 0 && $tagName[0] == '/') {
				$tagName = substr($tagName, 1);
				if ($openTags[count($openTags) - 1] == $tagName) unset($openTags[count($openTags) - 1]);
			} else {
				if (!$htmlMode || !self::isHtmlVoidElement($tagName))
					$openTags[count($openTags)] = $tagName;
			}
		}
		for ($i = count($openTags) - 1; $i >= 0; $i--) 
			$xml .= '</'.$openTags[$i].'>';

		return $xml;
	}

	/**
	 * This function cuts a piece of the first N characters in an XML code string while keeping it syntatically valid. If the cutting
	 * point is in the middle of an XML tag, the tag is completely added, but not its content.
	 *
	 * Parameters:
	 *	$xml - XML code string
	 *	$maxChars - Number of characters that will be kept in the summary (it includes tags)
	 *	$htmlMode - If true, it does not close open tags when they are HTML empty elements like <br>, <area>, <input>, etc.
	 *		    If false, it closes any open tag.
	 *	$addEllipsis - 	If true, an ellipsis is added at the cutting point. The ellipsis is merged with any existing points at 
	 *			the cutting point so no more than three points appear at the end of the summary.
	 * Output: XML piece of code. It may be longer than maxChars due to extra code to keep XML correctness.
	 */
	public static function xmlCut($xml, $maxChars, $htmlMode = false, $addEllipsis = true) {
		$cutPoint = $maxChars;

		// Check if the cut position is between quotes
		$i = 0;
		$len = $cutPoint;
		$quotes = 0;
		while($i < $len) {
			$pos = strpos($xml, '"', $i);
			if ($pos !== false && $pos < $len) {
				$quotes++;
				$lastQuotePos = $pos;
				$i = $pos + 1;
			} else break;
		}
		if (intval($quotes / 2.0) != $quotes / 2.0) {
			// There's an open quote before the cut point
			$cutPoint = $lastQuotePos + 1;
		}

		// Look for the last opening tag before the cut point
		$i = $cutPoint - 1;
		while($i >= 0 && $xml[$i] != '<' && $xml[$i] != '>') $i--;
		if ($i >= 0 && $xml[$i] == '<') {
			// The cut point is in a opening tag. Move cut point to the closing mark.
			$inQuotes = false;
			$tagEndMark = false;
			$len = strlen($xml);
			while($i < $len) {
				if ($xml[$i] == '"') $inQuotes = !$inQuotes;
				else if ($xml[$i] == '>' && !$inQuotes) {
					$tagEndMark = $i;
					break;
				}
				$i++;
			}
			if ($tagEndMark === false) $cutPoint = $len;
			else $cutPoint = $tagEndMark + 1;
		}

		$text = substr($xml, 0, $cutPoint);

		if ($addEllipsis && $cutPoint < strlen($xml)) {
			$i = $cutPoint - 1;
			$pointsToAdd = 3;
			while ($i >= 0 && $pointsToAdd > 0 && $text[$i] == '.') {
				$i--;
				$pointsToAdd--;
			}
			for ($i = 0; $i < $pointsToAdd; $i++) $text .= '.';
		}

		return self::fixBrokenXml($text, $htmlMode);
	}

	public static function removeTags($xml, $tagDescriptor, $offset = 0, $isHtml = false) {
		$tags = self::findTags($xml, $tagDescriptor, $offset, false, $isHtml);
		$lastIndex = 0;
		$output = '';
		foreach($tags as $tag) {
			$output .= substr($xml, $lastIndex, $tag['elementStart'] - $lastIndex);
			if ($tag['elementEnd'] !== false) $lastIndex = $tag['elementEnd'];
			else $lastIndex = $tag['contentStart'];
		}
		$output .= substr($xml, $lastIndex);
		return $output;		
	}

	/**
	 * This functions extracts a summary of an HTML document. A text summary of the specified maximum length is extracted from the
	 * contents of document paragraphs marked by <p> tags.
	 *
	 * Parameters:
	 * 	$html - HTML code string
	 *	$maxChars - Maximum number of characters in the summary
	 *	$minCharsPerParagraph - If a paragraph is cut, this number of character will be kept at least
	 *	$minLengthToIncludeParagraph - Minimum number of characters that a paragraph must have to be included in the summary
	 * Output: 
	 *	Document summary of APPROXIMATELY $maxChars characters.
	 */
	public static function htmlTextSummary($html, $maxChars, $minCharsPerParagraph = 256, $minLengthToIncludeParagraph = 100) {
		$html = self::removeTags($html, 'img', 0, true);
		$html = self::removeTags($html, 'form', 0, true);
		$html = self::removeTags($html, 'input', 0, true);
		$html = self::removeTags($html, 'select', 0, true);
		$html = self::removeTags($html, 'textarea', 0, true);
		$html = self::removeTags($html, 'button', 0, true);

		$paragraphs = self::findTagsInfo($html, array('name' => 'p', 'attrName' => null, 'attrValue' => null));
		$summary = '';
		if (count($paragraphs) == 0) return $summary;
		$charsPerParagraph = ceil($maxChars / count($paragraphs));
		if ($charsPerParagraph < $minCharsPerParagraph) $charsPerParagraph = $minCharsPerParagraph;
		foreach($paragraphs as $p) {
			$pContent = substr($html, $p['contentStart'], $p['contentEnd'] - $p['contentStart']);
			$pLen = strlen($pContent);
			if ($pLen >= $minLengthToIncludeParagraph) {
				if ($pLen > $charsPerParagraph * 1.1) {
					$pSummary = self::xmlCut($pContent, $charsPerParagraph, true, true);
				} else {
					$pSummary = $pContent;
				}
				$summary .= '<p>'.$pSummary."</p>\n";
				$maxChars -= strlen($pSummary);
				if ($maxChars <= 0) break;
			}
		}
		return $summary;
	}

	protected static function styleValueToPixels($value) {
		$w = trim($value);
		$l = strlen($w);
		$pixels = false;
		if ($l > 0) {
			if ($w[$l - 1] == '%') $pixels = doubleval(substr($w, 0, $l - 1)) * (16.0 / 100.0);
			else if ($l >= 3 && substr($w, $l - 2) == 'em') $pixels = doubleval(substr($w, 0, $l - 2)) * 16.0;
			else if ($l >= 3 && substr($w, $l - 2) == 'px') $pixels = doubleval(substr($w, 0, $l - 2));
			else if (is_numeric($w)) $pixels = doubleval($w);
		}
		return $pixels;
	}

	public static function getImagesInHtml($html) {
		$imgs = self::findTagsInfo($html, array('name' => 'img', 'attrName' => null, 'attrValue' => null));
		$output = array();
		foreach($imgs as $img) {
			$contentStart = $img['contentStart'] !== false ? $img['contentStart'] : strlen($html);
			$tag = substr($html, $img['elementStart'], $img['contentStart'] - $img['elementStart']);
			$attrs = self::getTagAttributes($tag);
			$src = isset($attrs['src']) ? $attrs['src'] : false;
			if ($src !== false) {
				$width = isset($attrs['width']) ? (is_numeric($attrs['width']) ? intval($attrs['width']) : false) : false;
				$height = isset($attrs['height']) ? (is_numeric($attrs['height']) ? intval($attrs['height']) : false) : false;
				if (isset($attrs['style'])) {
					$styleParams = Helpers_String::varStringToAssocArray($attrs['style'], ';', ':');
					if (isset($styleParams['width'])) {
						$w = self::styleValueToPixels($styleParams['width']);
						if ($w !== false) $width = $w;
					}
					if (isset($styleParams['height'])) {
						$h = self::styleValueToPixels($styleParams['height']);
						if ($h !== false) $height = $h;
					}
				}
				$output[] = array('src' => $src, 'width' => $width, 'height' => $height);
			}
		}
		return $output;
	}
}

?>
