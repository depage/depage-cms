class_tree_icon = function() { };
class_tree_icon.prototype = new MovieClip();

/* {{{ loadIcon() */
class_tree_icon.prototype.loadIcon = function(icon) {
    this.attachMovie("icon_" + icon, "icon", 0);
}
/* }}} */
/* {{{ getPageDataType() */
class_tree_icon.prototype.getPageDataType = function(node, isNotStartNode) {
    if (node == null || node.nodeName == "" || (node.nodeName.substr(0, 4) == conf.ns.section + ":" && isNotStartNode)) {
        return;
    }
    if (node.nodeName == conf.ns.edit + ":img") {
        this.dataInfo.hasImg = true;
    } else if (node.nodeName == conf.ns.edit + ":audio") {
        this.dataInfo.hasAudio = true;
    } else if (node.nodeName == conf.ns.edit + ":video") {
        this.dataInfo.hasVideo = true;
    } else if (node.nodeName == conf.ns.edit + ":flash") {
        this.dataInfo.hasFlash = true;
    } else if (node.nodeName == conf.ns.edit + ":list_formatted") {
        this.dataInfo.hasList = true;
        this.dataInfo.hintText += " " + this.getTextContent(node);
    } else if (node.nodeName.substr(0, 10) == conf.ns.edit + ":text_") {
        this.dataInfo.hasText = true;
        if (node.nodeName == conf.ns.edit + ":text_headline") {
            this.dataInfo.hasHeadline = true;
        }
        this.dataInfo.hintText += " " + this.getTextContent(node);
    } else if (node.nodeName == conf.ns.edit + ":plain_source") {
        this.dataInfo.hasSource = true;
        this.dataInfo.hintText += " " + this.dataInfo.getTextContent(node);
    } else if (node.nodeName == conf.ns.edit + ":a") {
        this.dataInfo.hasLink = true;
    }
    if (this.dataInfo.hasText == false) {
        this.getPageDataType(node.firstChild, true);
    }
    this.getPageDataType(node.nextSibling, true);
    
    this.dataInfo.hintText = this.dataInfo.hintText.replace("\n", " ");
};
/* }}} */
/* {{{ getTextContent() */
class_tree_icon.prototype.getTextContent = function(node) {
    textContent = " ";
    for (var i = 0; i < node.childNodes.length; i++) {
        if (textContent.length > 50) {
            return textContent;
        }
        if (node.childNodes[i].nodeType == 1) {
            textContent += this.getTextContent(node.childNodes[i]);
        } else if (node.childNodes[i].nodeType == 3) {
            textContent += node.childNodes[i].nodeValue;
        }
    }
    
    return textContent;
};
/* }}} */
/* {{{ getIconType() */
class_tree_icon.prototype.getIconType = function(node) {
    // init datainfo object
    this.datainfo = {
        hasImg: false,
        hasAudio: false,
        hasVideo: false,
        hasFlash: false,
        hasHeadline: false,
        hasSource: false,
        hasText: false,
        hasLink: false,
        hasList: false,
        hintText: ""
    }

    if (node.nodeName == conf.ns.page + ":meta") {
        treeIcon.loadIcon("meta");
        this.datainfo.icon = "meta";
    } else {
        this.getPageDataType(node);
        
        if (node.attributes.icon != "" && node.attributes.icon != undefined) {
            this.datainfo.icon = node.attributes.icon;
        } else if (this.datainfo.hasAudio) {
            this.datainfo.icon = "edit_audio";
        } else if (this.datainfo.hasVideo) {
            this.datainfo.icon = "edit_video";
        } else if (this.datainfo.hasFlash) {
            this.datainfo.icon = "edit_flash";
        } else if (this.datainfo.hasList) {
            this.datainfo.icon = "edit_list";
        } else if (this.datainfo.hasText && this.datainfo.hasImg) {
            this.datainfo.icon = "edit_imgtext";
        } else if (this.datainfo.hasHeadline) {
            this.datainfo.icon = "edit_headline";
        } else if (this.datainfo.hasText) {
            this.datainfo.icon = "edit_text";
        } else if (this.datainfo.hasImg) {
            this.datainfo.icon = "edit_img";
        } else if (this.datainfo.hasSource) {
            this.datainfo.icon = "edit_source";
        } else if (this.datainfo.hasLink) {
            this.datainfo.icon = "edit_a";
        } else {
            this.datainfo.icon = "edit_unknown";
        }
    }

    return this.datainfo;
}
/* }}} */

Object.registerClass("tree_icon", class_tree_icon);

/* vim:set ft=javascript sw=4 sts=4 fdm=marker : */
