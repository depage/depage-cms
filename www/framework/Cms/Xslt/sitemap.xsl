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
    extension-element-prefixes="xsl db dp proj pg sec edit ">

    <xsl:import href="xslt://base.xsl" />

    <xsl:decimal-format decimal-separator="." />

    <xsl:template match="proj:pages_struct">
        <urlset>
            <xsl:text disable-output-escaping="yes">
</xsl:text>
            <xsl:apply-templates select="pg:*[dp:pageVisible(.)]" />
        </urlset>
    </xsl:template>

    <xsl:template match="pg:folder">
        <xsl:apply-templates select="pg:*[dp:pageVisible(.)]" />
    </xsl:template>

    <xsl:template match="pg:redirect">
    </xsl:template>

    <xsl:template match="sec:separator">
    </xsl:template>

    <xsl:template match="pg:page">
        <xsl:variable name="page" select="." />
        <xsl:variable name="pageId" select="$page/@db:id" />
        <xsl:variable name="pageContent" select="dp:getpage($pageId)" />

        <xsl:variable name="lastmod">
            <xsl:apply-templates select="$page" mode="lastmod">
                <xsl:with-param name="page" select="$page" />
                <xsl:with-param name="pageId" select="$pageId" />
                <xsl:with-param name="pageContent" select="$pageContent" />
            </xsl:apply-templates>
        </xsl:variable>
        <xsl:variable name="prio">
            <xsl:apply-templates select="$page" mode="priority">
                <xsl:with-param name="page" select="$page" />
                <xsl:with-param name="pageId" select="$pageId" />
                <xsl:with-param name="pageContent" select="$pageContent" />
            </xsl:apply-templates>
        </xsl:variable>
        <xsl:variable name="priority">
            <xsl:choose>
                <xsl:when test="$prio &gt; 1">1</xsl:when>
                <xsl:when test="$prio &lt; 0.1">0.1</xsl:when>
                <xsl:otherwise><xsl:value-of select="format-number($prio, '0.0')" /></xsl:otherwise>
            </xsl:choose>
        </xsl:variable>

        <xsl:for-each select="$languages/*">
            <xsl:variable name="lang" select="@shortname" />
            <xsl:if test="dp:pageVisible($page, $lang)">
                <xsl:variable name="loc" select="dp:getPageRef($pageId, $lang, true())" />
                <url>
                    <loc><xsl:value-of select="$loc" /></loc>
                    <lastmod><xsl:value-of select="$lastmod" /></lastmod>
                    <priority><xsl:value-of select="$priority" /></priority>
                </url>
                <xsl:text disable-output-escaping="yes">
</xsl:text>
            </xsl:if>
        </xsl:for-each>

        <xsl:apply-templates select="pg:*[dp:pageVisible(.)]" />
    </xsl:template>

    <xsl:template match="pg:page" mode="lastmod">
        <xsl:param name="page" select="." />
        <xsl:param name="pageId" select="$page/@db:id" />
        <xsl:param name="pageContent" />

        <xsl:apply-templates select="." mode="lastmod-lastchange" />
    </xsl:template>

    <xsl:template match="pg:page" mode="lastmod-lastchange">
        <xsl:param name="page" select="." />
        <xsl:param name="pageId" select="$page/@db:id" />
        <xsl:param name="pageContent" />

        <xsl:value-of select="concat(translate(dp:getpage($pageId)/pg:page_data/@db:lastchange, ' ', 'T'), 'Z')"/>
    </xsl:template>

    <xsl:template match="pg:page" mode="lastmod-now">
        <xsl:param name="page" select="." />
        <xsl:param name="pageId" select="$page/@db:id" />
        <xsl:param name="pageContent" />

        <xsl:value-of select="dp:formatDate('now', 'Y-m-d\TH:i:s\Z')"/>
    </xsl:template>

    <xsl:template match="pg:page" mode="priority">
        <xsl:param name="page" select="." />
        <xsl:param name="pageId" select="$page/@db:id" />
        <xsl:param name="pageContent" />

        <xsl:variable name="extra" select="dp:choose($page/@nav_featured = 'true', 5, 0)" />

        <xsl:value-of select="format-number((5 + $extra) div 10, '0.0')" />
    </xsl:template>

    <xsl:template match="proj:pages_struct/pg:page[1]" mode="priority">
        <xsl:param name="page" select="." />
        <xsl:param name="pageId" select="$page/@db:id" />
        <xsl:param name="pageContent" />

        1
    </xsl:template>

    <xsl:template match="proj:pages_struct/pg:*[@nav_blog='true' or @nav_news='true']//pg:page" mode="priority">
        <xsl:param name="page" select="." />
        <xsl:param name="pageId" select="$page/@db:id" />
        <xsl:param name="pageContent" />

        <xsl:variable name="currentYear" select="dp:formatDate('now', 'Y')" />
        <xsl:variable name="year" select="../../@name" />

        <xsl:variable name="extra" select="dp:choose($page/@nav_featured = 'true', 1, 0)" />

        <xsl:value-of select="format-number((10 - $currentYear + $year + $extra) div 10, '0.0')" />
    </xsl:template>

    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
