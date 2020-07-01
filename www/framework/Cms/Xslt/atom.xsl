<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
    version="1.0"
    xmlns="http://www.w3.org/2005/Atom"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:sec="http://cms.depagecms.net/ns/section"
    xmlns:edit="http://cms.depagecms.net/ns/edit"
    extension-element-prefixes="xsl db dp proj pg sec edit php ">

    <xsl:import href="xslt://base.xsl" />
    <xsl:import href="xslt://atom-html.xsl" />

    <xsl:variable name="campaign" select="concat('?utm_campaign=', 'atom-feed')" />

    <!-- {{{ root -->
    <xsl:template match="/">
        <feed>
            <xsl:call-template name="init-feed" />
            <xsl:for-each select="$navigation//*[@nav_atom = 'true']/descendant-or-self::pg:page[dp:pageVisible(.)]">
                <xsl:if test="position() &lt; $num_items">
                    <xsl:variable name="url" select="@url" />
                    <xsl:variable name="pageid" select="@db:id" />

                    <xsl:for-each select="dp:getpage(@db:id)//pg:page_data//*[name() = $entries]">
                        <xsl:if test="position() &lt; $num_items">
                            <!-- generate newsline before every entry -->
                            <xsl:text>
</xsl:text><entry>
                                <xsl:call-template name="entry">
                                    <xsl:with-param name="anchor" select="concat('#entry-',@db:id)" />
                                    <xsl:with-param name="pageid" select="$pageid" />
                                </xsl:call-template>
                            </entry>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>
            </xsl:for-each>
        </feed>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ init-feed -->
    <xsl:template name="init-feed">
        <title><xsl:value-of select="$title" /></title>

        <link><xsl:attribute name="href"><xsl:value-of select="$baseUrl" /></xsl:attribute></link>
        <link rel="self"><xsl:attribute name="href"><xsl:value-of select="concat($baseUrl,$currentLang,'/atom.xml')" /></xsl:attribute></link>

        <id><xsl:value-of select="$baseUrl" /><xsl:value-of select="$campaign" /></id>
        <updated><xsl:value-of select="dp:formatDate('now', 'Y-m-d\TH:i:s\Z')" /></updated>
        <author>
            <name><xsl:value-of select="$author" /></name>
        </author>
        <rights><xsl:value-of select="$rights" /></rights>
        <xsl:if test="$icon != ''">
            <icon><xsl:value-of select="concat($baseUrl,$icon)" /></icon>
        </xsl:if>
        <xsl:if test="$logo != ''">
            <logo><xsl:value-of select="concat($baseUrl,$logo)" /></logo>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ entry -->
    <xsl:template name="entry">
        <xsl:param name="anchor" />
        <xsl:param name="pageid" />

        <link><xsl:attribute name="href"><xsl:value-of select="$baseUrl" /><xsl:value-of select="document(concat('pageref://',$pageid,'/',$currentLang))" /><xsl:value-of select="$campaign" /><xsl:value-of select="$anchor" /></xsl:attribute></link>
        <id><xsl:value-of select="$baseUrl" /><xsl:value-of select="document(concat('pageref://',$pageid,'/',$currentLang))" /><xsl:value-of select="$anchor" /></id>
        <updated><xsl:value-of select="dp:formatDate(edit:date/@value, 'Y-m-d\TH:i:s\Z')" /></updated>
        <title><xsl:value-of select="edit:text_headline[@lang = $currentLang]/*" /></title>
        <summary><xsl:value-of select=".//edit:text_formatted[@lang = $currentLang and 1]/*" /></summary>
        <content type="xhtml">
            <div xmlns="http://www.w3.org/1999/xhtml">
                <xsl:call-template name="content" />
            </div>
        </content>
    </xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>

