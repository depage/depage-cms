<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:func="http://exslt.org/functions"
    xmlns:exslt="http://exslt.org/common"
    extension-element-prefixes="xsl dp func php exslt ">

    <xsl:include href="xslt://nodetostring.xsl" />

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

        <xsl:choose>
            <xsl:when test="$pagedataid = ''">
                <func:result />
            </xsl:when>
            <xsl:otherwise>
                <func:result select="document(concat('xmldb://', $pagedataid))" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:color() -->
    <!--
        dp:color(colorname)

    -->
    <func:function name="dp:color">
        <xsl:param name="name" />
        <xsl:param name="colorscheme" select="$currentColorscheme" />

        <xsl:choose>
            <xsl:when test="$colors//proj:colorscheme[@name = $colorscheme]/color[@name = $name]">
                <!-- color from named colorscheme -->
                <func:result select="translate($colors//proj:colorscheme[@name = $colorscheme]/color[@name = $name]/@value,'ABCDEF','abcdef')" />
            </xsl:when>
            <xsl:otherwise>
                <!-- color from default colorscheme -->
                <func:result select="translate($colors//color[@name = $name]/@value,'ABCDEF','abcdef')" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->

    <!-- {{{ dp:useAbsolutePaths() -->
    <!--
        dp:useAbsolutePaths()

        @todo define these automatically
    -->
    <func:function name="dp:useAbsolutePaths">
        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::useAbsolutePaths')" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:useBaseUrl() -->
    <!--
        dp:useBaseUrl()

        @todo define these automatically
    -->
    <func:function name="dp:useBaseUrl">
        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::useBaseUrl')" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:changesrc() -->
    <!--
        dp:changesrc(src)

        @todo define these automatically
    -->
    <func:function name="dp:changesrc">
        <xsl:param name="src" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::changesrc', string($src))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:nodetostring() -->
    <!--
        dp:nodetostring(src)
    -->
    <func:function name="dp:nodetostring">
        <xsl:param name="src" />

        <xsl:variable name="escaped">
            <xsl:apply-templates select="exslt:node-set($src)" mode="nodetostring" />
        </xsl:variable>

        <func:result select="$escaped" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:urlencode() -->
    <!--
        dp:urlencode(url)

        @todo define these automatically
    -->
    <func:function name="dp:urlencode">
        <xsl:param name="url" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::urlencode', string($url))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:replaceEmailChars() -->
    <!--
        dp:urlencode(url)

        @todo define these automatically
    -->
    <func:function name="dp:replaceEmailChars">
        <xsl:param name="email" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::replaceEmailChars', string($email))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:atomizeText() -->
    <!--
        dp:atomizeText(text)

        @todo define these automatically
    -->
    <func:function name="dp:atomizeText">
        <xsl:param name="text" />

        <xsl:variable name="spans" select="php:function('Depage\Cms\Xslt\FuncDelegate::atomizeText', string($text))" />

        <func:result select="$spans/*" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:isListCharacter() -->
    <!--
        dp:isListCharacter(text)
    -->
    <func:function name="dp:isListCharacter">
        <xsl:param name="text" />

        <xsl:variable name="character" select="normalize-space(substring($text, 1, 1))" />
        <xsl:variable name="translated" select="translate($character, '-*•–—', '-----')" />

        <func:result select="$translated = '-'" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:phpEscape() -->
    <!--
        dp:phpEscape(text)

        @todo define these automatically
    -->
    <func:function name="dp:phpEscape">
        <xsl:param name="string" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::phpEscape', string($string))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:formatDate() -->
    <!--
        dp:formatDate(date)

        @todo define these automatically
    -->
    <func:function name="dp:formatDate">
        <xsl:param name="date" select="'now'" />
        <xsl:param name="format" select="''" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::formatDate', string($date), string($format))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:fileinfo() -->
    <!--
        dp:fileinfo(libref)

    -->
    <func:function name="dp:fileinfo">
        <xsl:param name="path" />
        <xsl:param name="extended" select="true()" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::fileinfo', string($path), string($extended))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:pageVisible() -->
    <!--
        dp:pageVisible(node)

    -->
    <func:function name="dp:pageVisible">
        <xsl:param name="pageNode" />
        <xsl:param name="lang" select="$currentLang" />

        <func:result select="not($pageNode/@nav_hidden = 'true' or $pageNode/@*[local-name() = concat('nav_no_', $lang)] = 'true')" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:autokeywords() -->
    <!--
        dp:autokeywords(keywords, contentNode)
    -->
    <func:function name="dp:autokeywords">
        <xsl:param name="keywords" />
        <xsl:param name="contentNode" select="/" />
        <xsl:variable name="contentString">
            <xsl:for-each select="$contentNode//*[@lang = $currentLang]//text()">
                <xsl:value-of select="." /><xsl:text> </xsl:text>
            </xsl:for-each>
        </xsl:variable>

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::autokeywords', string($keywords), string($contentString))" />
    </func:function>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
