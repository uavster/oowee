<?php
require_once dirname(__FILE__).'/../Base/ClassLoader.php';

class SiteEngine_Template {
	const LABEL_START_MARK = '<?';
	const LABEL_END_MARK = '?>';

	const INSTRUCTION_MARK = '__';
	const FOR_MARK = 'for';
	const REPEAT_MARK = 'repeat';
	const END_MARK = 'end';
	const TEMPLATE_MARK = 'template';
	const IF_MARK = 'if';
	const DEFAULT_MARK = 'default';
	const MOVE_MARK = 'move';
	const REPLACE_MARK = 'replace';
	const BOOKMARK_MARK = 'marker';
	
	private $html = false;
	private $lastError = false;

	private $labelStartMarkLength;
	private $labelEndMarkLength;
	private $labelHandlerMethod;
	private $labelHandlerClass;
	private $optArg;
	private $htmlLen;
	private $autoVars;
	private $path;

	public function __construct($path) {
		$this->path = $path;
		$this->html = file_get_contents($path);
	}
	
	public function isLoaded() {
		return $this->html !== false;
	}

	public function getLastError() {
		return $this->lastError;
	}

	protected function decodeSequence($str) {
		$parts = explode(',', $str);
		if (count($parts) == 2) {
			// Parse interval
			$start = $parts[0];
			$end = $parts[1];
			if ($start[0] != '[' || $end[strlen($end) - 1] != ']') return false;
			$start = trim(substr($start, 1));
			$end = trim(substr($end, 0, strlen($end) - 1));
			if (!is_numeric($start)) $start = $this->resolveLabel($start);
			if (!is_numeric($end)) $end = $this->resolveLabel($end);
			if (!is_numeric($start) || !is_numeric($end) || $start != intval($start) || $end != intval($end)) return false;
			return array(intval($start), intval($end));
		} else return false;
	}

	protected function decodeCount($val) {
		if (!is_numeric($val)) $val = $this->resolveLabel($val);
		// Parse count
		if (is_numeric($val) && $val == intval($val))
			return array(0, intval($val) - 1);
		else 
			return false;
	}

