<?php
class Widget_CMS extends SiteEngine_Widget {

	protected static function getBackCMSUrl() {
		return getSite()->getBaseUrl() . '!!cms/';
	}

	public static function getMediaUrl($ref, $action = '') {
		return self::getBackCMSUrl() . $ref . ($action != '' ? ('/' . $action) : '');
	}

	public static function getMediaRefByUrl($url) {
		$cmsPart = self::getBackCMSUrl();	// Only media of this site will be identified
		if (strpos($url, $cmsPart) === 0)
			return substr($url, strlen($cmsPart));
		else 
			return false;
	}

	public static function getBrowserUrl($onSelectHandler = null, $handlerExtraParams = null) {
		$url = getSite()->getBaseUrl() . 'cms';
		if ($onSelectHandler !== null) $url = Helpers_Url::setParam($url, 'selectFuncName', $onSelectHandler);
		if ($handlerExtraParams !== null) $url = Helpers_Url::setParam($url, 'selectFuncExtraParams', $handlerExtraParams);
		return $url;
	}

	public static function findMediaByRef($ref) {
		return R::findOne('media', '(ref = ?) and (site = ?)', array($ref, getSite()->getName()));
	}

	protected function refExists($ref) {
		return self::findMediaByRef($ref) !== null;
	}

	protected function ensureRefIsUnique($baseRef) {
		// The reference must be unique. Check if it already exists.
		// This is the easy solution. It takes N+1 queries, where N is the number of existing documents with the same $baseRef, 
		// which is not the typical case, but not optimal, though.
		// TODO: I should try at some point to reduce everything to a single query + result processing
		$fileName = pathinfo($baseRef, PATHINFO_FILENAME);
		$extension = pathinfo($baseRef, PATHINFO_EXTENSION);
		$newRef = $baseRef;
		$i = 2;
		while(true) {
			if (!$this->refExists($newRef)) break;
			$newRef = $fileName . '-' . $i . '.' . $extension;
			$i++;
		}
		return $newRef;
	}

	protected function newRefFromName($name) {
		return $this->ensureRefIsUnique(Helpers_Url::stringToRef($name));
	}

	protected function createMedia($reqParams, $mimeType, $ref = null, $edit = false) {
		try {
			if (!isset($_FILES['upload'])) throw new Exception('No file received');
			$file = $_FILES['upload'];

			$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
			$imageInfo = getimagesize($file['tmp_name']);
			if ($imageInfo !== false) {
				$width = $imageInfo[0];
				$height = $imageInfo[1];
				$mimeType = $imageInfo['mime'];
			} else {
				$width = $height = -1;
				if ($mimeType == '0') {
					// Infer MIME type 
					$mimeType = Helpers_Mime::fileExtensionToMimeType($extension, getSite()->getEncoding());
				}
			}

			if (!$edit) {
				$newMedia = R::dispense('media');
			} else {
				$newMedia = self::findMediaByRef($ref);
				if ($newMedia === null) throw new Exception('Media not found');
			}

			$newMedia->setMeta('buildcommand.unique' , array(array('ref')));
			// Creating indexes which are not unique is not documented and the line below doesn't seem to work. 
			// We do it manually and will export later the database schema.
			//$newMedia->setMeta('buildcommand.indexes' , array('site'));

			$newMedia->site = getSite()->getName();
			$content = file_get_contents($file['tmp_name']);
			if ($content === false) throw new Exception('Error reading file');
			$newMedia->origin = $file['name'];
			$newMedia->mime = $mimeType;
			$newMedia->width = $width;
			$newMedia->height = $height;
			$newMedia->lastModified = time();
			$newMedia->contentLength = filesize($file['tmp_name']);
			
			if ($ref === null) {
				// The reference is not specified; this implies media creation and reference inference by media name
				$urlRef = $this->newRefFromName($file['name']);
			} else {
				if (!$edit) {
					// If we're creating new media, the reference must be unique
					$urlRef = $this->ensureRefIsUnique($ref) . '.' . $extension;
				} else {
					// We're editting an existing media: the reference is as passed
					$urlRef = $ref;
				}
			}

			$newMedia->ref = $urlRef;
			$newMedia->content = $content;
			R::store($newMedia);

			$message = '';
			$url = self::getMediaUrl($urlRef);
		} catch(Exception $e) {
			$message = $e->getMessage();
			$url = '';
		}
	
		if (isset($reqParams['CKEditorFuncNum'])) {
			$this->setProcessOutputContentType('text/html');
			$funcNum = $reqParams['CKEditorFuncNum'];
			return "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '$message');</script>";
		} else {
			if ($message != '') throw new Exception($message);
			return array('url' => $url);
		}
	}

	protected function readMedia($ref) {
		$media = self::findMediaByRef($ref);
		if ($media === null) {
			$this->setProcessOutputContentType('text/html');
			return getSite()->outputDocNotFound();
		}

		$this->enableProcessOutputClientCache();
		$this->setProcessOutputContentType($media->mime);
		$sendContent = getSite()->cacheControl($media->lastModified);
		return $sendContent ? $media->content : '';
	}

	protected function deleteMedia($ref) {
		$media = self::findMediaByRef($ref);
		if ($media === null) throw new Exception('Media not found');
		R::trash($media);
		return array('result' => 'ok');				
	}

