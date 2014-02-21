<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet 
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
    xmlns:php="http://php.net/xsl"
    xmlns:dp="http://cms.depagecms.net/ns/depage" 
    xmlns:db="http://cms.depagecms.net/ns/database" 
    xmlns:pg="http://cms.depagecms.net/ns/page" 
    xmlns:func="http://exslt.org/functions" 
    extension-element-prefixes="xsl dp func ">

    <!-- {{{ dp:choose() -->
    <!--
        dp:choose(test, on-true, on-false)

    -->
    <func:function name="dp:choose">
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

    <!-- {{{ dp:getpage() -->
    <!--
        dp:getpage(pageid)

    -->
    <func:function name="dp:getpage">
        <xsl:param name="pageid" />
        <xsl:variable name="pagedataid" select="$navigation//pg:*[@db:id = $pageid]/@db:docref" />

        <func:result select="document(concat('xmldb://', $pagedataid))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:changesrc() -->
    <!--
        dp:changesrc(src)

        @todo define these automatically
    -->
    <func:function name="dp:changesrc">
        <xsl:param name="src" />

        <func:result select="php:function('depage\cms\xslt\FuncDelegate::changesrc', string($src))" />
    </func:function>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
