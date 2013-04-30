/**
*
* URL encode / decode
* http://www.webtoolkit.info/
*
**/

UrlCodec = Class.extend({

    // public method for url encoding
    _encode : function (string) {
        return encodeURI(this._utf8_encode(string));
    },

    // public method for url decoding
    _decode : function (string) {
        return this._utf8_decode(decodeURI(string));
    },

    // public method for url encoding
    _encodeComponent : function (string) {
        return encodeURIComponent(this._utf8_encode(string));
    },

    // public method for url decoding
    _decodeComponent : function (string) {
        return this._utf8_decode(decodeURIComponent(string));
    },

    // private method for UTF-8 encoding
    _utf8_encode : function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while ( i < utftext.length ) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            }
            else if((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            }
            else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }

        }

        return string;
    }

})

/**
 * Url class
 * Author: uavster
 */
Url = UrlCodec.extend({
	init : function(url) { 
		this.url = (url == null ? (window.location + '') : url); 
	},

	toString : function() {
		return this.url;
	},

	encode : function() {
		return this._encode(this.toString());
	},

	decode : function(encodedUrl) {
		this.url = this._decode(encodedUrl);
	},

	encodeComponent : function() {
		return this._encodeComponent(this.toString());
	},

	decodeComponent : function(encodedUrl) {
		this.url = this._decodeComponent(encodedUrl);
	},

	_trim : function(s) { 
		return s.replace(/^\s+|\s+$/,""); 
	},

	addParam : function(name, value) {
		var result = this.url;
		var sepPos = result.indexOf("?");
		if (sepPos == -1) result += "?";
		else if (this._trim(this.url.substring(sepPos)) != "?") result += "&";
		result += name + "=" + value;
		return new Url(result);
	},

	getParamString : function() {
		var pos = this.url.indexOf("?");
		if (pos == -1) return "";
		else return this.url.substring(pos + 1);
	},

	getParamInfo : function(name) {
		var query = this.getParamString();
		var params = query.split("&");
		name = name.toLowerCase();
		var result;
		for(var i = 0; i < params.length; i++) {
			var param = params[i];
			var parts = param.split("=");
			if (parts[0].toLowerCase() == name) {
				result = new Array();
				if (this.url.indexOf("&" + param) != -1)
					result.separator = "&";
				else if (this.url.indexOf("?" + param) != -1)
					result.separator = "?";
				result.string = param;
				result.name = parts[0];
				result.value = parts[1];
				return result;
			}
		}
		return null;
	},

	getParam : function(name) {
		var pinfo = this.getParamInfo(name);
		return pinfo == null ? null : pinfo.value;
	},

	setParam : function(name, value) {
		var param = this.getParamInfo(name);
		if (param == null) {
			return this.addParam(name, value);
		} else {
			return new Url(this.url.replace(param.separator + param.string, param.separator + param.name + "=" + value));
		}
	},

	removeParam : function(name) {
		var param = this.getParamInfo(name);
		if (param != null) {		
			var result = this.url.replace(param.separator + param.string, "");
			if (param.separator == "?") {
				var pos = result.indexOf("&");
				if (pos != -1) result = result.substring(0, pos - 1) + "?" + result.substring(pos + 1);
			}
			return new Url(result);
		}
		else return this;
	}

})
