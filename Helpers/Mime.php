<?php

class Helpers_Mime {

	public static function fileExtensionToMimeType($ext, $charset) {
		switch($ext) {
			case 'html': 
			case 'php':
			case 'htm':
				return "text/html; charset=$charset";
			case 'js': return "text/javascript; charset=$charset";
			case 'css': return "text/css; charset=$charset";
			case 'xml': return "application/xml; charset=$charset";
			case 'txt': return "text/plain; charset=$charset";
			case 'gif': return 'image/gif';
			case 'png': return 'image/png';
			case 'jpg': return 'image/jpeg';
			case 'jpeg': return 'image/jpeg';
			case 'tif': return 'image/tif';
			case 'tiff': return 'image/tif';
			case 'pdf': return 'application/pdf';
			case 'swf': return 'application/x-shockwave-flash';
			case 'zip': return 'application/x-compressed';
      case 'gz': return 'application/x-gzip-compressed';
			case 'xls': return 'application/excel';
			case 'xlsx': return 'application/excel';
			case 'doc': return 'application/msword';
			case 'docx': return 'application/msword';
			// Audio
			case 'aac': return 'audio/aac';
			case 'mp1':
			case 'mp2':
			case 'mp3':
				return 'audio/mpeg';
			case 'm4a':
				return 'audio/mp4';
			case 'oga':
			case 'ogg':
				return 'audio/ogg';
			case 'wav':
				return 'audio/wav';
			// Video
			case 'mp4':
			case 'm4v':
				return 'video/mp4';
			case 'mpg':
			case 'mpeg':
				return 'video/mpg';
			case 'ogv':
				return 'video/ogg';
			case 'webm':
				return 'video/webm';
			default: return 'application/octet-stream';
		}
	}
	
	public static function mimeTypeToFileExtension($type) {
		switch($type) {
			case 'text/html': return 'html';
			case 'text/javascript': return 'js';
			case 'text/css': return 'css';
			case 'application/xml': return 'xml';
			case 'text/plain': return 'txt';
			case 'image/gif': return 'gif';
			case 'image/png': return 'png';
			case 'image/jpeg': return 'jpg';
			case 'image/tif': return 'tif';
			case 'application/pdf': return 'pdf';
			case 'application/x-shockwave-flash': return 'swf';
			case 'application/x-compressed': return 'zip';
      case 'application/x-gzip-compressed': return 'gz';
			case 'application/excel': return 'xls';
			case 'application/msword': return 'doc';
			// Audio
			case 'audio/aac': return 'aac';
			case 'audio/mp4': return 'm4a';
			case 'audio/mpeg': return 'mp3';
			case 'audio/ogg': return 'ogg';
			case 'audio/wav': return 'wav';
			case 'audio/webm': return 'webm';
			// Video
			case 'video/mp4': return 'mp4';
			case 'video/ogg': return 'ogv';
			case 'video/webm': return 'webm';

			default: return '';
		}
	}
}

?>