	protected function decodeAssocArray($parts) {
		$result = array();
		foreach($parts as $part) {
			$subparts = preg_split("/[:]*\\\"([^\\\"]+)\\\"[:]*|" . "[:]*'([^']+)'[:]*|" . "[:]+/", trim($part), 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
			if (count($subparts) != 2) return false;
			$value = $this->resolveLabel($subparts[1]);
			if ($value === false) $value = getSite()->encodeOutput($subparts[1]);
			$key = $this->resolveLabel($subparts[0]);
			if ($key === false) $key = $subparts[0];
			$result[$key] = $value;
		}
		return $result;
	}

	protected function decodeInstruction($label) {
		// Split space-separated words. Sentences between quotes (' or ") are treated as single words.
		$parts = preg_split("/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/", $label, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$instruction = substr($parts[0], strlen(self::INSTRUCTION_MARK));
		$info = false;
		switch($instruction) {
			case self::FOR_MARK:
				$seq = '';
				for ($i = 2; $i < count($parts); $i++) $seq .= $parts[$i];				
				$seq = $this->decodeSequence($seq);
				if ($seq === false) break;
				$info = array('instruction' => $instruction, 'variable' => $parts[1], 'start' => $seq[0], 'end' => $seq[1]);
				break;
			case self::REPEAT_MARK:
				$seq = $this->decodeCount($parts[2]);
				if ($seq === false) break;
				$info = array('instruction' => self::FOR_MARK, 'variable' => $parts[1], 'start' => $seq[0], 'end' => $seq[1]);
				break;
			case self::IF_MARK:
				$info = array('instruction' => self::IF_MARK, 'expression' => $parts[1]);
				break;
			case self::MOVE_MARK:
				$info = array('instruction' => self::MOVE_MARK, 'expression' => $parts[1]);
				break;
			case self::REPLACE_MARK:
				$info = array('instruction' => self::REPLACE_MARK, 'expression' => $parts[1]);	
				break;
			case self::END_MARK:
				$info = array('instruction' => $instruction);
				break;
			case self::TEMPLATE_MARK:
				$params = $this->decodeAssocArray(array_slice($parts, 2));
				$info = array('instruction' => $instruction, 'path' => $parts[1], 'params' => $params);
				break;
			case self::BOOKMARK_MARK:
				$info = array('instruction' => self::BOOKMARK_MARK, 'name' => $parts[1]);
				break;
			default:
				$info = false;
				break;
		}
		return $info;
	}

	private function findClosingSquareBracket($label, $index) {
		// $index has the position of an opening square bracket. Find corresponding closing square bracket.
		$count = 1;
		$len = strlen($label);
		while($count > 0) {
			$index++;
			while($index < $len && $label[$index] != '[' && $label[$index] != ']') $index++;
			if ($index == $len) return false;
			if ($label[$index] == '[') $count++;
			else $count--;
		}
		return $index;
	}

	private function findOpeningSquareBracket($label, $index) {
		// $index has an arbitrary position. Find an opening square bracket which is before any closing square bracket.
		$len = strlen($label);
		while($index < $len && $label[$index] != '[' && $label[$index] != ']') $index++;
		if ($index == $len || $label[$index] == ']') return false;
		else return $index;
	}

	protected function decodeArrayAccess($label, $labelValues, $start, $end) {
		$first = $start;
		$first = $this->findOpeningSquareBracket($label, $start);
		if ($first !== false) {			
			$arrayName = substr($label, $start, $first - $start);
			$start = $first;
			$value = self::getLabelValueFromArray($labelValues, $arrayName);
			if ($value === null) return false;
			while ($start !== false) {
				$end = $this->findClosingSquareBracket($label, $start);
				if ($end === false) return false;
				if (!is_array($value)) return false;
				$start++;
				$res = $this->decodeArrayAccess($label, $labelValues, $start, $end);
				if ($res === false) { 
					return false;
				} else {
					if (array_key_exists($res, $value)) {
						$value = $value[$res];
						if ($end + 1 < strlen($label) && $label[$end + 1] == '[') {
							// There's a new opening bracket after the closing one. Keep on indexing.
							$start = $end + 1;
						} else {
							// No more opening brackets. This is the final value.
							$start = false;
						}
					} else { 
						return false;
					}
				}
			} 
			return $value;
		} else {
			$label = substr($label, $start, $end - $start); 
			if (is_numeric($label)) {
				return intval($label);
			} else if (array_key_exists($label, $this->autoVars)) {
				return $this->autoVars[$label];		// Replace loop variables by values
			} else {
				$value = self::getLabelValueFromArray($labelValues, $label);
				return $value === null ? false : $value;
			}
		}
	}

	public static function getLabelValueFromArray($labelValues, $label) {
		if (array_key_exists((string) $label, $labelValues)) {
			return $labelValues[(string) $label];
		} else {
			$default = self::INSTRUCTION_MARK . self::DEFAULT_MARK;
			if (array_key_exists($default, $labelValues)) return $labelValues[$default];
			else return null;
		}
	}
	
	protected function resolveLabel($label) {
		$operands = array();
		$operators = '=!<>&\|+\-\*\/\^~()';
		if (preg_match_all("/[^$operators]+/", $label, $operands, PREG_OFFSET_CAPTURE) === false) return false;
		$haveOperators = preg_match("/[$operators]+/", $label) === 1;
		$operands = $operands[0];
		$last = 0;
		$endLabel = '';
		if ($haveOperators) {
			for ($i = 0; $i < count($operands); $i++) {
				$operand = $operands[$i];
				$text = $operand[0];
				$position = $operand[1];

				$value = $this->resolveLabel($text);
				if (is_string($value)) $value = "'".addslashes($value)."'";
				else if (is_bool($value)) $value = $value ? 'true' : 'false';

				$endLabel .= substr($label, $last, $position - $last) . $value;
				$last = $position + strlen($text);
//				$label = str_replace($text, $value, $label);
			}
			$endLabel .= substr($label, $last);
			$expression = '$output = (' . $endLabel . ');';
			eval($expression);
		} else {

			$lhClass = $this->labelHandlerClass;
			$lhMethod = $this->labelHandlerMethod;
			// Normal label
			if (is_array($lhMethod)) {
				// Decode array accesses
				$output = $this->decodeArrayAccess($label, $lhMethod, 0, strlen($label));
			} else {
				// Call label handler method from its class or globally, if no class is defined							
				if ($lhClass != NULL) {
					$output = $lhClass->$lhMethod($label, $this->optArg);
				} else {
					$output = $lhMethod($label, $this->optArg);
				}
			}
		
		}
		return $output; 
	}

	private $movePending = array();

	public function addMovePending($mp) {
		$this->movePending = array_merge($this->movePending, $mp);
	}

	public function getMovePending() {
		return $this->movePending;
	}

	private $markers = array();

	protected function markerToHtml($marker) {
		return "<:$marker></:$marker>";
	}

	protected function renderRecursive($labelStart, $labelEnd, $output, $level, $instInfo) {
		$labelStart0 = $labelStart;
		$labelEnd0 = $labelEnd;
		if (is_array($instInfo) && $instInfo['instruction'] == 'for') {
			$this->autoVars[$instInfo['variable']] = $instInfo['start'];
		}
		while(($labelStart + $this->labelStartMarkLength) < $this->htmlLen && ($labelEnd + $this->labelEndMarkLength) < $this->htmlLen) {
			// Find a new label start
			$labelStart = strpos($this->html, self::LABEL_START_MARK, $labelEnd + $this->labelEndMarkLength);
			if ($labelStart === FALSE) $labelStart = $this->htmlLen;
			// Add to output the fragment from the end of the last label to the beginning of the new one
			$output .= substr($this->html, $labelEnd + $this->labelEndMarkLength, $labelStart - ($labelEnd + $this->labelEndMarkLength));
			if ($labelStart + $this->labelStartMarkLength < $this->htmlLen) {
				// Find end of the current label
				$labelEnd = strpos($this->html, self::LABEL_END_MARK, $labelStart + $this->labelStartMarkLength);
				if ($labelEnd === FALSE) $labelEnd = $this->htmlLen;
				// Get label contents
				$label = trim(substr($this->html, $labelStart + $this->labelStartMarkLength, $labelEnd - ($labelStart + $this->labelStartMarkLength)));

				$isInstruction = strpos($label, self::INSTRUCTION_MARK) === 0;
				if (!$isInstruction) {
					$output .= $this->resolveLabel($label);
				} else {
					// Instruction label
					$newInstInfo = $this->decodeInstruction($label);
					if ($newInstInfo === false) { $this->lastError = "Unable to decode instruction '$label'"; return false; }
					
					switch($newInstInfo['instruction']) {

					case self::MOVE_MARK:
					case self::REPLACE_MARK:
					case self::IF_MARK:
						$result = $this->renderRecursive($labelEnd + $this->labelEndMarkLength, $labelEnd, '', $level + 1, $newInstInfo);
						if ($result === false) return false;
						$output .= $result['output'];
						$labelEnd = $result['labelEnd'];
						$labelStart = $labelEnd + $this->labelEndMarkLength;
						break;

					case self::FOR_MARK:
						$result = $this->renderRecursive($labelEnd + $this->labelEndMarkLength, $labelEnd, '', $level + 1, $newInstInfo);
						if ($result === false) return false;
						if ($newInstInfo['end'] - $newInstInfo['start'] + 1 > 0) 
							$output .= $result['output'];
						$labelEnd = $result['labelEnd'];
						$labelStart = $labelEnd + $this->labelEndMarkLength;
						break;

					case self::END_MARK:
						if ($level == 0) { $this->lastError = "__end was found with no corresponding previous instruction"; return false; }						

						switch ($instInfo['instruction']) {

							case self::FOR_MARK:
								if ($this->autoVars[$instInfo['variable']] < $instInfo['end']) {
									// Repeat
									$labelStart = $labelStart0;
									$labelEnd = $labelEnd0;
									$this->autoVars[$instInfo['variable']]++;
								} else {
									unset($this->autoVars[$instInfo['variable']]);
									$labelStart = $labelEnd + $this->labelEndMarkLength;
									return array('output' => $output, 'labelStart' => $labelStart, 'labelEnd' => $labelEnd);
								}
								break;

							case self::IF_MARK:
								$condition = $this->resolveLabel($instInfo['expression']);
								$labelStart = $labelEnd + $this->labelEndMarkLength;
								return array('output' => $condition ? $output : '', 'labelStart' => $labelStart, 'labelEnd' => $labelEnd);
								break;

							case self::REPLACE_MARK:
								$moveMode = 'replace';
							case self::MOVE_MARK:
								if (!isset($moveMode)) $moveMode = 'append';
								$this->movePending[] = array('destination' => $instInfo['expression'], 'content' => $output, 'mode' => $moveMode);
								$labelStart = $labelEnd + $this->labelEndMarkLength;
								return array('output' => '', 'labelStart' => $labelStart, 'labelEnd' => $labelEnd);
								break;
						}
						break;

					case self::TEMPLATE_MARK:
						$path = $newInstInfo['path'];
						if ($newInstInfo['params'] === false) { $this->lastError = "Bad parameter syntax when including template '$path' from template '$this->path'"; return false; }
						$pathToTemplate = getSite()->findPathToTemplate($path);
						if ($pathToTemplate === false) { $this->lastError = "Unable to find path to template '$path'"; return false; }
						$template = new SiteEngine_Template($pathToTemplate);
						if (!$template->isLoaded()) { $this->lastError = "Unable to load template '$path'"; return false; }
						$newParams = is_array($this->labelHandlerMethod) ? array_merge($this->labelHandlerMethod, $newInstInfo['params']) : $newInstInfo['params'];
						$output .= $template->render($newParams);
						$this->addMovePending($template->getMovePending());
						break;

					case self::BOOKMARK_MARK:
						$name = $newInstInfo['name'];
						$output .= $this->markerToHtml($name);
						break;

					}
				}
			}
		}
		if ($level > 0) {
			$this->lastError = 'End of data reached when expecting __end instruction';
			return false;
		}
		return array('output' => $output, 'labelStart' => $labelStart, 'labelEnd' => $labelEnd);
	}

	protected function moveFragments(&$output) {
		// Group destinations
		$groupedFragments = array();
		$groupedModes = array();
		foreach($this->movePending as $fragment) {
			$destination = $fragment['destination'];
			$content = $fragment['content'];
			$mode = isset($fragment['mode']) ? $fragment['mode'] : 'append';
			if (!array_key_exists($destination, $groupedFragments)) {
				$groupedFragments[$destination] = $content;
				$groupedModes[$destination] = $mode;
			} else {
				switch($mode) {
					case 'append':
						$groupedFragments[$destination] .= $content;						
						break;
					case 'replace':
						$groupedFragments[$destination] = $content;
						$groupedModes[$destination] = 'replace';
						break;
				}
			}
		}

		$markers = array();
		$offset = 0;
		foreach($groupedFragments as $destination => $content) {
			// Place content in destination
			// Check if content goes in inner HTML or in an attribute
			$inAttr = false;
			if ($destination[0] != ':' && preg_match('/.+]\/(.+)$/', $destination, $destParts) !== false && count($destParts) > 1) {
				$inAttr = $destParts[1];
			}
			$tags = Helpers_Xml::findTags($output, $destination, $offset, 1);
			if ($tags !== false && count($tags) > 0) {
				$tag = $tags[0];

				$attrInfo = false;
				if ($inAttr !== false) {
					$elemStart = $tag['elementStart'];
					$contentStart = ($tag['contentStart'] !== false ? $tag['contentStart'] : strlen($output));
					$attributes = Helpers_Xml::getTagAttributes(substr($output, $elemStart, $contentStart - $elemStart), $attrOffsets);
					if (isset($attrOffsets[$inAttr])) {
						$attrInfo = $attrOffsets[$inAttr];
						$attrInfo['contentStart'] += $elemStart;
						$attrInfo['contentEnd'] += $elemStart;
					}
				}

				switch($groupedModes[$destination]) {
					case 'append':
						if ($attrInfo === false) {
							$insertIndex1 = $insertIndex2 = ($destination[0] != ':' ? $tag['contentEnd'] : $tag['elementEnd']);
						} else {
							$insertIndex1 = $insertIndex2 = $attrInfo['contentEnd'];
						}
						break;
					case 'replace':
						if ($attrInfo === false) {
							if ($destination[0] != ':') {
								$insertIndex1 = $tag['contentStart'];
								$insertIndex2 = ($tag['contentEnd'] !== false ? $tag['contentEnd'] : $insertIndex1);
							} else {
								$insertIndex1 =  $insertIndex2 = $tag['elementEnd'];
							}
						} else {
							$insertIndex1 = $attrInfo['contentStart'];
							$insertIndex2 = $attrInfo['contentEnd'];
						}
						break;
				}

				$output = substr($output, 0, $insertIndex1) . $content . substr($output, $insertIndex2);
				$offset = $tag['contentStart'];
			}
		}

		// Marker post-deletion
		while(($pos = strpos($output, '<:')) !== false) {
			$pos2 = strpos($output, '>', $pos + 2);
			if ($pos2 !== false) {
				$markerName = substr($output, $pos + 2, $pos2 - ($pos + 2));
				$output = str_replace($this->markerToHtml($markerName), '', $output);
			}
		}

/*		
		// TODO: This is the old code. Erase when the new one proves no errors for a long time.
		$markers = array();
		foreach($this->movePending as $fragment) {
			$destination = $fragment['destination'];
			$content = $fragment['content'];
			// Place content in destination
			$tag = "</$destination>";
			$index = strpos($output, $tag);
			if ($index !== false) {
				if ($destination[0] == ':' && !isset($markers[$tag])) $markers[$tag] = $index;
				$output = substr($output, 0, $index) . $content . substr($output, $index);
			}
		}

		foreach($markers as $tag => $index) {
			$output = str_replace($tag, '', $output);
		}
*/
	}

	public function render($labelHandlerMethod, $labelHandlerClass = NULL, $optArg = NULL, $runMoves = false) {
		$this->labelHandlerMethod = $labelHandlerMethod;
		$this->labelHandlerClass = $labelHandlerClass;
		$this->optArg = $optArg;
		$this->htmlLen = strlen($this->html);
		$this->autoVars = array();

		// Add some system-defined labels
		if (is_array($this->labelHandlerMethod)) {
			$this->labelHandlerMethod = array_merge(getSite()->getPublicLabels(), $this->labelHandlerMethod);
		}
		
		$this->labelStartMarkLength = strlen(self::LABEL_START_MARK);
		$this->labelEndMarkLength = strlen(self::LABEL_END_MARK);
		$labelStart = 0;
		$labelEnd = -$this->labelEndMarkLength;
		$result = $this->renderRecursive($labelStart, $labelEnd, '', 0, false, 0);
		if ($result === false) return false;

		$output = $result['output'];

		// Insert move-pending fragments into destinations
		if ($runMoves) {
			$this->moveFragments($output);
		}

		return $output;
	}

}

?>