	// The output of this method must be an array. All strings are encoded in utf8.
	public function process($widgetParams, $requestType, $requestParams) {
		if (isset($requestParams['action'])) $action = $requestParams['action'];
		if (isset($requestParams['id'])) $id = $requestParams['id'];

		if (!isset($action) || !isset($id)) {
			// Get action and media id
			$doc = $requestParams['OOWEE_DOCUMENT'];
			$label = $requestParams['OOWEE_LABEL'];
			$docParts = explode('/', $doc);
			if ($label == '') {
				if (count($docParts) <= 1) throw new Exception('Bad query');
				else if (count($docParts) == 2) {
					if (!isset($action)) $action = 'read';
					if (!isset($id)) $id = $docParts[1];
				}
				else {
					if (!isset($action)) $action = $docParts[count($docParts) - 1];
					if (!isset($id)) $id = implode('/', array_slice($docParts, 1, count($docParts) - 2));
				} 
			} else {
				if (count($docParts) <= 1) {
					if (!isset($action)) $action = 'read';
					if (!isset($id)) $id = $label;
				} else {
					if (!isset($action)) $action = $label;
					if (!isset($id)) $id = implode('/', array_slice($docParts, 1, count($docParts) - 1));
				}
			}
		}

		// Do action
		switch($action) {
			case 'read':
				$output = $this->readMedia($id);
				break;
			case 'edit':
				$edit = true;
			case 'new':
				// Access is only allowed for admin
				$className = getSite()->loadWidgetClass('UserManager');
				if ($className::requireUserRights('admin', false) === false) throw new Exception('Access denied');

				if (!isset($edit)) $edit = false;
				$parts = explode('/', $id);
				if (count($parts) > 2) $ref = implode('/', array_slice($parts, 2));
				else if (count($parts) == 2 && $parts[0] == '0') $ref = $parts[1];
				else $ref = null;
				$output = $this->createMedia($requestParams, $id, $ref, $edit);
				break;
			case 'del':
				// Access is only allowed for admin
				$className = getSite()->loadWidgetClass('UserManager');
				if ($className::requireUserRights('admin', false) === false) throw new Exception('Access denied');

				$output = $this->deleteMedia($id);
				break;
			default:
				throw new Exception('Bad command');
		}

		return $output;
	}

	protected function fileSizeToString($size) {
		// Code from http://www.jonasjohn.de/snippets/php/readable-filesize.htm
		$mod = 1024;	 
		$units = explode(' ','B KB MB GB TB PB');
		for ($i = 0; $size > $mod; $i++) {
			$size /= $mod;
		}

		return round($size, 2) . ' ' . $units[$i];
	}

	// The output content of this method must be HTML-encoded
	public function draw($widgetParams, $requestType, $requestParams) {
		$className = getSite()->loadWidgetClass('UserManager');

		if ($widgetParams['hideIfNotAdmin'] == 'yes') {
			$isAdmin = $className::requireUserRights('admin', false) !== false;
			if (!$isAdmin) {
				header('HTTP/1.0 404 Not Found');
				include SiteEngine_SiteManager::getDefaultUnknownSiteDoc();
				return;
			}
		}

		// Access is only allowed for admin
		$isAdmin = $className::requireUserRights('admin') !== false;

		if ($isAdmin) {
			$this->outputScriptRef('js/cms/mediaBrowser.js');
			$this->outputAsyncProcessCall('delDone', false, 'delMedia(id)', false, "?action=del&id=' + id + '");
			$this->outputAsyncProcessCall('newMediaUploaded', false, 'onNewMediaSubmit', 'uploadMediaForm', '/new');

			$onClickCode = '';

			if (isset($requestParams['CKEditorFuncNum'])) {
				$funcNum = $requestParams['CKEditorFuncNum'];
				$onClickCode = "window.opener.CKEDITOR.tools.callFunction($funcNum, '" . self::getBackCMSUrl() . "' + ref); window.close();";
			}

			if (isset($requestParams['selectFuncName'])) {
				$this->setProcessOutputContentType('text/html');
				if (!isset($urlRef)) $urlRef = '';
				$funcCall = 'window.opener.' . $requestParams['selectFuncName'] . "('" . self::getBackCMSUrl() . "' + ref, ref";
				if (isset($requestParams['selectFuncExtraParams'])) $funcCall .= ', ' . $requestParams['selectFuncExtraParams'];
				$funcCall .= ')';
				$onClickCode = $funcCall;
			}

			$this->outputScriptCode($onClickCode, 'mediaClicked(ref)');

		}

		$errorMsg = false;

		$allMedia = R::find('media', 'site = ?', array(getSite()->getName()));
		$numMedia = 0;
		$isImage = array();
		$isFlash = array();
		$mediaSource = array();
		$mediaInfo = $mediaWidth = $mediaId = $mediaRef = array();

		foreach($allMedia as $id => $media) {
			$parts = explode('/', $media->mime);
			$isIm = $parts[0] == 'image';
			$isImage[] = $isIm;
			$isFlash[] = $media->mime == 'application/x-shockwave-flash';
			$mediaSource[] = self::getMediaUrl($media->ref);
			$mediaInfo[] = $media->width . 'x' . $media->height . ', ' . $this->fileSizeToString($media->contentLength) . ', ' . Helpers_Mime::mimeTypeToFileExtension($media->mime);
			$mediaWidth[] = $isIm ? ($media->width > 200 ? '100%' : $media->width) : '100%';
			$mediaId[] = $id;
			$mediaRef[] = $media->ref;
			$numMedia++;
		}
		if ($numMedia == 0) $errorMsg = 'No media found';

		$labels = array('isAdmin' => $isAdmin, 'siteName' => getSite()->getName(), 'errorMsg' => $errorMsg, 'numMedia' => $numMedia, 'isImage' => $isImage, 'isFlash' => $isFlash, 'mediaId' => $mediaId, 'mediaRef' => $mediaRef, 'mediaWidth' => $mediaWidth, 'mediaSource' => $mediaSource, 'mediaInfo' => $mediaInfo);
		$this->outputTemplate('CMS/mediaBrowser.tpl', $labels);
	}
}
?>
