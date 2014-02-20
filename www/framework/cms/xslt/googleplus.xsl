<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [ 
    <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" extension-element-prefixes="xsl rpc db proj pg sec edit backup ">
<!-- {{{ Google Plus script -->
<xsl:template name="googleplusscript">		
    <script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
</xsl:template>
<!-- }}} -->
<!-- {{{ Google Plus Button -->
<xsl:template name="googleplusbutton">		
    <xsl:param name="size" select="'medium'" />
    <xsl:param name="count" select="'false'" />
    <xsl:param name="base" select="$baseurl" />
    <xsl:param name="href_id" select="$currentPageId"/>

    <xsl:text disable-output-escaping="yes">&lt;g:plusone</xsl:text> size="<xsl:value-of select="$size" />" count="<xsl:value-of select="$count" />" <xsl:if test="$href_id != ''"> href="<xsl:value-of select="concat($baseurl,substring(document(concat('pageref://', $href_id, '/', $currentLang,'/absolute'))/., 2))" />" </xsl:if> <xsl:text disable-output-escaping="yes">&gt;&lt;/g:plusone&gt;</xsl:text>
</xsl:template>
<!-- }}} -->
    
    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>

