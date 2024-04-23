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
    xmlns:str="http://exslt.org/strings"
    extension-element-prefixes="xsl dp func php exslt str pg ">

    <xsl:include href="xslt://nodetostring.xsl" />

    <xsl:key name="page-by-id" match="pg:*" use="@db:id"/>
    <xsl:key name="colorscheme-by-name" match="proj:colorscheme" use="@name"/>

    <!-- aliases -->
    <!-- {{{ dp:getpage() -->
    <!--
        dp:getpage(pageid)
    -->
    <func:function name="dp:getpage">
        <xsl:param name="pageid" />

        <func:result select="dp:getPage($pageid)" />
    </func:function>
    <!-- }}} -->

    <!-- functions -->
    <!-- {{{ dp:choose() -->
    <!--
        dp:choose(test, on-true, on-false)

    -->
    <func:function name="dp:choose">
        <xsl:param name="test"/>
        <xsl:param name="a" />
        <xsl:param name="b" />

        <xsl:choose>
            <xsl:when test="not($test = '') and not($test = false())">
                <func:result select="$a" />
            </xsl:when>
            <xsl:otherwise>
                <func:result select="$b" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:value() -->
    <!--
        dp:value(value, fallback)

    -->
    <func:function name="dp:value">
        <xsl:param name="a" />
        <xsl:param name="b" />

        <xsl:choose>
            <xsl:when test="$a and not($a = '') and not($a = false())">
                <func:result select="$a" />
            </xsl:when>
            <xsl:otherwise>
                <func:result select="$b" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:hasLangContent() -->
    <!--
        dp:hasLangContent($node, $lang)

    -->
    <func:function name="dp:hasLangContent">
        <xsl:param name="node" select="." />
        <xsl:param name="lang" select="$currentLang" />

        <xsl:choose>
            <xsl:when test="normalize-space(string($node)) != '' and $node/@lang = $lang">
                <func:result select="true()" />
            </xsl:when>
            <xsl:otherwise>
                <func:result select="false()" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->

    <!-- {{{ dp:getDocument() -->
    <!--
        dp:getDocument(pageid, xpath)
    -->
    <func:function name="dp:getDocument">
        <xsl:param name="docref" />
        <xsl:param name="xpath" select="''" />

        <func:result select="document(concat('xmldb://', $docref, '/', $xpath))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:getPageNode() -->
    <!--
        dp:getPageNode(pageid)
    -->
    <func:function name="dp:getPageNode">
        <xsl:param name="pageid" />
        <xsl:variable name="result" select="key('page-by-id',$pageid)" />

        <xsl:choose>
            <xsl:when test="not($result)">
                <xsl:variable name="navigation" select="document('xmldb://pages')"/>

                <xsl:for-each select="$navigation">
                    <func:result select="key('page-by-id',$pageid)" />
                </xsl:for-each>
            </xsl:when>
            <xsl:otherwise>
                <func:result select="$result" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:getPage() -->
    <!--
        dp:getPage(pageid, xpath)
    -->
    <func:function name="dp:getPage">
        <xsl:param name="pageid" />
        <xsl:param name="xpath" select="''" />
        <xsl:variable name="docref" select="dp:getPageNode($pageid)/@db:docref" />

        <xsl:choose>
            <xsl:when test="$docref = ''">
                <xsl:message terminate="no">dp:getPage unknown page id '<xsl:value-of select="$docref" />'</xsl:message>
                <error>dp:getPage unknown page id '<xsl:value-of select="$docref" />'</error>
            </xsl:when>
            <xsl:otherwise>
                <func:result select="dp:getDocument($docref, $xpath)" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:getMeta() -->
    <!--
        dp:getMeta(pageid)
    -->
    <func:function name="dp:getPageMeta">
        <xsl:param name="pageId" />

        <xsl:choose>
            <xsl:when test="$pageId">
                <func:result select="dp:getPage($pageId, '//pg:meta')/pg:meta" />
            </xsl:when>
            <xsl:otherwise>
                <func:result select="/dp:non-existent-node-to-automatically-return-empty-result" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:getPageRef() -->
    <!--
        dp:getPageRef(pageid)
    -->
    <func:function name="dp:getPageRef">
        <xsl:param name="pageid" />
        <xsl:param name="lang" select="$currentLang" />
        <xsl:param name="absolute" select="false()" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::getPageRef', string($pageid), string($lang), $absolute)" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:getLibRef() -->
    <!--
        dp:getLibRef(url)

    -->
    <func:function name="dp:getLibRef">
        <xsl:param name="url" />
        <xsl:param name="absolute" select="false()" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::getLibRef', string($url), $absolute)" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:getRef() -->
    <!--
        dp:getRef(pageid)

    -->
    <func:function name="dp:getRef">
        <xsl:param name="url" />
        <xsl:param name="lang" select="$currentLang" />
        <xsl:param name="absolute" select="false()" />
        <xsl:param name="hash" select="substring-after($url, '#')" />

        <xsl:choose>
            <xsl:when test="substring($url, 1, 8) = 'libid://' and substring-after($url, '.') != ''">
                <xsl:variable name="before" select="substring-before($url, '.')" />
                <xsl:variable name="after" select="substring-after($url, '.')" />

                <func:result select="concat(dp:getLibRef($before, $absolute), '.', $after)"/>
            </xsl:when>
            <xsl:when test="substring($url, 1, 8) = 'libid://'">
                <func:result select="dp:getLibRef($url, $absolute)"/>
            </xsl:when>
            <xsl:when test="substring($url, 1, 9) = 'libref://'">
                <func:result select="dp:getLibRef($url, $absolute)"/>
            </xsl:when>
            <xsl:when test="substring($url, 1, 10) = 'pageref://' and $hash = ''">
                <func:result select="dp:getPageRef(substring-after($url, 'pageref://'), $lang, $absolute)"/>
            </xsl:when>
            <xsl:when test="substring($url, 1, 10) = 'pageref://'">
                <func:result select="concat(dp:getPageRef(substring-before(substring-after($url, 'pageref://'), '#'), $lang, $absolute), '#', $hash)"/>
            </xsl:when>
            <xsl:when test="substring($url, 1, 7) = 'mailto:'">
                <func:result select="dp:replaceEmailChars($url)"/>
            </xsl:when>
            <xsl:otherwise>
                <func:result select="$url"/>
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

        <xsl:for-each select="$colors">
            <xsl:choose>
                <xsl:when test="key('colorscheme-by-name', $colorscheme)/color[@name = $name]">
                    <!-- color from named colorscheme -->
                    <func:result select="translate(key('colorscheme-by-name', $colorscheme)/color[@name = $name]/@value,'ABCDEF','abcdef')" />
                </xsl:when>
                <xsl:otherwise>
                    <!-- color from default colorscheme -->
                    <func:result select="translate(.//color[@name = $name]/@value,'ABCDEF','abcdef')" />
                </xsl:otherwise>
            </xsl:choose>
        </xsl:for-each>
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:transformDoc() -->
    <!--
        dp:transformDoc(pageid, xpath)
    -->
    <func:function name="dp:transformDoc">
        <xsl:param name="docId" />
        <xsl:param name="lang" select="$currentLang"/>
        <xsl:param name="subtype" select="''" />
        <xsl:variable name="docIdValue"><xsl:value-of select="$docId" /></xsl:variable>

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::transformDoc', $docIdValue, $lang, $subtype)" />
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
    <!-- {{{ dp:tolower() -->
    <!--
        dp:tolower(string)
    -->
    <func:function name="dp:tolower">
        <xsl:param name="string" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::tolower', string($string))" />
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
        <xsl:param name="arg" />

        <xsl:choose>
            <xsl:when test="$arg = true() and string($arg) = 'true'">
                <func:result select="'true'" />
            </xsl:when>
            <xsl:when test="$arg = false() and string($arg) = 'false'">
                <func:result select="'false'" />
            </xsl:when>
            <xsl:otherwise>
                <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::phpEscape', string($arg))" />
            </xsl:otherwise>
        </xsl:choose>

    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:jsEscape() -->
    <!--
        dp:jsEscape(text)

        @todo define these automatically
    -->
    <func:function name="dp:jsEscape">
        <xsl:param name="arg" />

        <xsl:choose>
            <xsl:when test="$arg = true() and string($arg) = 'true'">
                <func:result select="'true'" />
            </xsl:when>
            <xsl:when test="$arg = false() and string($arg) = 'false'">
                <func:result select="'false'" />
            </xsl:when>
            <xsl:otherwise>
                <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::jsEscape', string($arg))" />
            </xsl:otherwise>
        </xsl:choose>
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:cssEscape() -->
    <!--
        dp:cssEscape(text)

        @todo define these automatically
    -->
    <func:function name="dp:cssEscape">
        <xsl:param name="arg" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::cssEscape', string($arg))" />
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
    <!-- {{{ dp:urlinfo() -->
    <!--
        dp:urlinfo(url)

    -->
    <func:function name="dp:urlinfo">
        <xsl:param name="url" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::urlinfo', string($url))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:filesInFolder() -->
    <!--
        dp:filesInFolder(libref)

    -->
    <func:function name="dp:filesInFolder">
        <xsl:param name="folderId" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::filesInFolder', string($folderId))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:includeUnparsed() -->
    <!--
        dp:includeUnparsed(libref)

    -->
    <func:function name="dp:includeUnparsed">
        <xsl:param name="path" />

        <func:result select="php:function('Depage\Cms\Xslt\FuncDelegate::includeUnparsed', string($path))" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:pageVisible() -->
    <!--
        dp:pageVisible(node)

    -->
    <func:function name="dp:pageVisible">
        <xsl:param name="pageNode" />
        <xsl:param name="lang" select="$currentLang" />

        <func:result select="not($pageNode/@nav_hidden = 'true') and dp:pageHasLang($pageNode, $lang)" />
    </func:function>
    <!-- }}} -->
    <!-- {{{ dp:pageHasLang() -->
    <!--
        dp:pageHasLang(node, lang)

    -->
    <func:function name="dp:pageHasLang">
        <xsl:param name="pageNode" />
        <xsl:param name="lang" select="$currentLang" />

        <func:result select="not($pageNode/@*[local-name() = concat('nav_no_', $lang)] = 'true')" />
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
    <!-- {{{ dp:srcset() -->
    <!--
        dp:srcset(realSrc, sizes, ext, action, center)
    -->
    <func:function name="dp:srcset">
        <xsl:param name="realSrc" />
        <xsl:param name="sizes" select="''" />
        <xsl:param name="ext" select="'jpg'" />
        <xsl:param name="action" select="'tf'" />
        <xsl:param name="center" select="''" />

        <xsl:variable name="sizeNodes" select="str:tokenize($sizes, ',')" />

        <xsl:variable name="srcset">
            <xsl:for-each select="$sizeNodes">
                <xsl:if test="position() &gt; 1">, </xsl:if>
                <xsl:variable name="currentSize" select="substring-before(normalize-space(.), ' ')" />
                <xsl:variable name="mediaSize" select="substring-after(normalize-space(.), ' ')" />
                <xsl:value-of select="concat($realSrc, '.', $action, $currentSize, $center, '.', $ext, ' ', $mediaSize)" />
            </xsl:for-each>
        </xsl:variable>

        <func:result>
            <xsl:value-of select="normalize-space($srcset)" />
        </func:result>
    </func:function>
    <!-- }}} -->

    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
