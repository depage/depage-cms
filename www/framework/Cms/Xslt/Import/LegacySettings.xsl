<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:func="http://exslt.org/functions"
    extension-element-prefixes="xsl dp func php ">

    <xsl:template match="@*|node()">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
        </xsl:copy>
    </xsl:template>

    <!-- {{{ update navigation/tags -->
    <xsl:template match="proj:navigations">
        <proj:navigation>
            <xsl:apply-templates select="@*"/>
            <xsl:apply-templates select="proj:navigation[not(substring(@shortname, 1, 4) = 'tag_' or substring(@shortname, 1, 4) = 'cat_')]" mode="navigation"/>
        </proj:navigation>
        <proj:tags>
            <xsl:apply-templates select="proj:navigation[substring(@shortname, 1, 4) = 'tag_' or substring(@shortname, 1, 4) = 'cat_']" mode="tag" />
        </proj:tags>
    </xsl:template>
    <xsl:template match="proj:navigation" mode="navigation">
        <xsl:copy>
            <xsl:apply-templates select="@*|node()"/>
            <xsl:variable name="nav" select="." />

            <xsl:for-each select="//proj:language">
                <localized>
                    <xsl:attribute name="lang"><xsl:value-of select="@shortname" /></xsl:attribute>
                    <xsl:value-of select="$nav/@name" />
                </localized>
            </xsl:for-each>
        </xsl:copy>
    </xsl:template>
    <xsl:template match="proj:navigation" mode="tag">
        <proj:tag>
            <xsl:apply-templates select="@*|node()"/>
            <xsl:variable name="tag" select="." />

            <xsl:for-each select="//proj:language">
                <xsl:variable name="lang" select="@shortname" />

                <localized>
                    <xsl:attribute name="lang"><xsl:value-of select="$lang" /></xsl:attribute>

                    <xsl:choose>
                        <xsl:when test="$lang = 'en'">
                            <xsl:value-of select="substring($tag/@shortname, 5)" />
                        </xsl:when>
                        <xsl:when test="$lang = 'de'">
                            <xsl:value-of select="substring-after($tag/@name, ': ')" />
                        </xsl:when>
                        <otherwise>
                            <xsl:value-of select="$tag/@name" />
                        </otherwise>
                    </xsl:choose>
                </localized>
            </xsl:for-each>
        </proj:tag>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ update publish targets -->
    <xsl:template match="proj:publish">
        <proj:publishTargets>
            <xsl:apply-templates select="@*"/>
            <xsl:apply-templates select="*"/>
        </proj:publishTargets>
    </xsl:template>
    <xsl:template match="proj:publish_folder">
        <proj:publishTarget>
            <xsl:attribute name="default"><xsl:if test="position() = 1">true</xsl:if><xsl:if test="position() &gt; 1">false</xsl:if></xsl:attribute>

            <xsl:apply-templates select="@*|node()"/>
        </proj:publishTarget>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ delete elements -->
    <!-- @todo delete proj:type -->
    <xsl:template match="proj:backup|@db:name|@db:invalid"></xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
