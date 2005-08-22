/*
 *	interfaceObjs
 *
 *	contains object-daclarations for linked symbols
 */
 
/*
 *	Class rectangle
 *
 *	parentClass for rectangle subclasses
 */
// {{{ constructor
class_rectangle = function() {
	this.init();
};
class_rectangle.prototype = new Movieclip();
// }}}
// {{{ init()
class_rectangle.prototype.init = function() {
	this.width = 100;
	this.height = 100;
	this.redraw();
}; 
// }}}
// {{{ onLoad()
class_rectangle.prototype.onLoad = function() {
	if (this.color != undefined) {
		this.setRGB(this.color);
	}
};
// }}}

/*
 *	Class rectangle_back
 *	Extends class_rectangle()
 *
 *	Handles rectangles without outline
 */
// {{{ constructor()
class_rectangle_back = function() {
	this.init();
};
class_rectangle_back.prototype = new class_rectangle();
// }}}

/*
 *	Class rectangle_outline
 *
 *	Handles rectangle outlines
 */
// {{{ constructor
class_rectangle_outline = function() {
	this.init();
};
class_rectangle_outline.prototype = new class_rectangle();
// }}}
// {{{ redraw
class_rectangle_outline.prototype.redraw = function() {
	this.clear();
	
	this.lineStyle(0);
	this.moveTo(0, 0);
	this.lineTo(this.width, 0);
	this.lineTo(this.width, this.height);
	this.lineTo(0, this.height);
	this.lineTo(0, 0);
};
// }}}

/*
 *	Register Classes to Symbols in Library
 */
Object.registerClass("rectangle_back", class_rectangle_back);
Object.registerClass("rectangle_outline", class_rectangle_outline);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
