Ajax.prototype.abort = function() {
	if (this.req) this.req.abort();
	if (this.endHandler != null && !this.endHandlerCalled) {
		this.endHandlerCalled = true;
		this.endHandler(null, this, this.param);
	}
}

Ajax.prototype.getRequestObject = function() {
	var req = false;
	// Branch for native XMLHttpRequest object
	if(window.XMLHttpRequest) {
		try {
			req = new XMLHttpRequest();
		} catch(e) {
			req = false;
		}
	// Branch for IE/Windows ActiveX version
	} else if(window.ActiveXObject) {
		try {
			req = new ActiveXObject("Msxml2.XMLHTTP");
		} catch(e) {
			try {
		  		req = new ActiveXObject("Microsoft.XMLHTTP");
			} catch(e) {
		  		req = false;
			}
		}
	}
	return req;
}

Ajax.prototype.responseHandler = function() {
	// Only if req shows "loaded"		
	if (this.req.readyState == 4) {
		// Only if "OK"
		var response = null;
		if (this.req.status == 200) response = this.req.responseXML;					
		if (this.endHandler != null && !this.endHandlerCalled) {
			this.endHandlerCalled = true;
			var res = this.endHandler(response, this, this.param);
			if (res != null) { 
				var self = this;
				setTimeout(
					function() { 
						self.requestAsync(self.url, self.endHandler, self.param, self.method, self.sendParams, self.headerFields); 
					}, 
				res); 
			}
		}
	}
}	

/**
 * Starts an asynchronous http request and calls the specified handler when finished. 
 * The handler is always called, regardless of the request success. The handler
 * is called with the following parameters (with this order): 
 * 	response: if a response was received, it contains the XMLHttpRequest.responseXML field; null, if an error happened
 *	ajaxObject: the ajax object instance
 *	param: optional parameter passed to requestAsync()
 *
 * The handler may return an integer with the number of milliseconds after which the request will be repeated.
 *
 * @params:
 *	url - request url
 *	eh - request end handler (optional)
 *	param - parameter to pass to the request end handler (optional)
 *	method - http method to use (GET, POST, PUT, ...) (optional; GET is the default)
 *	sendParam - parameter to pass to XMLHttpRequest.send() (mainly for POSTs) (optional)
 *	headerFields - 	array of key/value pairs where each key is the name of a header field and 
 *			the associated value is the header field value (optional)
 * @return
 *	false if the request object could not be create; true if everything is ok.
 */
Ajax.prototype.requestAsync = function(url, eh, param, method, sendParams, headerFields, binary) {	
	param = typeof param !== 'undefined' ? param : null;
	method = typeof method !== 'undefined' ? method : 'GET';
	sendParams = typeof sendParams !== 'undefined' ? sendParams : null;
	headerFields = typeof headerFields !== 'undefined' ? headerFields : null;
	binary = typeof binary !== 'undefined' ? binary : false;
	eh = typeof eh !== 'undefined' ? eh : null;

	this.url = url;
	this.param = param;
	this.endHandler = eh;
	this.method = method;
	this.sendParams = sendParams;
	this.headerFields = headerFields;
	this.abort();
	this.endHandlerCalled = false;
	this.req = this.getRequestObject();
	if (!this.req) return false;

	var self = this;
	this.req.onreadystatechange = function() { self.responseHandler(); };	
	this.req.open(method, url, true);
	if (headerFields != null) {
		for (var key in headerFields) {
			var value = headerFields[key];
			this.req.setRequestHeader(key, value);
		}
	}
	if (binary && typeof this.req.sendAsBinary !== 'undefined') this.req.sendAsBinary(sendParams);
	else this.req.send(sendParams);
	
	return true;
}

Ajax.prototype.postFormAsync = function(url, eh, param, formId) {
	if (typeof FormData !== 'undefined') {
		return this.requestAsync(url, eh, param, 'POST', new FormData(document.getElementById(formId)));
	} else {
		var data = new FormDataCompatibility(document.getElementById(formId));
		var content = data.buildBody(typeof this.getRequestObject().sendAsBinary !== 'undefined');
		return this.requestAsync(url, eh, param, 'POST', content, {'Content-Type': data.getContentType(), 'Content-Length': content.length}, true);
	}
}

Ajax.prototype.req;
Ajax.prototype.param;
Ajax.prototype.endHandler;
Ajax.prototype.endHandlerCalled;
Ajax.prototype.url;
Ajax.prototype.method;
Ajax.prototype.sendParams;
Ajax.prototype.headerFields;

Ajax.prototype.getNodeInnerText = function(node) {
	if (navigator.appVersion.indexOf("MSIE") == -1) return node.textContent;
	else return node.text;
}

