<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [ 
    <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" extension-element-prefixes="xsl rpc db proj pg sec edit backup ">
    <!-- {{{ edit:a -->
    <xsl:template match="edit:a" name="edit:a">
        <xsl:param name="href" select="@href"/>
        <xsl:param name="href_id" select="@href_id"/>
        <xsl:param name="pretext" select="@pretext"/>
        <xsl:param name="aptext" select="@aptext"/>
        <xsl:param name="content"/>
        <xsl:param name="class" select="@class"/>
        <xsl:param name="id" select="@id"/>
        <xsl:param name="onFocus" select="@onFocus"/>
        <xsl:param name="target" select="@target"/>
        <xsl:param name="onMouseOver" select="@onMouseOver"/>
        <xsl:param name="onMouseOut" select="@onMouseOut"/>
        <xsl:param name="lang" select="$tt_lang"/>

        <xsl:if test="name(../..) = 'sec:unordered_list' ">
            <xsl:text disable-output-escaping="yes">&lt;li&gt;</xsl:text>
        </xsl:if>

        <a>
            <!-- {{{ href -->
            <xsl:choose>
                <xsl:when test="$href and substring($href, 1, 8) = 'libref:/'">
                    <xsl:attribute name="href">
                        <xsl:value-of select="document($href)/." disable-output-escaping="yes"/>
                    </xsl:attribute>
                </xsl:when>
                <xsl:when test="@href and substring($href, 1, 7) = 'mailto:'">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$href" disable-output-escaping="yes"/>
                    </xsl:attribute>
                </xsl:when>
                <xsl:when test="$href and not(substring($href, 1, 8) = 'pageref:')">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$href" disable-output-escaping="yes"/>
                    </xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="href">
                        <xsl:value-of select="document(concat('pageref:/', $href_id, '/', $lang))/." disable-output-escaping="yes"/>
                    </xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <!-- }}} -->
            <!-- {{{ attributes -->
            <xsl:if test="$lang">
                <xsl:attribute name="hreflang"><xsl:value-of select="$lang"/></xsl:attribute>
            </xsl:if>
            <xsl:choose>
                <xsl:when test="$target != ''">
                    <xsl:attribute name="target"><xsl:value-of select="$target"/></xsl:attribute>
                </xsl:when>
                <xsl:when test="@href and (substring($href, 1, 5) = 'http:' or substring($href, 1, 6) = 'https:')">
                    <xsl:attribute name="target">_blank</xsl:attribute>
                </xsl:when>
            </xsl:choose>
            <xsl:if test="$class != ''">
                <xsl:attribute name="class"><xsl:value-of select="$class"/></xsl:attribute>
            </xsl:if>
            <xsl:if test="$id != ''">
                <xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute>
            </xsl:if>
            <xsl:if test="$onFocus != ''">
                <xsl:attribute name="onFocus"><xsl:value-of select="$onFocus"/></xsl:attribute>
            </xsl:if>
            <xsl:if test="$onMouseOver != ''">
                <xsl:attribute name="onMouseOver"><xsl:value-of select="$onMouseOver"/></xsl:attribute>
            </xsl:if>
            <xsl:if test="$onMouseOut != ''">
                <xsl:attribute name="onMouseOut"><xsl:value-of select="$onMouseOut"/></xsl:attribute>
            </xsl:if>
            <!-- }}} -->
            <!-- {{{ content -->
            <!-- get name from meta-information if link is ref to page_id -->
            <xsl:variable name="linkdesc">
                <xsl:if test="$href_id">
                    <xsl:value-of select="document(concat('get:page/', $href_id))//*/pg:meta/pg:linkdesc[@lang = $tt_lang]/@value"/>
                </xsl:if>
            </xsl:variable>

            <xsl:value-of select="$pretext"/>
                <xsl:choose>
                    <xsl:when test="$href_id and not($content) and not($linkdesc = '')">
                        <xsl:value-of select="$linkdesc"/>
                    </xsl:when>
                    <xsl:when test="$content != '' ">
                        <xsl:value-of select="$content"/>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:apply-templates/>
                    </xsl:otherwise>
                </xsl:choose>
            <xsl:value-of select="$aptext"/>
            <!-- }}} -->
        </a>
        <xsl:if test="name(../..) = 'sec:unordered_list' ">
            <xsl:text disable-output-escaping="yes">&lt;/li&gt;</xsl:text>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:img -->
    <xsl:template match="edit:img" name="edit:img">
        <xsl:param name="href" select="@href"/>
        <xsl:param name="href_id" select="@href_id"/>
        <xsl:param name="target" select="@target"/>
        <xsl:param name="onMouseOver" select="@onMouseOver"/>
        <xsl:param name="onMouseOut" select="@onMouseOut"/>
        <xsl:param name="onFocus" select="@onFocus"/>
        <xsl:param name="lang" select="$tt_lang"/>
        <xsl:param name="src" select="@src"/>
        <xsl:param name="width" select="@width"/>
        <xsl:param name="height" select="@height"/>
        <xsl:param name="border" select="@border"/>
        <xsl:param name="class" select="@class"/>
        <xsl:param name="id" select="@id"/>
        <xsl:param name="style" select="@style"/>
        <xsl:param name="alt" select="@alt"/>
        <xsl:param name="hspace" select="@hspace"/>
        <xsl:param name="vspace" select="@vspace"/>
        <xsl:param name="img_name" select="@img_name"/>

        <!-- {{{ plain image -->
        <xsl:if test="not($href or $href_id) or $href = ''">
            <img>
                <xsl:choose>
                    <xsl:when test="$src != ''">
                        <xsl:attribute name="src">
                            <xsl:value-of select="document($src)/."/>
                        </xsl:attribute>
                        <xsl:choose>
                            <xsl:when test="$width != ''"><xsl:attribute name="width"><xsl:value-of select="$width"/></xsl:attribute></xsl:when>
                            <xsl:otherwise><!--xsl:attribute name="width"><xsl:value-of select="document(concat('call:fileinfo/', $src))/file/@width"/></xsl:attribute--></xsl:otherwise>
                        </xsl:choose>
                        <xsl:choose>
                            <xsl:when test="$height != ''"><xsl:attribute name="height"><xsl:value-of select="$height"/></xsl:attribute></xsl:when>
                            <xsl:otherwise><!--xsl:attribute name="height"><xsl:value-of select="document(concat('call:fileinfo/', $src))/file/@height"/></xsl:attribute--></xsl:otherwise>
                        </xsl:choose>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:attribute name="src">
                            <xsl:value-of select="document('libref:/grfx/all/null.gif')/."/>
                        </xsl:attribute>
                        <xsl:choose>
                            <xsl:when test="$width != ''"><xsl:attribute name="width"><xsl:value-of select="$width"/></xsl:attribute></xsl:when>
                            <xsl:otherwise><xsl:attribute name="width">1</xsl:attribute></xsl:otherwise>
                        </xsl:choose>
                        <xsl:choose>
                            <xsl:when test="$height != ''"><xsl:attribute name="height"><xsl:value-of select="$height"/></xsl:attribute></xsl:when>
                            <xsl:otherwise><xsl:attribute name="height">1</xsl:attribute></xsl:otherwise>
                        </xsl:choose>
                    </xsl:otherwise>
                </xsl:choose>

                <xsl:if test="$border != ''"><xsl:attribute name="border"><xsl:value-of select="$border"/></xsl:attribute></xsl:if>
                <xsl:if test="$class != ''"><xsl:attribute name="class"><xsl:value-of select="$class"/></xsl:attribute></xsl:if>
                <xsl:if test="$style != ''"><xsl:attribute name="style"><xsl:value-of select="$style"/></xsl:attribute></xsl:if>
                <xsl:if test="$alt != ''"><xsl:attribute name="alt"><xsl:value-of select="$alt"/></xsl:attribute></xsl:if>
                <xsl:if test="$hspace != ''"><xsl:attribute name="hspace"><xsl:value-of select="$hspace"/></xsl:attribute></xsl:if>
                <xsl:if test="$vspace != ''"><xsl:attribute name="vspace"><xsl:value-of select="$vspace"/></xsl:attribute></xsl:if>
                <xsl:if test="$img_name != ''"><xsl:attribute name="name"><xsl:value-of select="$img_name"/></xsl:attribute></xsl:if>
            </img>
        </xsl:if>
        <!-- }}} -->
        <!-- {{{ image with link -->
        <xsl:if test="$href != '' or $href_id">
            <a>
                <!-- {{{ href -->
                <xsl:choose>
                    <xsl:when test="$href and substring($href, 1, 8) = 'libref:/'">
                        <xsl:attribute name="href">
                            <xsl:value-of select="document($href)/." disable-output-escaping="yes"/>
                        </xsl:attribute>
                    </xsl:when>
                    <xsl:when test="@href and substring($href, 1, 7) = 'mailto:'">
                        <xsl:attribute name="href">
                            <xsl:value-of select="$href" disable-output-escaping="yes"/>
                        </xsl:attribute>
                    </xsl:when>
                    <xsl:when test="$href and not(substring($href, 1, 8) = 'pageref:')">
                        <xsl:attribute name="href">
                            <xsl:value-of select="$href" disable-output-escaping="yes"/>
                        </xsl:attribute>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:attribute name="href">
                            <xsl:value-of select="document(concat('pageref:/', $href_id, '/', $lang))/." disable-output-escaping="yes"/>
                        </xsl:attribute>
                    </xsl:otherwise>
                </xsl:choose>
                <!-- }}} -->
                <!-- {{{ attributes -->
                <xsl:if test="$lang">
                    <xsl:attribute name="hreflang"><xsl:value-of select="$lang"/></xsl:attribute>
                </xsl:if>
                <xsl:choose>
                    <xsl:when test="$target != ''">
                        <xsl:attribute name="target"><xsl:value-of select="$target"/></xsl:attribute>
                    </xsl:when>
                    <xsl:when test="@href and (substring($href, 1, 5) = 'http:' or substring($href, 1, 6) = 'https:')">
                        <xsl:attribute name="target">_blank</xsl:attribute>
                    </xsl:when>
                </xsl:choose>
                <xsl:if test="$class != ''">
                    <xsl:attribute name="class"><xsl:value-of select="$class"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="$id != ''">
                    <xsl:attribute name="id"><xsl:value-of select="$id"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="$onFocus != ''">
                    <xsl:attribute name="onFocus"><xsl:value-of select="$onFocus"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="$onMouseOver != ''">
                    <xsl:attribute name="onMouseOver"><xsl:value-of select="$onMouseOver"/></xsl:attribute>
                </xsl:if>
                <xsl:if test="$onMouseOut != ''">
                    <xsl:attribute name="onMouseOut"><xsl:value-of select="$onMouseOut"/></xsl:attribute>
                </xsl:if>
                <!-- }}} -->
                <xsl:call-template name="edit:img">
                    <xsl:with-param name="href" select="''"/>
                    <xsl:with-param name="href_id" select="''"/>
                    <xsl:with-param name="target" select="''"/>
                    <xsl:with-param name="onMouseOver" select="''"/>
                    <xsl:with-param name="onMouseOut" select="''"/>
                    <xsl:with-param name="class" select="''"/>
                    <xsl:with-param name="id" select="''"/>
                    <xsl:with-param name="src" select="$src"/>
                    <xsl:with-param name="width" select="$width"/>
                    <xsl:with-param name="height" select="$height"/>
                    <xsl:with-param name="border" select="$border"/>
                    <xsl:with-param name="style" select="$style"/>
                    <xsl:with-param name="alt" select="$alt"/>
                    <xsl:with-param name="hspace" select="$hspace"/>
                    <xsl:with-param name="vspace" select="$vspace"/>
                    <xsl:with-param name="img_name" select="$img_name"/>
                </xsl:call-template>
            </a>
        </xsl:if>
        <!-- }}} -->
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ edit:text_formatted -->
    <xsl:template name="edit:text_formatted" match="edit:text_formatted">
        <xsl:param name="class" />
        <xsl:param name="id" />

        <xsl:if test="($tt_multilang = 'true' and @lang = $tt_lang) or $tt_multilang != 'true'">
            <xsl:apply-templates><xsl:with-param name="class" select="$class"/><xsl:with-param name="id" select="$id"/></xsl:apply-templates>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline -->
    <xsl:template match="edit:text_headline">
        <xsl:if test="($tt_multilang = 'true' and @lang = $tt_lang) or $tt_multilang != 'true'">
            <xsl:apply-templates><xsl:with-param name="class" select="'headline'"/></xsl:apply-templates>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ p -->
    <xsl:template match="p">
        <xsl:param name="class" />
        <xsl:param name="linebreaks" />

        <xsl:choose>
            <xsl:when test="$class != ''">
                <p class="{$class}"><xsl:apply-templates/>&nbsp;</p>
            </xsl:when>
            <xsl:when test="$linebreaks != ''">
                <xsl:apply-templates/><xsl:if test="position() != last()"><br /></xsl:if>
            </xsl:when>
            <xsl:otherwise>
                <p><xsl:apply-templates/>&nbsp;</p>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ b -->
    <xsl:template match="b"><b><xsl:apply-templates/></b></xsl:template>
    <!-- }}} -->
    <!-- {{{ i -->
    <xsl:template match="i"><i><xsl:apply-templates/></i></xsl:template>
    <!-- }}} -->
    <!-- {{{ small -->
    <xsl:template match="small"><small><xsl:apply-templates /></small></xsl:template>
    <!-- }}} -->
    <!-- {{{ br -->
    <xsl:template match="br"><br /></xsl:template>
    <!-- }}} -->
    <!-- {{{ a -->
    <xsl:template match="a">
        <xsl:choose>
            <xsl:when test="substring(@href,1,8) = 'pageref:'">
                <xsl:call-template name="edit:a">
                    <xsl:with-param name="href_id" select="substring(@href,9)" />
                    <xsl:with-param name="target" select="@target" />
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:call-template name="edit:a">
                    <xsl:with-param name="href" select="@href" />
                    <xsl:with-param name="target" select="@target" />
                </xsl:call-template>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ edit:date -->
    <xsl:template match="edit:date" name="edit:date">
        <xsl:param name="format" select=" 'short' "/>

        <time><xsl:attribute name="datetime"><xsl:value-of select="translate(edit:date/@value,'/','-')"/></xsl:attribute>
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

        <xsl:if test="$tt_lang = 'de' ">
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
        <xsl:if test="$tt_lang = 'en' ">
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

        <xsl:value-of select="$day"/>.<xsl:value-of select="$month"/>.<xsl:value-of select="$year"/>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ edit:plain_source -->
    <xsl:template match="edit:plain_source">
        <xsl:value-of select="document(concat('call:/changesrc/', string(.)))/*" disable-output-escaping="yes"/>
    </xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>

