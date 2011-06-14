<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" xmlns:dpg="http://www.depagecms.net/ns/depage" extension-element-prefixes="xsl rpc db proj pg sec edit backup dpg">

<xsl:output method="html" indent="no" omit-xml-declaration="yes" />
<xsl:strip-space elements="*" />

<!-- ignore root element, only transform children -->
<xsl:template match="/*">
    <ul>
    <xsl:apply-templates />
    </ul>
</xsl:template>

<xsl:template match="node()">
    <xsl:variable name="id" select="@db:id" />
    <!-- TODO: <xsl:variable name="type" select="@type" /> -->
    <xsl:variable name="type" select="'folder'" />
    <xsl:variable name="name" select="@name" />
    <xsl:variable name="hint" select="@hint" />

    <li rel='{$type}' id='node_{$id}'><ins class='jstree-icon'>&#160;</ins><a href=''><ins class='jstree-icon'>&#160;</ins><xsl:value-of select="$name" /></a><span><xsl:value-of select="$hint" /></span>
    <xsl:choose>
        <xsl:when test="count(node()) > 0">
            <ul>
            <xsl:apply-templates />
            </ul>
        </xsl:when>
    </xsl:choose>
    </li>
</xsl:template>

<!-- vim:set fenc=UTF-8 sw=4 sts=4 fdm=marker et : -->
        
</xsl:stylesheet>
