/**
 * AnimatedElement class
 * 
 * It tracks the starting and ending values of some properties of a visual element
 * and updates them in a run method
 */
function AnimatedElement(e, absolutePos) {
	if (typeof absolutePos === 'undefined') absolutePos = true;
	this.element = e;
	this.refreshProperties(absolutePos);
}

AnimatedElement.prototype.graphics = new Graphics();

AnimatedElement.prototype.getOriginalPos = function() {
	return { x: this.x0, y: this.y0 }
}

AnimatedElement.prototype.refreshProperties = function(absolutePos) {
	if (this.element == null) return;
	var p = absolutePos ? this.graphics.getAbsolutePos(this.element) : this.graphics.getOffsetPos(this.element, true);
	this.x = p.x; this.y = p.y;
	this.w = this.element.offsetWidth;
	this.x0 = this.x;
	this.y0 = this.y;
	this.h = this.element.offsetHeight;
	this.a = this.graphics.getOpacity(this.element);
	if (this.a + "" == "NaN") this.a = 1;
	this.element.style.left = this.x + "px";
	this.element.style.top = this.y + "px";
	this.element.style.width = this.w + "px";
	this.element.style.height = this.h + "px";	
	this.graphics.setOpacity(this.element, this.a);
	this.speed_x = 0;
	this.speed_y = 0;
	this.speed_w = 0;
	this.speed_h = 0;
	this.speed_a = 0;
	this.direction_x = 0;
	this.direction_y = 0;
	this.direction_w = 0;
	this.direction_h = 0;
	this.direction_a = 0;
	this.end_x = this.x;
	this.end_y = this.y;
	this.end_w = this.w;
	this.end_h = this.h;
	this.end_a = this.a;
	this.running_x = false;
	this.running_y = false;
	this.running_w = false;
	this.running_h = false;
	this.running_a = false;
	this.finished = 0;
/*	if (BrowserDetect.browser == "Firefox") {
		this.accel_factor_pos = 0.25;
		this.accel_factor_size = 0.22;
	}
	else {*/
		this.accel_factor_pos = 0.1;
		this.accel_factor_size = 0.1;
//	}
	this.accel_factor = 0.1;
	this.w0 = this.w;
	this.h0 = this.h;
}

AnimatedElement.prototype.animateObject = function() {
	if (this.element == null) return;
	// Update Y
	if (this.running_y) {
		if (this.y > this.end_y) {
			if (this.direction_y > 0) 
				this.y = this.end_y;
			else {
				this.y -= this.speed_y; 
				if (this.y <= this.end_y) {
					this.y = this.end_y;
					this.running_y = false;
				}
			}
		}
		else if (this.y < this.end_y) { 
			if (this.direction_y < 0)
				this.y = this.end_y;
			else {
				this.y += this.speed_y; 
				if (this.y >= this.end_y) {
					this.y = this.end_y;
					this.running_y = false;
				}
			}
		}
		else
			this.running_y = false;
			
		this.element.style.top = this.y + "px"; 	
		this.speed_y = Math.abs(this.y - this.end_y) * this.accel_factor_pos;
		if (this.speed_y < this.accel_factor_pos) this.speed_y = this.accel_factor_pos;
	}
	
	// Update X
	if (this.running_x) {
		if (this.x > this.end_x) {
			if (this.direction_x > 0) 
				this.x = this.end_x;
			else {
				this.x -= this.speed_x; 
				if (this.x <= this.end_x) {
					this.x = this.end_x;
					this.running_x = false;
				}
			}
		}
		else if (this.x < this.end_x) { 
			if (this.direction_x < 0)
				this.x = this.end_x;
			else {
				this.x += this.speed_x; 
				if (this.x >= this.end_x) {
					this.x = this.end_x;
					this.running_x = false;
				}
			}
		}
		else
			this.running_x = false;
			
		this.element.style.left = this.x + "px"; 	
		this.speed_x = Math.abs(this.x - this.end_x) * this.accel_factor_pos;
		if (this.speed_x < this.accel_factor_pos) this.speed_x = this.accel_factor_pos;
	}
	
	// Update width
	if (this.running_w) {
		if (this.w > this.end_w) {
			if (this.direction_w > 0) 
				this.w = this.end_w;
			else {
				this.w -= this.speed_w; 
				if (this.w <= this.end_w) {
					this.w = this.end_w;
					this.running_w = false;
				}
			}
		}
		else if (this.w < this.end_w) { 
			if (this.direction_w < 0)
				this.w = this.end_w;
			else {
				this.w += this.speed_w; 
				if (this.w >= this.end_w) {
					this.w = this.end_w;
					this.running_w = false;
				}
			}
		}
		else
			this.running_w = false;
			
		this.element.style.width = this.w + "px"; 	
		this.speed_w = Math.abs(this.w - this.end_w) * this.accel_factor_size;
		if (this.speed_w < this.accel_factor_size) this.speed_w = this.accel_factor_size;
	}
	
	// Update height
	if (this.running_h) {
		if (this.h > this.end_h) {
			if (this.direction_h > 0) 
				this.h = this.end_h;
			else {
				this.h -= this.speed_h; 
				if (this.h <= this.end_h) {
					this.h = this.end_h;
					this.running_h = false;
				}
			}
		}
		else if (this.h < this.end_h) { 
			if (this.direction_h < 0)
				this.h = this.end_h;
			else {
				this.h += this.speed_h; 
				if (this.h >= this.end_h) {
					this.h = this.end_h;
					this.running_h = false;
				}
			}
		}
		else
			this.running_h = false;
			
		this.element.style.height = this.h + "px"; 	
		this.speed_h = Math.abs(this.h - this.end_h) * this.accel_factor_size;
		if (this.speed_h < this.accel_factor_size) this.speed_h = this.accel_factor_size;
	}
		
	// Update opacity
	if (this.running_a) {
		if (this.a > this.end_a) {
			if (this.direction_a > 0) 
				this.a = this.end_a;
			else {
				this.a -= this.speed_a; 
				if (this.a <= this.end_a) {
					this.a = this.end_a;
					this.running_a = false;
				}
			}
		}
		else if (this.a < this.end_a) { 
			if (this.direction_a < 0)
				this.a = this.end_a;
			else {
				this.a += this.speed_a; 
				if (this.a >= this.end_a) {
					this.a = this.end_a;
					this.running_a = false;
				}
			}
		}
		else
			this.running_a = false;
			
		this.graphics.setOpacity(this.element, this.a);
		this.speed_a = Math.abs(this.a - this.end_a) * this.accel_factor;
		if (this.speed_a < this.accel_factor) this.speed_a = this.accel_factor;
	}
}

