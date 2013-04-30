Graphics = Class.extend({
	getAbsolutePos: function(obj) {
		var curleft = 0;
		var curtop = 0;
		if (obj.offsetParent)
		{
			while (obj.offsetParent)
			{
				curleft += obj.offsetLeft
				curtop += obj.offsetTop
				obj = obj.offsetParent;
			}
		}
		else {
			if (obj.x) curleft += obj.x;
			if (obj.y) curtop += obj.y;
		}
		return { x: curleft, y: curtop };
	},

	getOffsetPos: function(obj, countMargin) {
		if (typeof countMargin === 'undefined') countMargin = false;
		var x = obj.offsetLeft, y = obj.offsetTop;
		if (countMargin) {
			x -= this.getStyle(obj, 'margin-left').replace('px', '');
			y -= this.getStyle(obj, 'margin-top').replace('px', '');
		}
		return { x: x, y: y };
	},

	camelize: function(s) {
	    var oStringList = s.split('-');
	    if (oStringList.length == 1) return oStringList[0];

	    var camelizedString = s.indexOf('-') == 0
	      ? oStringList[0].charAt(0).toUpperCase() + oStringList[0].substring(1)
	      : oStringList[0];

	    for (var i = 1, len = oStringList.length; i < len; i++) {
	      var s = oStringList[i];
	      camelizedString += s.charAt(0).toUpperCase() + s.substring(1);
	    }

	    return camelizedString;
	},

	getPageSize: function() {
	    	var d = document, bd = d.body, dd = d.documentElement;
		var height = Math.max(bd.scrollHeight, bd.offsetHeight, bd.clientHeight, dd.offsetHeight, dd.scrollHeight, dd.clientHeight);
		var width = Math.max(bd.scrollWidth, bd.offsetWidth, bd.clientWidth, dd.offsetWidth, dd.scrollWidth, dd.clientWidth);
		return { w: width, h: height };
	},

	getPageVisibleSize: function() {
		// From http://andylangton.co.uk
		var e = window, a = 'inner';
		if (!('innerWidth' in window))
		{
			a = 'client';
			e = document.documentElement ? document.documentElement : document.body;	// modified: uavster
		}
		var w = e[ a+'Width' ];
		var h = e[ a+'Height' ];
		return { w:w , h:h }
	},

	getPageScroll: function() {
//		return { x:(document.all ? document.scrollLeft : window.pageXOffset), y:(document.all ? document.scrollTop : window.pageYOffset) }
		var sx, sy;
		if (window.pageXOffset) sx = window.pageXOffset;
		else sx = document.body.scrollLeft;
		if (window.pageYOffset) sy = window.pageYOffset;
		else sy = document.body.scrollTop;
		return { x:sx, y:sy }
	},

	getScreenTotalSize: function() {
		return { w:screen.width, h:screen.height }
	},

	getScreenAvailSize: function() {
		return { w:screen.availWidth, h:screen.availHeight }
	},

	getStyle: function(element, style) {
	    var value = element.style[this.camelize(style)];
	    if (!value) {
	      if (document.defaultView && document.defaultView.getComputedStyle) {
		var css = document.defaultView.getComputedStyle(element, null);
		value = css ? css.getPropertyValue(style) : null;
	      } else if (element.currentStyle) {
		value = element.currentStyle[this.camelize(style)];
	      }
	    }

	    if (window.opera && ['left', 'top', 'right', 'bottom'].include(style))
	    	if (Element.getStyle(element, 'position') == 'static') value = 'auto';

		return value == 'auto' ? null : value;
	},

	setStyle: function(element, style) {
	    for (var name in style)
	      element.style[this.camelize(name)] = style[name];
	},
	  
	setOpacity: function(element, value) {
	  if (value >= 1){
	    this.setStyle(element, { opacity: 
	      (/Gecko/.test(navigator.userAgent) && !/Konqueror|Safari|KHTML/.test(navigator.userAgent)) ? 
	      0.999999 : null });
	    if(/MSIE/.test(navigator.userAgent))  
	      this.setStyle(element, {filter: this.getStyle(element,'filter').replace(/alpha\([^\)]*\)/gi,'')});  
	  } else {  
	    if(value < 0.00001) value = 0; 
	    this.setStyle(element, {opacity: value});
	    if(/MSIE/.test(navigator.userAgent))  {
	     this.setStyle(element, 
	       { filter: this.getStyle(element,'filter').replace(/alpha\([^\)]*\)/gi,'') +
		         'alpha(opacity='+value*100+')' }); 
	     }
	  }
	},

	getOpacity: function(element) {  
		var opacity;
		if (opacity = this.getStyle(element, 'opacity'))
			return parseFloat(opacity);
		if (opacity = (this.getStyle(element, 'filter') || '').match(/alpha\(opacity=(.*)\)/))  
		if (opacity[1]) return parseFloat(opacity[1]) / 100;  
		return 1.0;  
	}
});
