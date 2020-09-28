<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:sec="http://cms.depagecms.net/ns/section"
    xmlns:edit="http://cms.depagecms.net/ns/edit"
    xmlns:exslt="http://exslt.org/common"
    extension-element-prefixes="xsl exslt db proj pg sec edit ">

    <xsl:import href="xslt://functions.xsl" />
    <xsl:key name="page-by-id" match="pg:*" use="@db:id"/>

    <!-- {{{ edit:a -->
    <xsl:template match="edit:a" name="edit:a">
        <xsl:param name="href" select="@href"/>
        <xsl:param name="href_id" select="@href_id"/>
        <xsl:param name="type" select="@type"/>
        <xsl:param name="rel" select="@rel"/>
        <xsl:param name="pretext" select="@pretext"/>
        <xsl:param name="aptext" select="@aptext"/>
        <xsl:param name="content" />
        <xsl:param name="justapply" select="false()"/>
        <xsl:param name="redirect"/>
        <xsl:param name="altcontent"/>
        <xsl:param name="class" select="@class"/>
        <xsl:param name="id" select="@id"/>
        <xsl:param name="target" select="@target"/>
        <xsl:param name="lang" select="$currentLang"/>

        <xsl:if test="@lang = $lang or not(@lang)">
            <!-- get name from meta-information if link is ref to page_id -->
            <xsl:variable name="pgmetaText">
                <xsl:if test="$href_id and $content = '' and not($justapply)">
                    <xsl:copy-of select="dp:getPage($href_id, '//pg:meta')/pg:meta" />
                </xsl:if>
            </xsl:variable>
            <xsl:variable name="pgmeta" select="exslt:node-set($pgmetaText)/pg:meta" />
            <xsl:variable name="linkdesc" select="dp:value(
                $pgmeta/pg:linkdesc[@lang = $lang]/@value,
                dp:getPageNode($href_id)/@name
            )" />
            <xsl:variable name="title" select="$pgmeta/pg:title[@lang = $lang]/@value" />

            <a>
                <!-- {{{ href -->
                <xsl:attribute name="href">
                    <xsl:choose>
                        <xsl:when test="$href_id != ''">
                            <xsl:value-of select="dp:getPageRef($href_id, $lang)" />
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="dp:getRef($href)" />
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:attribute>
                <!-- }}} -->
                <!-- {{{ attributes -->
                <xsl:if test="$lang">
                    <xsl:attribute name="hreflang"><xsl:value-of select="$lang"/></xsl:attribute>
                </xsl:if>
                <xsl:choose>
                    <xsl:when test="$target != ''">
                        <xsl:attribute name="target"><xsl:value-of select="$target"/></xsl:attribute>
                    </xsl:when>
                    <xsl:when test="@href and (substring($href, 1, 7) = 'http://' or substring($href, 1, 8) = 'https://')">
                        <xsl:attribute name="target">_blank</xsl:attribute>
                    </xsl:when>
                </xsl:choose>
                <xsl:if test="$class != '' or $redirect != ''">
                    <xsl:attribute name="class"><xsl:value-of select="$class"/><xsl:if test="$redirect != ''"> redirect</xsl:if></xsl:attribute>
                </xsl:if>
                <xsl:if test="$id != ''">
                    <xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="$type != ''">
                    <xsl:attribute name="type"><xsl:value-of select="$type"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="$rel != ''">
                    <xsl:attribute name="rel"><xsl:value-of select="$rel"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="$title != ''">
                    <xsl:attribute name="title"><xsl:value-of select="$title"/></xsl:attribute>
                </xsl:if>
                <!-- }}} -->
                <!-- {{{ content -->
                <xsl:value-of select="$pretext" disable-output-escaping="yes" />
                <xsl:choose>
                    <xsl:when test="$justapply">
                        <xsl:apply-templates />
                    </xsl:when>
                    <xsl:when test="$content != ''">
                        <xsl:value-of select="$content"/>
                    </xsl:when>
                    <xsl:when test="$href_id and not($linkdesc = '')">
                        <xsl:value-of select="$linkdesc"/>
                    </xsl:when>
                    <xsl:when test="$altcontent != ''">
                        <xsl:value-of select="$altcontent"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:apply-templates />
                    </xsl:otherwise>
                </xsl:choose>
                <xsl:value-of select="$aptext" disable-output-escaping="yes" />
                <!-- }}} -->
            </a>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:a href -->
    <xsl:template match="edit:a" mode="href">
        <xsl:param name="absolute" select="false()" />
        <xsl:param name="lang" select="$currentLang" />

        <xsl:choose>
            <xsl:when test="@href_id != ''">
                <xsl:value-of select="dp:getPageRef(@href_id, $lang, $absolute)" />
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="dp:getRef(@href, $absolute)" />
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:img -->
    <xsl:template match="edit:img" name="edit:img">
        <xsl:param name="href" select="@href"/>
        <xsl:param name="href_id" select="@href_id"/>
        <xsl:param name="type" select="@type"/>
        <xsl:param name="rel" select="@rel"/>
        <xsl:param name="target" select="@target"/>
        <xsl:param name="lang" select="$currentLang"/>
        <xsl:param name="src" select="@src"/>
        <xsl:param name="sizes" select="@sizes"/>
        <xsl:param name="srcset" select="@srcset"/>
        <xsl:param name="width" select="@width"/>
        <xsl:param name="height" select="@height"/>
        <xsl:param name="border" select="@border"/>
        <xsl:param name="class" select="@class"/>
        <xsl:param name="id" select="@id"/>
        <xsl:param name="style" select="@style"/>
        <xsl:param name="alt" select="@alt"/>
        <xsl:param name="title" select="@title"/>
        <xsl:param name="img_name" select="@img_name"/>
        <xsl:param name="loading" select="@loading"/>

        <xsl:choose>
            <!-- {{{ image with link -->
            <xsl:when test="($href and $href != '') or ($href_id and $href_id != '')">
                <!-- get name from meta-information if link is ref to page_id -->
                <xsl:variable name="linktitle"><xsl:if test="$href_id"><xsl:value-of select="dp:getPage($href_id, '//pg:meta')/pg:meta/pg:title[@lang = $lang]/@value"/></xsl:if></xsl:variable>

                <a>
                    <!-- {{{ href -->
                    <xsl:attribute name="href">
                        <xsl:choose>
                            <xsl:when test="$href_id != ''">
                                <xsl:value-of select="dp:getPageRef($href_id, $lang)" />
                            </xsl:when>
                            <xsl:otherwise>
                                <xsl:value-of select="dp:getRef($href)" />
                            </xsl:otherwise>
                        </xsl:choose>
                    </xsl:attribute>
                    <!-- }}} -->
                    <!-- {{{ attributes -->
                    <xsl:choose>
                        <xsl:when test="$target != ''">
                            <xsl:attribute name="target"><xsl:value-of select="$target"/></xsl:attribute>
                        </xsl:when>
                        <xsl:when test="@href and (substring($href, 1, 5) = 'http:' or substring($href, 1, 6) = 'https:')">
                            <xsl:attribute name="target">_blank</xsl:attribute>
                        </xsl:when>
                    </xsl:choose>
                    <xsl:if test="$lang"><xsl:attribute name="hreflang"><xsl:value-of select="$lang"/></xsl:attribute></xsl:if>
                    <xsl:if test="$type != ''"><xsl:attribute name="type"><xsl:value-of select="$type"/></xsl:attribute></xsl:if>
                    <xsl:if test="$rel != ''"><xsl:attribute name="rel"><xsl:value-of select="$rel"/></xsl:attribute></xsl:if>
                    <xsl:if test="$linktitle != ''"><xsl:attribute name="title"><xsl:value-of select="$linktitle"/></xsl:attribute></xsl:if>
                    <xsl:if test="$class != ''"><xsl:attribute name="class"><xsl:value-of select="$class"/></xsl:attribute></xsl:if>
                    <xsl:if test="$id != ''"><xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute></xsl:if>
                    <!-- }}} -->
                    <xsl:call-template name="edit:img">
                        <xsl:with-param name="href" select="''"/>
                        <xsl:with-param name="href_id" select="''"/>
                        <xsl:with-param name="target" select="''"/>
                        <xsl:with-param name="class" select="$class"/>
                        <xsl:with-param name="id" select="''"/>
                        <xsl:with-param name="src" select="$src"/>
                        <xsl:with-param name="sizes" select="$sizes"/>
                        <xsl:with-param name="srcset" select="$srcset"/>
                        <xsl:with-param name="width" select="$width"/>
                        <xsl:with-param name="height" select="$height"/>
                        <xsl:with-param name="border" select="$border"/>
                        <xsl:with-param name="style" select="$style"/>
                        <xsl:with-param name="alt" select="$alt"/>
                        <xsl:with-param name="title" select="$title"/>
                        <xsl:with-param name="img_name" select="$img_name"/>
                        <xsl:with-param name="loading" select="$loading"/>
                    </xsl:call-template>
                </a>
            </xsl:when>
            <!-- }}} -->
            <!-- {{{ plain image -->
            <xsl:when test="($src and $src != '') or ($srcset and $srcset != '') or ($alt and $alt != '')">
                <img>
                    <xsl:choose>
                        <xsl:when test="$src != '' and substring($src, 1, 8) = 'libref:/'">
                            <xsl:attribute name="src">
                                <xsl:value-of select="dp:getLibRef($src)"/>
                            </xsl:attribute>
                        </xsl:when>
                        <xsl:when test="$src != ''">
                            <xsl:attribute name="src">
                                <xsl:value-of select="$src"/>
                            </xsl:attribute>
                        </xsl:when>
                    </xsl:choose>

                    <xsl:attribute name="alt"><xsl:value-of select="$alt"/></xsl:attribute>
                    <xsl:if test="$srcset != ''"><xsl:attribute name="srcset"><xsl:value-of select="normalize-space($srcset)"/></xsl:attribute></xsl:if>
                    <xsl:if test="$sizes != ''"><xsl:attribute name="sizes"><xsl:value-of select="normalize-space($sizes)"/></xsl:attribute></xsl:if>
                    <xsl:if test="$border != ''"><xsl:attribute name="border"><xsl:value-of select="$border"/></xsl:attribute></xsl:if>
                    <xsl:if test="$class != ''"><xsl:attribute name="class"><xsl:value-of select="normalize-space($class)"/></xsl:attribute></xsl:if>
                    <xsl:if test="$id != ''"><xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute></xsl:if>
                    <xsl:if test="$style != ''"><xsl:attribute name="style"><xsl:value-of select="normalize-space($style)"/></xsl:attribute></xsl:if>
                    <xsl:if test="$title != ''"><xsl:attribute name="title"><xsl:value-of select="normalize-space($title)"/></xsl:attribute></xsl:if>
                    <xsl:if test="$img_name != ''"><xsl:attribute name="name"><xsl:value-of select="$img_name"/></xsl:attribute></xsl:if>
                    <xsl:if test="$width != ''"><xsl:attribute name="width"><xsl:value-of select="$width"/></xsl:attribute></xsl:if>
                    <xsl:if test="$height != ''"><xsl:attribute name="height"><xsl:value-of select="$height"/></xsl:attribute></xsl:if>
                    <xsl:if test="$loading != ''"><xsl:attribute name="loading"><xsl:value-of select="$loading"/></xsl:attribute></xsl:if>
                </img>
            </xsl:when>
            <xsl:otherwise>
            </xsl:otherwise>
            <!-- }}} -->
        </xsl:choose>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:img inline-svg -->
    <xsl:template match="edit:img" name="img-svg" mode="inline-svg">
        <xsl:param name="href" select="@href"/>
        <xsl:param name="href_id" select="@href_id"/>
        <xsl:param name="type" select="@type"/>
        <xsl:param name="rel" select="@rel"/>
        <xsl:param name="target" select="@target"/>
        <xsl:param name="lang" select="$currentLang"/>
        <xsl:param name="src" select="@src"/>
        <xsl:param name="sizes" select="@sizes"/>
        <xsl:param name="srcset" select="@srcset"/>
        <xsl:param name="width" select="@width"/>
        <xsl:param name="height" select="@height"/>
        <xsl:param name="border" select="@border"/>
        <xsl:param name="class" select="@class"/>
        <xsl:param name="id" select="@id"/>
        <xsl:param name="style" select="@style"/>
        <xsl:param name="alt" select="@alt"/>
        <xsl:param name="title" select="@title"/>
        <xsl:param name="img_name" select="@img_name"/>

        <xsl:choose>
            <!-- {{{ svg image -->
            <xsl:when test="starts-with($src, 'libref://') and '.svg' = substring($src, string-length($src) - string-length('.svg') + 1)">
                <xsl:variable name="svgFile" select="concat($libPath, substring($src, 9))" />
                <xsl:copy-of select="document($svgFile)/*" />
            </xsl:when>
            <!-- }}} -->
            <!-- {{{ other image -->
            <xsl:otherwise>
                <xsl:call-template name="edit:img">
                    <xsl:with-param name="href" select="$href"/>
                    <xsl:with-param name="href_id" select="$href_id"/>
                    <xsl:with-param name="target" select="$target"/>
                    <xsl:with-param name="class" select="$class"/>
                    <xsl:with-param name="id" select="$id"/>
                    <xsl:with-param name="src" select="$src"/>
                    <xsl:with-param name="sizes" select="$sizes"/>
                    <xsl:with-param name="srcset" select="$srcset"/>
                    <xsl:with-param name="width" select="$width"/>
                    <xsl:with-param name="height" select="$height"/>
                    <xsl:with-param name="border" select="$border"/>
                    <xsl:with-param name="style" select="$style"/>
                    <xsl:with-param name="alt" select="$alt"/>
                    <xsl:with-param name="title" select="$title"/>
                    <xsl:with-param name="img_name" select="$img_name"/>
                </xsl:call-template>
            </xsl:otherwise>
            <!-- }}} -->
        </xsl:choose>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ edit:text_formatted -->
    <xsl:template name="edit:text_formatted" match="edit:text_formatted">
        <xsl:param name="class" />
        <xsl:param name="id" />
        <xsl:param name="linebreaks" />

        <xsl:if test="dp:hasLangContent()">
            <xsl:apply-templates>
                <xsl:with-param name="class" select="$class"/>
                <xsl:with-param name="id" select="$id"/>
                <xsl:with-param name="linebreaks" select="$linebreaks"/>
            </xsl:apply-templates>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_formatted mode autolist-->
    <xsl:template match="edit:text_formatted" mode="autolist">
        <xsl:if test="dp:hasLangContent()">
            <xsl:apply-templates mode="autolist" />
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline -->
    <xsl:template match="edit:text_headline">
        <xsl:if test="dp:hasLangContent()">
            <xsl:apply-templates mode="linebreaks" />
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline h1 -->
    <xsl:template match="edit:text_headline" mode="h1">
        <xsl:if test="dp:hasLangContent()">
            <h1><xsl:apply-templates mode="linebreaks" /></h1>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline h2 -->
    <xsl:template match="edit:text_headline" mode="h2">
        <xsl:if test="dp:hasLangContent()">
            <h2><xsl:apply-templates mode="linebreaks" /></h2>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline h3 -->
    <xsl:template match="edit:text_headline" mode="h3">
        <xsl:if test="dp:hasLangContent()">
            <h3><xsl:apply-templates mode="linebreaks" /></h3>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline h4 -->
    <xsl:template match="edit:text_headline" mode="h4">
        <xsl:if test="dp:hasLangContent()">
            <h4><xsl:apply-templates mode="linebreaks" /></h4>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline h5 -->
    <xsl:template match="edit:text_headline" mode="h5">
        <xsl:if test="dp:hasLangContent()">
            <h5><xsl:apply-templates mode="linebreaks" /></h5>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline h6 -->
    <xsl:template match="edit:text_headline" mode="h6">
        <xsl:if test="dp:hasLangContent()">
            <h6><xsl:apply-templates mode="linebreaks" /></h6>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline p -->
    <xsl:template match="edit:text_headline" mode="p">
        <xsl:if test="dp:hasLangContent(">
            <p><xsl:apply-templates mode="linebreaks" /></p>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ p -->
    <xsl:template match="p">
        <xsl:param name="class" />
        <xsl:param name="linebreaks" />
        <xsl:variable name="nbsp"><xsl:if test="count(br[position() = last()]) = 0">&#160;</xsl:if></xsl:variable>

        <xsl:choose>
            <xsl:when test="$class != ''">
                <p class="{$class}"><xsl:apply-templates/><xsl:value-of select="$nbsp" /></p>
            </xsl:when>
            <xsl:when test="$linebreaks = 'true' or $linebreaks = true()">
                <xsl:apply-templates select="." mode="linebreaks" />
            </xsl:when>
            <xsl:otherwise>
                <p><xsl:apply-templates/><xsl:value-of select="$nbsp" /></p>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template match="p" mode="linebreaks">
        <xsl:apply-templates mode="linebreaks"/><xsl:if test="position() != last()"><xsl:text> </xsl:text><br /></xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ p mode autolist -->
    <xsl:template match="p" mode="autolist">
        <xsl:variable name="nbsp"><xsl:if test="count(br[position() = last()]) = 0">&#160;</xsl:if></xsl:variable>
        <xsl:choose>
            <xsl:when test="dp:isListCharacter(substring(., 1, 2))">
                <xsl:if test="position() = 1 or not(dp:isListCharacter(substring(preceding-sibling::*[1], 1, 2)))">
                    <xsl:text disable-output-escaping="yes">&lt;ul&gt;</xsl:text>
                </xsl:if>
                    <li><xsl:for-each select="child::node()">
                        <xsl:if test="position() = 1">
                            <xsl:value-of select="substring(., 3)" />
                        </xsl:if>
                        <xsl:if test="position() != 1">
                            <xsl:apply-templates select="." />
                        </xsl:if>
                    </xsl:for-each>&#160;</li>
                <xsl:if test="position() = last or not(dp:isListCharacter(substring(following-sibling::*[1], 1, 2)))">
                    <xsl:text disable-output-escaping="yes">&lt;/ul&gt;</xsl:text>
                </xsl:if>
            </xsl:when>
            <xsl:otherwise>
                <p><xsl:apply-templates/><xsl:value-of select="$nbsp" /></p>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ ul ol autolist-->
    <xsl:template match="ul | ol" mode="autolist">
        <xsl:apply-templates select="." />
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ b i strong em small ul ol li -->
    <xsl:template match="b | i | strong | em | small | ul | ol | li">
        <xsl:variable name="tagName" select="name()" />
        <xsl:element name="{$tagName}">
            <xsl:apply-templates />
        </xsl:element>
    </xsl:template>
    <xsl:template match="li/p">
        <xsl:apply-templates />
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ br -->
    <xsl:template match="br"><br /></xsl:template>
    <!-- }}} -->
    <!-- {{{ br mode linebreaks-->
    <xsl:template match="br" mode="linebreaks"></xsl:template>
    <!-- }}} -->
    <!-- {{{ a -->
    <xsl:template match="a">
        <xsl:call-template name="edit:a">
            <xsl:with-param name="justapply" select="true()" />
            <xsl:with-param name="href" select="@href" />
            <xsl:with-param name="target" select="@target" />
        </xsl:call-template>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ edit:date -->
    <xsl:template match="edit:date" name="edit:date">
        <xsl:param name="format" select=" 'short' "/>

        <time><xsl:attribute name="datetime"><xsl:value-of select="translate(@value,'/','-')"/></xsl:attribute>
            <xsl:choose>
                <xsl:when test="$format = 'long' ">
                    <xsl:call-template name="formatdatelong">
                        <xsl:with-param name="date"><xsl:value-of select="@value"/></xsl:with-param>
                    </xsl:call-template>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:call-template name="formatdateshort">
                        <xsl:with-param name="date"><xsl:value-of select="@value"/></xsl:with-param>
                    </xsl:call-template>
                </xsl:otherwise>
            </xsl:choose>
        </time>
    </xsl:template>

    <xsl:template name="formatdatelong">
        <xsl:param name="date"/>

        <xsl:variable name="year"><xsl:value-of select="substring($date,1,4)"/></xsl:variable>
        <xsl:variable name="month"><xsl:value-of select="substring($date,6,2)"/></xsl:variable>
        <xsl:variable name="day"><xsl:value-of select="substring($date,9,2)"/></xsl:variable>

        <xsl:value-of select="$day"/>.
        <xsl:text> </xsl:text>

        <xsl:if test="$currentLang = 'de' ">
            <xsl:if test="$month = '01' ">Januar</xsl:if>
            <xsl:if test="$month = '02' ">Februar</xsl:if>
            <xsl:if test="$month = '03' ">März</xsl:if>
            <xsl:if test="$month = '04' ">April</xsl:if>
            <xsl:if test="$month = '05' ">Mai</xsl:if>
            <xsl:if test="$month = '06' ">Juni</xsl:if>
            <xsl:if test="$month = '07' ">Juli</xsl:if>
            <xsl:if test="$month = '08' ">August</xsl:if>
            <xsl:if test="$month = '09' ">September</xsl:if>
            <xsl:if test="$month = '10' ">Oktober</xsl:if>
            <xsl:if test="$month = '11' ">November</xsl:if>
            <xsl:if test="$month = '12' ">Dezember</xsl:if>
        </xsl:if>
        <xsl:if test="$currentLang = 'en' ">
            <xsl:if test="$month = '01' ">January</xsl:if>
            <xsl:if test="$month = '02' ">February</xsl:if>
            <xsl:if test="$month = '03' ">March</xsl:if>
            <xsl:if test="$month = '04' ">April</xsl:if>
            <xsl:if test="$month = '05' ">May</xsl:if>
            <xsl:if test="$month = '06' ">June</xsl:if>
            <xsl:if test="$month = '07' ">July</xsl:if>
            <xsl:if test="$month = '08' ">August</xsl:if>
            <xsl:if test="$month = '09' ">September</xsl:if>
            <xsl:if test="$month = '10' ">October</xsl:if>
            <xsl:if test="$month = '11' ">November</xsl:if>
            <xsl:if test="$month = '12' ">December</xsl:if>
        </xsl:if>
        <xsl:text> </xsl:text>
        <xsl:value-of select="$year"/>
    </xsl:template>

    <xsl:template name="formatdateshort">
        <xsl:param name="date"/>

        <xsl:variable name="year"><xsl:value-of select="substring($date,1,4)"/></xsl:variable>
        <xsl:variable name="month"><xsl:value-of select="substring($date,6,2)"/></xsl:variable>
        <xsl:variable name="day"><xsl:value-of select="substring($date,9,2)"/></xsl:variable>

        <xsl:if test="$currentLang = 'de' ">
            <xsl:value-of select="$day"/>.<xsl:value-of select="$month"/>.<xsl:value-of select="$year"/>
        </xsl:if>
        <xsl:if test="$currentLang = 'en' ">
            <xsl:value-of select="$day"/>/<xsl:value-of select="$month"/>/<xsl:value-of select="$year"/>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ edit:plain_source -->
    <xsl:template match="edit:plain_source">
        <xsl:value-of select="dp:changesrc(string(.))" disable-output-escaping="yes"/>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ sec:redirect -->
    <xsl:template match="sec:redirect">
        <xsl:variable name="url">
            <xsl:apply-templates select="edit:a[@lang = $currentLang]" mode="href">
                <xsl:with-param name="absolute" select="true()" />
            </xsl:apply-templates>
        </xsl:variable>
        <xsl:processing-instruction name="php">
            @header("Location: <xsl:value-of select="$url" />");
        ?</xsl:processing-instruction>
        die();
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ PHP Redirect -->
    <xsl:template name="php_redirect">
        <xsl:if test="$currentPage/@redirect = 'true'">
            @header("Location: <xsl:for-each select="//sec:redirect/edit:a[@lang = $currentLang]">
                <xsl:choose>
                    <xsl:when test="@href_id != ''">
                        <xsl:value-of select="dp:getPageRef(@href_id, $currentLang, true())" />
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:value-of select="dp:getRef(@href)" />
                    </xsl:otherwise>
                </xsl:choose>
            </xsl:for-each>");
            die();
        </xsl:if>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ header alternate languages -->
    <xsl:template name="header_alternate_lang">
        <xsl:variable name="pgmeta" select="/pg:page_data/pg:meta" />

        <xsl:for-each select="$settings/proj:settings/proj:languages/proj:language">
            <xsl:variable name="lang"><xsl:value-of select="@shortname" /></xsl:variable>

            <xsl:if test="$lang != $currentLang and dp:pageVisible($currentPage, $lang)">
                <xsl:variable name="linkdesc" select="dp:value(
                    $pgmeta/pg:linkdesc[@lang = $lang]/@value,
                    $currentPage/@name
                )"/>
                <xsl:variable name="title" select="$pgmeta/pg:title[@lang = $lang]/@value"/>

                <link rel="alternate">
                    <xsl:attribute name="href">
                        <xsl:value-of select="dp:getPageRef($currentPageId, $lang)"/>
                    </xsl:attribute>
                    <xsl:attribute name="hreflang"><xsl:value-of select="$lang" /></xsl:attribute>
                    <xsl:attribute name="title">
                        <xsl:value-of select="@name" />
                        <xsl:if test="$linkdesc != ''"> . <xsl:value-of select="$linkdesc" /></xsl:if>
                        <xsl:if test="$title != ''"> . <xsl:value-of select="$title" /></xsl:if>
                    </xsl:attribute>
                </link>
            </xsl:if>
        </xsl:for-each>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ header include css -->
    <xsl:template name="header_include_css">
        <xsl:param name="file" />
        <xsl:param name="media" select="''" />

        <xsl:variable name="fileref" select="concat('libref://', $file)" />
        <xsl:variable name="date" select="translate(dp:fileinfo($fileref, false())/file/@date,'/:- ','')" />

        <link rel="stylesheet" type="text/css"><xsl:if test="$media != ''"><xsl:attribute name="media"><xsl:value-of select="$media" /></xsl:attribute></xsl:if><xsl:attribute name="href"><xsl:value-of select="dp:getLibRef($fileref)"/>?<xsl:value-of select="$date" /></xsl:attribute></link>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ header include js -->
    <xsl:template name="header_include_js">
        <xsl:param name="file" />
        <xsl:param name="defer" select="true()" />

        <xsl:variable name="fileref" select="concat('libref://', $file)" />
        <xsl:variable name="date" select="translate(dp:fileinfo($fileref, false())/file/@date,'/:- ','')" />

        <script type="text/javascript"><xsl:if test="$defer = true()"><xsl:attribute name="defer"></xsl:attribute></xsl:if><xsl:attribute name="src"><xsl:value-of select="dp:getLibRef($fileref)"/>?<xsl:value-of select="$date" /></xsl:attribute></script>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ header include baseurl -->
    <xsl:template name="header_include_baseurl">
        <xsl:variable name="call" select="dp:useBaseUrl()" />

        <base href="{$baseUrl}" />
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ highlight -->
    <xsl:template match="@db:id" mode="highlight">
        <xsl:if test="not($depageIsLive)">
            <xsl:attribute name="data-db-id"><xsl:value-of select="." /></xsl:attribute>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ sec:vcard/sec:a -->
    <xsl:template match="sec:vcard/sec:a">
        <p>
            <xsl:apply-templates select="*" />
        </p>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ sec:unordered_list/sec:a -->
    <xsl:template match="sec:unordered_list/sec:a">
        <li><span></span>
            <xsl:apply-templates select="*" />
        </li>
    </xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xslt sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
