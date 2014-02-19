<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet 
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
    xmlns:dp="http://cms.depagecms.net/ns/depage" 
    xmlns:func="http://exslt.org/functions" 
    extension-element-prefixes="xsl dp func ">

    <!-- {{{ if() -->
    <!--
        dp:if(test, on-true, on-false)

    -->
    <func:function name="dp:if">
        <xsl:param name="test"/>
        <xsl:param name="a" />
        <xsl:param name="b" />

        <xsl:choose>
            <xsl:when test="$test != '' and $test != false()">
                <func:result select="$a" />
            </xsl:when>
            <xsl:otherwise>
                <func:result select="$b" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
