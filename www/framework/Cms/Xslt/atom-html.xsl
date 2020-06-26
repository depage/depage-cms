<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet
    version="1.0"
    xmlns="http://www.w3.org/1999/xhtml"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:php="http://php.net/xsl"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:sec="http://cms.depagecms.net/ns/section"
    xmlns:edit="http://cms.depagecms.net/ns/edit"
    extension-element-prefixes="xsl db dp proj pg sec edit php ">

    <!-- {{{ edit:text_formatted -->
    <xsl:template match="edit:text_formatted">
        <xsl:if test="@lang = $currentLang">
            <xsl:apply-templates />
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline -->
    <xsl:template match="edit:text_headline">
        <xsl:if test="@lang = $currentLang and count(p) &gt; 0">
            <h1>
                <xsl:for-each select="p">
                    <xsl:apply-templates />
                </xsl:for-each>
            </h1>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:img -->
    <xsl:template match="edit:img">
        <xsl:if test="@src != ''">
            <img>
                <xsl:attribute name="src">
                    <xsl:value-of select="$baseUrl" />lib<xsl:value-of select="substring(@src,8)"/>
                </xsl:attribute>
                <xsl:attribute name="width"><xsl:value-of select="dp:fileinfo(@src)/file/@width"/></xsl:attribute>
                <xsl:attribute name="height"><xsl:value-of select="dp:fileinfo(@src)/file/@height"/></xsl:attribute>
            </img>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:a -->
    <xsl:template match="edit:a" name="edit:a">
        <xsl:param name="href" select="@href"/>
        <xsl:param name="href_id" select="@href_id"/>
        <xsl:param name="type" select="@type"/>
        <xsl:param name="rel" select="@rel"/>
        <xsl:param name="pretext" select="@pretext"/>
        <xsl:param name="aptext" select="@aptext"/>
        <xsl:param name="content"/>
        <xsl:param name="justapply" select="false()"/>
        <xsl:param name="redirect"/>
        <xsl:param name="altcontent"/>
        <xsl:param name="class" select="@class"/>
        <xsl:param name="id" select="@id"/>
        <xsl:param name="onFocus" select="@onFocus"/>
        <xsl:param name="target" select="@target"/>
        <xsl:param name="onMouseOver" select="@onMouseOver"/>
        <xsl:param name="onMouseOut" select="@onMouseOut"/>
        <xsl:param name="lang" select="$currentLang"/>

        <!-- get name from meta-information if link is ref to page_id -->
        <xsl:variable name="linkdesc"><xsl:if test="$href_id"><xsl:value-of select="dp:getpage($href_id)//*/pg:meta/pg:linkdesc[@lang = $lang]/@value"/></xsl:if></xsl:variable>
        <xsl:variable name="title"><xsl:if test="$href_id"><xsl:value-of select="dp:getpage($href_id)//*/pg:meta/pg:title[@lang = $lang]/@value"/></xsl:if></xsl:variable>

        <a>
            <!-- {{{ href -->
            <xsl:choose>
                <xsl:when test="$href and substring($href, 1, 8) = 'libref:/'">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseUrl" />lib<xsl:value-of select="substring(@href,8)" disable-output-escaping="yes" /><xsl:value-of select="$campaign" />
                    </xsl:attribute>
                </xsl:when>
                <xsl:when test="@href and substring($href, 1, 7) = 'mailto:'">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$href" disable-output-escaping="yes"/>
                    </xsl:attribute>
                </xsl:when>
                <xsl:when test="$href and substring($href, 1, 8) = 'pageref:'">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseUrl" /><xsl:value-of select="document(concat($href, '/', $lang))/." disable-output-escaping="yes"/><xsl:value-of select="$campaign" />
                    </xsl:attribute>
                </xsl:when>
                <xsl:when test="$href_id != ''">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseUrl" /><xsl:value-of select="document(concat('pageref://', $href_id, '/', $lang))/." disable-output-escaping="yes"/><xsl:value-of select="$campaign" />
                    </xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="href">
                        <xsl:value-of select="$href" disable-output-escaping="yes"/>
                    </xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <!-- }}} -->
            <!-- {{{ attributes -->
            <xsl:if test="$lang">
                <xsl:attribute name="hreflang"><xsl:value-of select="$lang"/></xsl:attribute>
            </xsl:if>
            <!-- }}} -->
            <!-- {{{ content -->
            <xsl:value-of select="$pretext" disable-output-escaping="yes" />
            <xsl:choose>
                <xsl:when test="$content != '' and not($justapply)">
                    <xsl:value-of select="$content"/>
                </xsl:when>
                <xsl:when test="$href_id and not($linkdesc = '') and not($justapply)">
                    <xsl:value-of select="$linkdesc"/>
                </xsl:when>
                <xsl:when test="$altcontent != '' and not($justapply)">
                    <xsl:value-of select="$altcontent"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:apply-templates/>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:value-of select="$aptext" disable-output-escaping="yes" />
            <!-- }}} -->
        </a>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ p -->
    <xsl:template match="p">
        <p><xsl:apply-templates /></p>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ a -->
    <xsl:template match="a">
        <xsl:choose>
            <xsl:when test="substring(@href,1,10) = 'pageref://'">
                <xsl:call-template name="edit:a">
                    <xsl:with-param name="justapply" select="true()" />
                    <xsl:with-param name="href_id" select="substring(@href,11)" />
                    <xsl:with-param name="target" select="@target" />
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:call-template name="edit:a">
                    <xsl:with-param name="justapply" select="true()" />
                    <xsl:with-param name="href" select="@href" />
                    <xsl:with-param name="target" select="@target" />
                </xsl:call-template>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- }}} -->
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
    <!-- {{{ edit:date -->
    <xsl:template match="edit:date">
        <time><xsl:attribute name="datetime"><xsl:value-of select="translate(@value,'/','-')"/></xsl:attribute>
            <xsl:call-template name="formatdateshort">
                <xsl:with-param name="date"><xsl:value-of select="@value"/></xsl:with-param>
            </xsl:call-template>
        </time>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:plain_source -->
    <xsl:template match="edit:plain_source">
        <pre><code>
        <xsl:value-of select="dp:changesrc(string(.))" disable-output-escaping="no"/>
        </code></pre>
    </xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
