<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" xmlns:dpg="http://www.depagecms.net/ns/depage" extension-element-prefixes="xsl rpc db proj pg sec edit backup dpg">

    <xsl:output method="html" indent="no" omit-xml-declaration="yes" />
    <xsl:strip-space elements="*" />

    <xsl:param name="projectName" />
    <xsl:variable name="maxlength" select="20" />

    <xsl:template match="/*">
        <xsl:choose>
            <xsl:when test="count(node()) > 0">
                <ul>
                    <xsl:apply-templates />
                </ul>
            </xsl:when>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="pg:* | sec:* | proj:folder | edit:plain_source">
        <!-- only show nodes with namespace "pg" or "sec" in tree -->
        <xsl:apply-templates select="." mode="treeNode" />
    </xsl:template>

    <xsl:template match="pg:meta">
        <xsl:apply-templates select="." mode="treeNodeWithoutChildren">
            <xsl:with-param name="name" select="'Meta'" />
        </xsl:apply-templates>
    </xsl:template>

    <xsl:template match="proj:colorscheme">
        <xsl:apply-templates select="." mode="treeNodeWithoutChildren">
            <xsl:with-param name="name" select="@name" />
        </xsl:apply-templates>
    </xsl:template>

    <xsl:template match="proj:colorscheme[@db:name = 'tree_name_color_global']">
        <xsl:apply-templates select="." mode="treeNodeWithoutChildren">
            <xsl:with-param name="name" select="'Global Colors'" />
        </xsl:apply-templates>
    </xsl:template>

    <xsl:template match="pg:title | pg:linkdesc | pg:desc" />

    <xsl:template match="*" mode="treeNode">
        <xsl:param name="showChildren" select="true()" />
        <xsl:param name="name" select="@name" />

        <xsl:variable name="id"><xsl:value-of select="@db:id" /></xsl:variable>
        <xsl:variable name="type" select="name()" />
        <xsl:variable name="icon">
            <xsl:value-of select="concat('icon-', translate($type, ':', '_'))" />
            <xsl:apply-templates select="." mode="icon-class" />
        </xsl:variable>
        <xsl:variable name="title">
            <xsl:apply-templates select="." mode="title" />
        </xsl:variable>
        <xsl:variable name="ns" select="substring-before(name(), ':')" />
        <xsl:variable name="href">
            <xsl:if test="name() = 'pg:page' or name() = 'pg:folder'">pageref://<xsl:value-of select="@db:id" /></xsl:if>
            <xsl:if test="name() = 'proj:folder'">libref:/<xsl:value-of select="@url" /></xsl:if>
        </xsl:variable>

        <li
            rel="{$type}"
            id="{$id}"
            data-doc-ref="{@db:docref}"
            data-url="{@url}"
            data-node-id="{@db:id}">
            <a href="{$href}" class="{$icon}" title="{$title}">
                <xsl:value-of select="$name" />
                <xsl:apply-templates select="." mode="hint" />
            </a>
            <xsl:if test="$showChildren and count(node()) > 0">
                <ul>
                    <xsl:apply-templates />
                </ul>
            </xsl:if>
        </li>
    </xsl:template>

    <xsl:template match="*" mode="treeNodeWithoutChildren">
        <xsl:param name="name" />

        <xsl:apply-templates select="." mode="treeNode">
            <xsl:with-param name="showChildren" select="false()" />
            <xsl:with-param name="name" select="$name" />
        </xsl:apply-templates>
    </xsl:template>

    <xsl:template match="sec:*" mode="hint">
        <span class="hint">
            <xsl:text> </xsl:text>
            <xsl:apply-templates select="edit:*" mode="hint" />
        </span>
    </xsl:template>

    <xsl:template match="edit:img" mode="hint">
        <xsl:if test="substring(@src, 1, 9) = 'libref://'">
            <!--
            <xsl:call-template name="filename">
                <xsl:with-param name="filename" select="@src" />
            </xsl:call-template>
            -->
            <span class="mini-thumb">
                <xsl:attribute name="style">background-image: url('projects/<xsl:value-of select="$projectName" />/lib/<xsl:value-of select="substring(@src, 10)" /><xsl:if test="not(substring(@src, string-length(@src) - 3) = '.svg')">.thumbfill-48x48.png</xsl:if>');</xsl:attribute>
            </span>
        </xsl:if>
    </xsl:template>

    <xsl:template match="edit:text_headline | edit:text_formatted" mode="hint">
        <xsl:value-of select="substring(., 1, $maxlength)"/>
        <xsl:if test="string-length(.) &gt; $maxlength">
           <xsl:text>...</xsl:text>
       </xsl:if>
       <xsl:text> </xsl:text>
    </xsl:template>

    <xsl:template match="edit:text_singleline" mode="hint">
        <xsl:value-of select="substring(@value, 1, $maxlength)"/>
        <xsl:if test="string-length(@value) &gt; $maxlength">
           <xsl:text>...</xsl:text>
       </xsl:if>
       <xsl:text> </xsl:text>
    </xsl:template>

    <xsl:template match="pg:* | edit:*" mode="hint" />

    <xsl:template match="sec:separator" mode="hint">—</xsl:template>

    <xsl:template match="*" mode="icon-class" />

    <xsl:template name="filename">
        <xsl:param name="filename"  />

        <xsl:choose>
            <xsl:when test="substring-after($filename, '/') = ''">
                <xsl:value-of select="$filename" /><xsl:text> </xsl:text>
            </xsl:when>
            <xsl:otherwise>
                <xsl:call-template name="filename">
                    <xsl:with-param name="filename" select="substring-after($filename, '/')" />
                </xsl:call-template>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="sec:*" mode="title">
        <xsl:value-of select="@name" />
        <xsl:text> </xsl:text>
        <xsl:apply-templates select="edit:*" mode="hint" />
    </xsl:template>

    <xsl:template match="pg:folder | pg:page" mode="title">
        <xsl:value-of select="@name" />
        <xsl:if test="@db:released = 'false'"> – changed ⤶</xsl:if>
    </xsl:template>

    <xsl:template match="pg:folder | pg:page" mode="icon-class">
        <xsl:if test="@nav_hidden = 'true'">
            <xsl:text> </xsl:text>
            page-hidden
        </xsl:if>
        <xsl:if test="@db:published = 'true'">
            <xsl:text> </xsl:text>
            page-published
        </xsl:if>
        <xsl:if test="@db:published = 'false'">
            <xsl:text> </xsl:text>
            page-not-published
        </xsl:if>
        <xsl:if test="@db:released = 'true'">
            <xsl:text> </xsl:text>
            page-released
        </xsl:if>
        <xsl:if test="@db:released = 'false'">
            <xsl:text> </xsl:text>
            page-unreleased
        </xsl:if>
    </xsl:template>

    <xsl:template match="pg:page[@redirect = 'true']" mode="icon-class">
        <xsl:text> </xsl:text>
        icon-redirect
    </xsl:template>

    <xsl:template match="sec:*" mode="icon-class">
        <xsl:text> </xsl:text>
        <xsl:choose>
            <xsl:when test="@icon != ''">
                icon-<xsl:value-of select="@icon" />
            </xsl:when>
            <xsl:when test="count(edit:video) &gt; 0">
               icon-edit_video
            </xsl:when>
            <xsl:when test="count(edit:audio) &gt; 0">
               icon-edit_audio
            </xsl:when>
            <xsl:when test="count(edit:img) &gt; 0 and count(edit:text_multiline | edit:text_formatted) &gt; 0">
            icon-edit_imgtext
            </xsl:when>
            <xsl:when test="count(edit:img) &gt; 0">
               icon-edit_img
            </xsl:when>
            <xsl:when test="count(edit:text_headline) &gt; 0">
               icon-edit_headline
            </xsl:when>
            <xsl:when test="count(edit:text_singleline | edit:text_multiline | edit:text_formatted) &gt; 0">
               icon-edit_text
            </xsl:when>
            <xsl:when test="count(edit:a) &gt; 0">
               icon-edit_a
            </xsl:when>
            <xsl:otherwise>
                icon-edit_unknown
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker et : -->
</xsl:stylesheet>