Ajax.prototype.setNodeInnerText = function(node, text) {
	if (navigator.appVersion.indexOf("MSIE") == -1) node.textContent = text;
	else node.text = text;
}

Ajax.prototype.getNodeProperty = function(node, property) {
	var prop = node.attributes.getNamedItem(property);
	return prop.value != null ? prop.value : prop.nodeValue;
}

Ajax.prototype.setNodeProperty = function(node, property, value) {
	var prop = node.attributes.getNamedItem(property);
	if (prop.value != null) prop.value = value;
	else prop.nodeValue = value;
}

Ajax.prototype.get_html_translation_table = function(table, quote_style) {
  // http://kevin.vanzonneveld.net
  // +   original by: Philip Peterson
  // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   bugfixed by: noname
  // +   bugfixed by: Alex
  // +   bugfixed by: Marco
  // +   bugfixed by: madipta
  // +   improved by: KELAN
  // +   improved by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
  // +      input by: Frank Forte
  // +   bugfixed by: T.Wild
  // +      input by: Ratheous
  // %          note: It has been decided that we're not going to add global
  // %          note: dependencies to php.js, meaning the constants are not
  // %          note: real constants, but strings instead. Integers are also supported if someone
  // %          note: chooses to create the constants themselves.
  // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
  // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
  var entities = {},
    hash_map = {},
    decimal;
  var constMappingTable = {},
    constMappingQuoteStyle = {};
  var useTable = {},
    useQuoteStyle = {};

  // Translate arguments
  constMappingTable[0] = 'HTML_SPECIALCHARS';
  constMappingTable[1] = 'HTML_ENTITIES';
  constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
  constMappingQuoteStyle[2] = 'ENT_COMPAT';
  constMappingQuoteStyle[3] = 'ENT_QUOTES';

  useTable = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
  useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

  if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
    throw new Error("Table: " + useTable + ' not supported');
    // return false;
  }

  entities['38'] = '&amp;';
  if (useTable === 'HTML_ENTITIES') {
    entities['160'] = '&nbsp;';
    entities['161'] = '&iexcl;';
    entities['162'] = '&cent;';
    entities['163'] = '&pound;';
    entities['164'] = '&curren;';
    entities['165'] = '&yen;';
    entities['166'] = '&brvbar;';
    entities['167'] = '&sect;';
    entities['168'] = '&uml;';
    entities['169'] = '&copy;';
    entities['170'] = '&ordf;';
    entities['171'] = '&laquo;';
    entities['172'] = '&not;';
    entities['173'] = '&shy;';
    entities['174'] = '&reg;';
    entities['175'] = '&macr;';
    entities['176'] = '&deg;';
    entities['177'] = '&plusmn;';
    entities['178'] = '&sup2;';
    entities['179'] = '&sup3;';
    entities['180'] = '&acute;';
    entities['181'] = '&micro;';
    entities['182'] = '&para;';
    entities['183'] = '&middot;';
    entities['184'] = '&cedil;';
    entities['185'] = '&sup1;';
    entities['186'] = '&ordm;';
    entities['187'] = '&raquo;';
    entities['188'] = '&frac14;';
    entities['189'] = '&frac12;';
    entities['190'] = '&frac34;';
    entities['191'] = '&iquest;';
    entities['192'] = '&Agrave;';
    entities['193'] = '&Aacute;';
    entities['194'] = '&Acirc;';
    entities['195'] = '&Atilde;';
    entities['196'] = '&Auml;';
    entities['197'] = '&Aring;';
    entities['198'] = '&AElig;';
    entities['199'] = '&Ccedil;';
    entities['200'] = '&Egrave;';
    entities['201'] = '&Eacute;';
    entities['202'] = '&Ecirc;';
    entities['203'] = '&Euml;';
    entities['204'] = '&Igrave;';
    entities['205'] = '&Iacute;';
    entities['206'] = '&Icirc;';
    entities['207'] = '&Iuml;';
    entities['208'] = '&ETH;';
    entities['209'] = '&Ntilde;';
    entities['210'] = '&Ograve;';
    entities['211'] = '&Oacute;';
    entities['212'] = '&Ocirc;';
    entities['213'] = '&Otilde;';
    entities['214'] = '&Ouml;';
    entities['215'] = '&times;';
    entities['216'] = '&Oslash;';
    entities['217'] = '&Ugrave;';
    entities['218'] = '&Uacute;';
    entities['219'] = '&Ucirc;';
    entities['220'] = '&Uuml;';
    entities['221'] = '&Yacute;';
    entities['222'] = '&THORN;';
    entities['223'] = '&szlig;';
    entities['224'] = '&agrave;';
    entities['225'] = '&aacute;';
    entities['226'] = '&acirc;';
    entities['227'] = '&atilde;';
    entities['228'] = '&auml;';
    entities['229'] = '&aring;';
    entities['230'] = '&aelig;';
    entities['231'] = '&ccedil;';
    entities['232'] = '&egrave;';
    entities['233'] = '&eacute;';
    entities['234'] = '&ecirc;';
    entities['235'] = '&euml;';
    entities['236'] = '&igrave;';
    entities['237'] = '&iacute;';
    entities['238'] = '&icirc;';
    entities['239'] = '&iuml;';
    entities['240'] = '&eth;';
    entities['241'] = '&ntilde;';
    entities['242'] = '&ograve;';
    entities['243'] = '&oacute;';
    entities['244'] = '&ocirc;';
    entities['245'] = '&otilde;';
    entities['246'] = '&ouml;';
    entities['247'] = '&divide;';
    entities['248'] = '&oslash;';
    entities['249'] = '&ugrave;';
    entities['250'] = '&uacute;';
    entities['251'] = '&ucirc;';
    entities['252'] = '&uuml;';
    entities['253'] = '&yacute;';
    entities['254'] = '&thorn;';
    entities['255'] = '&yuml;';
  }

  if (useQuoteStyle !== 'ENT_NOQUOTES') {
    entities['34'] = '&quot;';
  }
  if (useQuoteStyle === 'ENT_QUOTES') {
    entities['39'] = '&#39;';
  }
  entities['60'] = '&lt;';
  entities['62'] = '&gt;';


  // ascii decimals to real symbols
  for (decimal in entities) {
    if (entities.hasOwnProperty(decimal)) {
      hash_map[String.fromCharCode(decimal)] = entities[decimal];
    }
  }

  return hash_map;
}

