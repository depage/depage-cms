<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" xmlns:dpg="http://www.depagecms.net/ns/depage" extension-element-prefixes="xsl rpc db proj pg sec edit backup dpg">

<xsl:output method="html" indent="no" omit-xml-declaration="yes" />
<xsl:strip-space elements="*" />

<!-- ignore root element, only transform children -->
<xsl:template match="/*">
    <xsl:choose>
        <xsl:when test="count(node()) > 0">
            <ul>
                <xsl:apply-templates />
            </ul>
        </xsl:when>
    </xsl:choose>
</xsl:template>

<xsl:template match="node()">
    <xsl:variable name="id" select="@db:id" />
    <xsl:variable name="type" select="name()" />
    <xsl:variable name="name" select="@name" />
    <xsl:variable name="hint">
        <xsl:value-of select="@hint" />
        <xsl:choose>
            <xsl:when test="$type = 'pg:separator'">—</xsl:when>
            <xsl:otherwise><xsl:value-of select="substring($type, 4)" /></xsl:otherwise>
        </xsl:choose>
    </xsl:variable>
    <xsl:variable name="ns" select="substring-before(name(), ':')" />

    <!-- only show nodes with namespace "pg" or "sec" in tree -->
    <xsl:if test="$ns = 'pg' or $ns = 'sec'">
        <li rel='{$type}' id='node_{$id}' data-db-ref='{@db:ref}'>
            <ins class='jstree-icon jstree-ocl'>&#160;</ins>
            <a href=''>
                <ins class='jstree-icon jstree-themeicon'>&#160;</ins>
                <xsl:value-of select="$name" />
                <span><xsl:value-of select="$hint" /></span>
            </a>
            <xsl:choose>
                <xsl:when test="count(node()) > 0">
                    <ul>
                        <xsl:apply-templates />
                    </ul>
                </xsl:when>
            </xsl:choose>
        </li>
    </xsl:if>
</xsl:template>

<!-- vim:set ft=xslt sw=4 sts=4 fdm=marker et : -->
</xsl:stylesheet>
