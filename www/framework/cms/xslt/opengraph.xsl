<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [ 
    <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" extension-element-prefixes="xsl rpc db proj pg sec edit backup ">
<!-- {{{ opengraph -->
<xsl:template name="opengraph">		
    <xsl:param name="title"></xsl:param>
    <xsl:param name="type"></xsl:param>
    <xsl:param name="userid"><xsl:value-of select="$tt_var_fb-Account" /></xsl:param>
    <xsl:param name="sitename"><xsl:value-of select="$tt_var_Title" /></xsl:param>
    <xsl:param name="url"><xsl:value-of select="concat($baseurl,substring(document(concat('pageref:/', $tt_actual_id, '/', $tt_lang,'/absolute'))/., 2))" /></xsl:param>
    <xsl:param name="description"><xsl:value-of select="//pg:meta/pg:desc[@lang = $tt_lang]/@value"/></xsl:param>
    <xsl:param name="image"></xsl:param>

    <xsl:variable name="ogtitle">
        <xsl:choose>
            <xsl:when test="$title != ''"><xsl:value-of select="$title" /></xsl:when>
            <xsl:otherwise>
                <!-- get title from meta -->
                <xsl:choose>
                    <xsl:when test="$tt_multilang = 'true' and //pg:meta/pg:title[@lang = $tt_lang]/@value != '' "><xsl:value-of select="//pg:meta/pg:title[@lang = $tt_lang]/@value"/></xsl:when>
                    <xsl:when test="$tt_multilang = 'true' and //pg:meta/pg:linkdesc[@lang = $tt_lang]/@value != '' "><xsl:value-of select="//pg:meta/pg:linkdesc[@lang = $tt_lang]/@value"/></xsl:when>
                    <xsl:when test="//pg:meta/pg:title/@value != '' "><xsl:value-of select="//pg:meta/pg:title/@value"/></xsl:when>
                    <xsl:when test="//pg:meta/pg:linkdesc/@value != '' "><xsl:value-of select="//pg:meta/pg:linkdesc/@value"/></xsl:when>
                    <xsl:otherwise><xsl:value-of select="/pg:page/@name" /></xsl:otherwise>
                </xsl:choose>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:variable>

    <xsl:variable name="ogtype">
        <xsl:choose>
            <xsl:when test="$type != ''"><xsl:value-of select="$type" /></xsl:when>
            <xsl:otherwise>article</xsl:otherwise>
        </xsl:choose>
    </xsl:variable>

    <xsl:variable name="imageurl">
        <xsl:choose>
            <xsl:when test="starts-with($image, 'libref:')"><xsl:value-of select="concat($baseurl, substring(document($image)/., 2))" /></xsl:when>
            <xsl:otherwise><xsl:value-of select="$image" /></xsl:otherwise>
        </xsl:choose>
    </xsl:variable>


    <meta property="og:title"><xsl:attribute name="content"><xsl:value-of select="$ogtitle" /></xsl:attribute></meta>
    <meta property="og:type"><xsl:attribute name="content"><xsl:value-of select="$ogtype" /></xsl:attribute></meta>

    <xsl:text disable-output-escaping="yes">
        &lt;!--&lt;?php
            $url = "http://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
            echo("-" . "-&gt;");
            echo("&lt;meta property=\"og:url\" content=\"$url\"&gt;");
            echo("&lt;!-" . "-;");
            if (false) {
        ?&gt;--&gt;
    </xsl:text>
    <meta property="og:url"><xsl:attribute name="content"><xsl:value-of select="$url" /></xsl:attribute></meta>
    <xsl:text disable-output-escaping="yes">
        &lt;!--&lt;?php
            }
        ?&gt;--&gt;
    </xsl:text>
    
    <meta property="og:site_name"><xsl:attribute name="content"><xsl:value-of select="$sitename" /></xsl:attribute></meta>
    <xsl:if test="$userid != ''">
        <meta property="fb:admins"><xsl:attribute name="content"><xsl:value-of select="$userid" /></xsl:attribute></meta>
    </xsl:if>
    <xsl:if test="$description != ''">
        <meta property="og:description"><xsl:attribute name="content"><xsl:value-of select="$description" /></xsl:attribute></meta>
    </xsl:if>
    <xsl:if test="$image != ''">
        <meta property="og:image"><xsl:attribute name="content"><xsl:value-of select="$imageurl" /></xsl:attribute></meta>
    </xsl:if>
</xsl:template>
<!-- }}} -->
    
    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
