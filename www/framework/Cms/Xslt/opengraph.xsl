<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
    <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:sec="http://cms.depagecms.net/ns/section"
    xmlns:edit="http://cms.depagecms.net/ns/edit"
    extension-element-prefixes="xsl db proj pg sec edit ">

<!-- {{{ opengraph -->
<xsl:template name="opengraph">
    <xsl:param name="title"></xsl:param>
    <xsl:param name="type"></xsl:param>
    <xsl:param name="userid"><xsl:value-of select="$var-fb-Account" /></xsl:param>
    <xsl:param name="sitename"><xsl:value-of select="$var-Title" /></xsl:param>
    <xsl:param name="url"><xsl:value-of select="dp:getPageRef($currentPageId, $currentLang, true())" /></xsl:param>
    <xsl:param name="description"><xsl:value-of select="//pg:meta/pg:desc[@lang = $currentLang]/@value"/></xsl:param>
    <xsl:param name="image"></xsl:param>
    <xsl:param name="lastModified"><xsl:value-of select="//pg:page_data/@db:lastchange"/></xsl:param>
    <xsl:param name="lastPublished"></xsl:param>

    <xsl:variable name="ogtitle">
        <xsl:choose>
            <xsl:when test="not($title = '')"><xsl:value-of select="$title" /></xsl:when>
            <xsl:otherwise>
                <!-- get title from meta -->
                <xsl:choose>
                    <xsl:when test="$currentPage/@multilang = 'true' and //pg:meta/pg:title[@lang = $currentLang]/@value != '' "><xsl:value-of select="//pg:meta/pg:title[@lang = $currentLang]/@value"/></xsl:when>
                    <xsl:when test="$currentPage/@multilang = 'true' and //pg:meta/pg:linkdesc[@lang = $currentLang]/@value != '' "><xsl:value-of select="//pg:meta/pg:linkdesc[@lang = $currentLang]/@value"/></xsl:when>
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
            <xsl:when test="starts-with($image, 'libref://')"><xsl:value-of select="dp:getLibRef($image)" /></xsl:when>
            <xsl:otherwise><xsl:value-of select="$image" /></xsl:otherwise>
        </xsl:choose>
    </xsl:variable>


    <meta property="og:title"><xsl:attribute name="content"><xsl:value-of select="$ogtitle" /></xsl:attribute></meta>
    <meta property="og:type"><xsl:attribute name="content"><xsl:value-of select="$ogtype" /></xsl:attribute></meta>

    <meta property="og:url"><xsl:attribute name="content"><xsl:value-of select="$url" /></xsl:attribute></meta>

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
    <meta property="article:modified_time"><xsl:attribute name="content"><xsl:value-of select="dp:formatDate($lastModified, 'Y-m-d\TH:i:s\Z')" /></xsl:attribute></meta>
    <xsl:if test="$lastPublished != ''">
        <meta property="article:published_time"><xsl:attribute name="content"><xsl:value-of select="dp:formatDate($lastPublished, 'Y-m-d\TH:i:s\Z')" /></xsl:attribute></meta>
    </xsl:if>
</xsl:template>
<!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
