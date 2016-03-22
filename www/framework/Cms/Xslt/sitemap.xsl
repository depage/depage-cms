<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
    version="1.0"
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:sec="http://cms.depagecms.net/ns/section"
    xmlns:edit="http://cms.depagecms.net/ns/edit"
    extension-element-prefixes="xsl db proj pg sec edit ">

    <xsl:template match="proj:pages_struct">
        <urlset>
            <xsl:apply-templates select="*[not(@nav_hidden = 'true')]" />
        </urlset>
    </xsl:template>

    <xsl:template match="pg:folder">
        <xsl:apply-templates select="*[not(@nav_hidden = 'true')]" />
    </xsl:template>

    <xsl:template match="pg:page">
        <xsl:variable name="pageId" select="@db:id" />

        <xsl:for-each select="$languages/*">
            <xsl:variable name="lang" select="@shortname" />
            <url>
                <loc>
                    <xsl:value-of select="$baseUrl" /><xsl:value-of select="document(concat('pageref://', $pageId, '/', $lang, '/absolute'))/." disable-output-escaping="yes"/>
                </loc>
                <lastmod>
                    <xsl:value-of select="substring-before(dp:getpage($pageId)/pg:page_data/@db:lastchange, ' ')"/>
                </lastmod>
            </url>
        </xsl:for-each>

        <xsl:apply-templates select="*[not(@nav_hidden = 'true')]" />
    </xsl:template>

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
