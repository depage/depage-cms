<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
<!--
    Converts a nodeset to string, especially useful for json conversions.

    ===================================================================================

    Copyright 2011, Thomas Appel, http://thomas-appel.com, mail(at)thomas-appel.com
    dual licensed under MIT and GPL license
    http://dev.thomas-appel.com/licenses/mit.txt
    http://dev.thomas-appel.com/licenses/gpl.txt

    ===================================================================================

    Example usage:

    (convert a exsl nodeset to string: )
    ___

    <xsl:variable name="somelink">
        <a href="{url}" class="some-class"><xsl:value-of select="name"/></a>
    </xsl:variable>
    <xsl:apply-templates select="exsl:node-set($somelink)/* | exsl:node-set($some-link)/text()"/>
    ___

    (convert xml noset to string: )
    ___

    <xsl:apply-templates select="node | node[text()]"/>

-->

    <xsl:template match="*" mode="nodetostring-selfclosetag">
        <xsl:text>&lt;</xsl:text>
        <xsl:value-of select="name()"/>
        <xsl:apply-templates select="@*" mode="nodetostring"/>
        <xsl:text>/&gt;</xsl:text>
    </xsl:template>

    <xsl:template match="*" mode="nodetostring-opentag">
        <xsl:text>&lt;</xsl:text>
        <xsl:value-of select="name()"/>
        <xsl:apply-templates select="@*" mode="nodetostring"/>
        <xsl:text>&gt;</xsl:text>
    </xsl:template>

    <xsl:template match="*" mode="nodetostring-closetag">
        <xsl:text>&lt;/</xsl:text>
        <xsl:value-of select="name()"/>
        <xsl:text>&gt;</xsl:text>
    </xsl:template>

    <xsl:template match="* | text()" mode="nodetostring">
        <xsl:variable name="empty"/>

        <xsl:choose>
            <xsl:when test="boolean(name())">
                <xsl:choose>
                    <!--
                         if element is not empty
                    -->
                    <xsl:when test="normalize-space(.) != $empty or *">
                        <xsl:apply-templates select="." mode="nodetostring-opentag"/>
                            <xsl:apply-templates select="* | text() | processing-instruction()" mode="nodetostring"/>
                        <xsl:apply-templates select="." mode="nodetostring-closetag"/>
                    </xsl:when>
                    <!--
                         assuming emty tags are self closing, e.g. <img/>, <source/>, <input/>
                    -->
                    <xsl:otherwise>
                        <xsl:apply-templates select="." mode="nodetostring-selfclosetag"/>
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="."/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="processing-instruction()" mode="nodetostring">
        <xsl:text>&lt;?</xsl:text>
        <xsl:value-of select="name()"/><xsl:text> </xsl:text>
        <xsl:value-of select="."/>
        <xsl:text>&gt;</xsl:text>
    </xsl:template>

    <xsl:template match="@*" mode="nodetostring">
        <xsl:variable name="q">
            <xsl:text>"</xsl:text>
        </xsl:variable>

        <xsl:if test="position() = 1">
            <xsl:text> </xsl:text>
        </xsl:if>
        <xsl:value-of select="concat(name(), '=', $q, ., $q)"/>
        <xsl:if test="position() != last()">
            <xsl:text> </xsl:text>
        </xsl:if>
    </xsl:template>

    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
