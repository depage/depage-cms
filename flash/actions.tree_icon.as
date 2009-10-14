class_tree_icon = function() { };
class_tree_icon.prototype = new MovieClip();
class_tree_icon.prototype.loadIcon = function(icon) {
    this.attachMovie("icon_" + icon, "icon", 0);
}

Object.registerClass("tree_icon", class_tree_icon);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
