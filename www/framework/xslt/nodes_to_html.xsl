<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" xmlns:dpg="http://www.depagecms.net/ns/depage" extension-element-prefixes="xsl rpc db proj pg sec edit backup dpg">

<xsl:output method="html" indent="no" omit-xml-declaration="yes" />
<xsl:strip-space elements="*" />

<xsl:template match="/*">
    <xsl:choose>
        <xsl:when test="count(node()) > 0">
            <ul>
                <xsl:apply-templates />
            </ul>
        </xsl:when>
    </xsl:choose>
</xsl:template>

<xsl:template match="pg:* | sec:*">
    <xsl:apply-templates select="." mode="treeNode" />
</xsl:template>

<xsl:template match="pg:meta">
    <xsl:apply-templates select="." mode="treeNodeWithoutChildren">
        <xsl:with-param name="name" select="'Meta'" />
    </xsl:apply-templates>
</xsl:template>

<xsl:template match="*" mode="treeNode">
    <xsl:param name="showChildren" select="true()" />
    <xsl:param name="name" select="@name" />

    <xsl:variable name="id" select="@db:id" />
    <xsl:variable name="type" select="name()" />
    <xsl:variable name="icon" select="concat('icon-', translate($type, ':', '-'))" />
    <xsl:variable name="hint">
        <xsl:value-of select="@hint" />
        <xsl:choose>
            <xsl:when test="$type = 'sec:separator'">–</xsl:when>
            <xsl:otherwise><xsl:value-of select="$type" /></xsl:otherwise>
        </xsl:choose>
    </xsl:variable>
    <xsl:variable name="ns" select="substring-before(name(), ':')" />

    <!-- only show nodes with namespace "pg" or "sec" in tree -->
    <li
        rel="{$type}"
        id="node_{$id}"
        data-doc-ref="{@db:docref}"
        data-url="{@url}"
        data-node-id="{$id}">
        <a href="" class="{$icon}">
            <xsl:value-of select="$name" />
            <span><xsl:value-of select="$hint" /></span>
        </a>
        <xsl:choose>
            <xsl:when test="$showChildren and count(node()) > 0">
                <ul>
                    <xsl:apply-templates />
                </ul>
            </xsl:when>
        </xsl:choose>
    </li>
</xsl:template>

<xsl:template match="*" mode="treeNodeWithoutChildren">
    <xsl:param name="name" />

    <xsl:apply-templates select="." mode="treeNode">
        <xsl:with-param name="showChildren" select="false()" />
        <xsl:with-param name="name" select="$name" />
    </xsl:apply-templates>
</xsl:template>

<!-- vim:set ft=xslt sw=4 sts=4 fdm=marker et : -->
</xsl:stylesheet>
