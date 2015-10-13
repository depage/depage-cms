<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY % htmlentities SYSTEM "xslt://htmlentities.ent"> %htmlentities;
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

    <!--
        DEBUG ROOT
    -->
    <xsl:output method="html"/>
    <xsl:template match="/">
        <xsl:value-of select="'&lt;!DOCTYPE html&gt;&#xa;'" disable-output-escaping="yes"/>
        <html>
            <head>
                <title>debugging</title>
                <style type="text/css">
                    <xsl:comment>
                    * {
                        font-family : verdana, geneva, arial, tahoma, helvetica, sans-serif;
                        font-size : 11px;
                        line-height : 15px;
                        text-decoration : none;
                        margin-top : 0px;
                        margin-bottom : 0px;
                    }
                    a {
                        font-decoration : none;
                        color : #000000;
                    }
                    a.hover {
                        font-decoration : underline;
                    }
                    </xsl:comment>
                </style>
            </head>
            <body>
                <table>
                    <tr>
                        <td width="20%" valign="top">
                            <p><b>[ NAVIGATION ]</b><br /><br /></p>
                            <xsl:for-each select="$navigation/proj:pages_struct/*">
                                <xsl:call-template name="element_navigation" />
                            </xsl:for-each>
                        </td>
                        <td width="20px" />
                        <td width="80%" valign="top">
                            <p><b>[ DOKUMENT ]</b><br /><br /></p>
                            <xsl:for-each select="*">
                                <xsl:call-template name="element" />
                            </xsl:for-each>
                        </td>
                    </tr>
                </table>
            </body>
        </html>
    </xsl:template>

    <!-- ELEMENT -->
    <xsl:template match="*" name="element">
        <xsl:param name="level" select="1" />
        <xsl:if test="name() != ''">
            <p>
                <xsl:attribute name="style">margin-left:<xsl:value-of select="$level * 15 - 15" />px</xsl:attribute><b>&lt;<xsl:value-of select="name()" /></b>&nbsp;<xsl:for-each select="./@*"><xsl:call-template name="attribute" /></xsl:for-each><xsl:if test="count(./*) > 0 or not(string(.) = '')"><b>&gt;</b></xsl:if><xsl:if test="count(./*) = 0 and string(.) = ''"><b>/&gt;</b></xsl:if>
            </p>
        </xsl:if>
        <xsl:if test="count(./*) = 0 and not(string(.) = '')">
            <xsl:text disable-output-escaping="yes">&lt;p style=&quot;margin-left:</xsl:text><xsl:value-of select="$level * 15" />px<xsl:text disable-output-escaping="yes">&quot;&gt;</xsl:text>
        </xsl:if>
        <xsl:apply-templates>
            <xsl:with-param name="level" select="$level + 1"></xsl:with-param>
        </xsl:apply-templates>
        <xsl:if test="count(./*) = 0 and not(string(.) = '')">
            <xsl:text disable-output-escaping="yes">&lt;/p&gt;</xsl:text>
        </xsl:if>
        <xsl:if test="(count(./*) > 0 or not(string(.) = '')) and name() != ''">
            <p><xsl:attribute name="style">    margin-left:<xsl:value-of select="$level * 15 - 15" />px</xsl:attribute><b>&lt;/<xsl:value-of select="name()" />&gt;</b></p>
        </xsl:if>
    </xsl:template>

    <!-- ATTRIBUTE -->
    <xsl:template name="attribute">
        <xsl:text> </xsl:text>
        <xsl:value-of select="name()" />=&quot;<span style="color:#ff9900;"><xsl:value-of select="." /></span>&quot;
    </xsl:template>

    <!-- ELEMENT NAVIGATION -->
    <xsl:template name="element_navigation">
        <xsl:param name="level" select="1" />
        <p>
            <xsl:attribute name="style">margin-left:<xsl:value-of select="$level * 15 - 15" />px</xsl:attribute>
        <a>
            <xsl:attribute name="href">
                <xsl:value-of select="document(concat('pageref://', @db:id, '/', $currentLang))/." disable-output-escaping="yes" />
            </xsl:attribute>
            <xsl:choose>
                <xsl:when test="@status = 'active'" >
                    <b><xsl:if test="name() = 'pg:folder'">[</xsl:if><xsl:value-of select="@name" /><xsl:if test="name() = 'pg:folder'">]</xsl:if> &nbsp;(<xsl:value-of select="@db:id" />)</b>
                </xsl:when>
                <xsl:when test="@status = 'parent-of-active'" >
                    <b><span style="color:#555555;"><xsl:if test="name() = 'pg:folder'">[</xsl:if><xsl:value-of select="@name" /><xsl:if test="name() = 'pg:folder'">]</xsl:if></span></b> &nbsp;(<xsl:value-of select="@db:id" />)
                </xsl:when>
                <xsl:otherwise>
                    <xsl:if test="name() = 'pg:folder'">[</xsl:if><xsl:value-of select="@name" /><xsl:if test="name() = 'pg:folder'">]</xsl:if> &nbsp;(<xsl:value-of select="@db:id" />)
                </xsl:otherwise>
                <!--xsl:for-each select="./@*"><xsl:call-template name="attribute" /></xsl:for-each-->
            </xsl:choose>
            </a>
        </p>
        <xsl:for-each select="./*">
            <xsl:call-template name="element_navigation">
                <xsl:with-param name="level" select="$level + 1"></xsl:with-param>
            </xsl:call-template>
        </xsl:for-each>
    </xsl:template>

    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