Ajax.prototype.htmlEntityDecode = function(string, quote_style) {
  // http://kevin.vanzonneveld.net
  // +   original by: john (http://www.jd-tech.net)
  // +      input by: ger
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   bugfixed by: Onno Marsman
  // +   improved by: marc andreu
  // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +      input by: Ratheous
  // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
  // +      input by: Nick Kolosov (http://sammy.ru)
  // +   bugfixed by: Fox
  // -    depends on: get_html_translation_table
  // *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
  // *     returns 1: 'Kevin & van Zonneveld'
  // *     example 2: html_entity_decode('&amp;lt;');
  // *     returns 2: '&lt;'
  var hash_map = {},
    symbol = '',
    tmp_str = '',
    entity = '';
  tmp_str = string.toString();

  if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {
    return false;
  }

  // fix &amp; problem
  // http://phpjs.org/functions/get_html_translation_table:416#comment_97660
  delete(hash_map['&']);
  hash_map['&'] = '&amp;';

  for (symbol in hash_map) {
    entity = hash_map[symbol];
    tmp_str = tmp_str.split(entity).join(symbol);
  }
  tmp_str = tmp_str.split('&#039;').join("'");

  return tmp_str;
}

Ajax.prototype.walkXmlTree = function(output, nodes) {
	for (var i = 0; i < nodes.length; i++) {
		var node = nodes[i];
		var noElemEnum = typeof Node === 'undefined' || typeof Node.ELEMENT_NODE === 'undefined';
		var isElem = noElemEnum ? (node.nodeType == 1) : (node.nodeType == Node.ELEMENT_NODE);
		if (isElem) {
			if (node.childNodes.length > 0) {
				var noTextEnum = typeof Node === 'undefined' || typeof Node.TEXT_NODE === 'undefined';
				var value;

				var isText = (node.childNodes.length == 1) && (noTextEnum ? (node.childNodes[0].nodeType == 3) : (node.childNodes[0].nodeType == Node.TEXT_NODE));
				if (isText) {
					value = this.htmlEntityDecode(node.childNodes[0].nodeValue);
				} else {
					value = this.walkXmlTree(new Array(), node.childNodes);
				}

				// Indexes have the form __i__number
				if (/^__i__[0-9]+$/.test(node.nodeName)) {
					// Indexed array
					var index = parseInt(node.nodeName.substring(node.nodeName.search(/[0-9]+/)), 10);
					output[index] = value;
				} else {
					output[node.nodeName] = value;
				}				
			}
		}
	}
	return output;
}

Ajax.prototype.xmlToArray = function(xmlDocument, rootNodeName) {
	if (xmlDocument == null) return null;
	rootNodeName = typeof rootNodeName !== 'undefined' ? rootNodeName : 'root';
	var output = null;
	var rootNodes = xmlDocument.getElementsByTagName(rootNodeName);
	if (rootNodes.length > 0) 
		output = this.walkXmlTree(new Array(), rootNodes[0].childNodes);
	return output;
}

function Ajax() {	
	this.self = this;
	this.endHandlerCalled = true;
}