AnimatedElement.prototype.setEndY = function(y) {
	this.end_y = y;
	this.direction_y = this.end_y - this.y;
	this.speed_y = Math.abs(this.direction_y) * this.accel_factor_pos;
	if (this.speed_y < this.accel_factor_pos) this.speed_y = this.accel_factor_pos;
	if (this.speed_y > 0) this.running_y = true;
}

AnimatedElement.prototype.setEndX = function(x) {
	this.end_x = x;
	this.direction_x = this.end_x - this.x;
	this.speed_x = Math.abs(this.direction_x) * this.accel_factor_pos;
	if (this.speed_x < this.accel_factor_pos) this.speed_x = this.accel_factor_pos;
	if (this.speed_x > 0) this.running_x = true;
}

AnimatedElement.prototype.setEndWidth = function(w) {
	this.end_w = w;
	this.direction_w = this.end_w - this.w;
	this.speed_w = Math.abs(this.direction_w) * this.accel_factor_size;
	if (this.speed_w < this.accel_factor_size) this.speed_w = this.accel_factor_size;
	if (this.speed_w > 0) this.running_w = true;
}

AnimatedElement.prototype.setEndHeight = function(h) {
	this.end_h = h;
	this.direction_h = this.end_h - this.h;
	this.speed_h = Math.abs(this.direction_h) * this.accel_factor_size;
	if (this.speed_h < this.accel_factor_size) this.speed_h = this.accel_factor_size;
	if (this.speed_h > 0) this.running_h = true;
}

AnimatedElement.prototype.setEndOpacity = function(a) {
	this.end_a = a;
	this.direction_a = this.end_a - this.a;
	this.speed_a = Math.abs(this.direction_a) * this.accel_factor;
	if (this.speed_a < this.accel_factor) this.speed_a = this.accel_factor;
	if (this.speed_a > 0) this.running_a = true;
}

/**
 * Animator class
 * 
 * When played, it automatically updates the properties of the tracked visual elements 
 */
function Animator() {
	this.elements = new Array();
	this.endCallback = null;
	this.endCallbackParam = 0;
	this.playing = false;
	animators.push(this);
}

Animator.prototype.removeAll = function() {
	this.elements.length = 0;
}

Animator.prototype.createElement = function(ctrl, absolutePos) {
	if (typeof absolutePos === 'undefined') absolutePos = true;
	for (var i = 0; i < this.elements.length; i++) {
		var e = this.elements[i];
		if (e.element === ctrl) return e;
	}
	var e = new AnimatedElement(ctrl, absolutePos);
	this.addElement(e);
	return e;
}

Animator.prototype.addElement = function(o) {
	if (this.elements.indexOf(o) < 0)
		this.elements.push(o);
}

Animator.prototype.addElementArray = function(oa) {
	for (var i = 0; i < oa.length; i++)
		this.addElement(oa[i]);
}

var Animator_timerID = null;
var animators = new Array();

function Animator_run() {
	var animators_remaining = animators.length;
	for (var j = 0; j < animators.length; j++) {
			var anim = animators[j];
			if (anim.playing) {
				anim.finished = 0;
				for (var i = 0; i < anim.elements.length; i++) {
					var o = anim.elements[i];
					if (o.element != null && (o.x != o.end_x || o.y != o.end_y || o.w != o.end_w || o.h != o.end_h || o.a != o.end_a))
						o.animateObject();						
					else anim.finished ++; 
				}
				if (anim.finished == anim.elements.length) {
					anim.playing = false;
					// Animator finished: notify
					if (anim.endCallback != null) anim.endCallback(anim.endCallbackParam);
					if (!anim.playing) animators_remaining --;
				}
			}
			else animators_remaining --;
	}
	if (animators_remaining > 0)
		Animator_timerID = setTimeout(Animator_run, 10);
	else {
		clearTimeout(Animator_timerID);
		Animator_timerID = null;
	}
}

Animator.prototype.isFinished = function() {
	for (var i = 0; i < this.elements.length; i++) {
		var o = this.elements[i];
		if (o.element != null && (o.x != o.end_x || o.y != o.end_y || o.w != o.end_w || o.h != o.end_h || o.a != o.end_a)) return false;
	}		
	return true;
}

Animator.prototype.play = function() {
	this.playing = true;
	if (!this.isFinished() && Animator_timerID == null)
		Animator_timerID = setTimeout(Animator_run, 10);
}

Animator.prototype.setEndCallback = function(callback, params) {
	this.endCallback = callback;
	this.endCallbackParam = params;
}

