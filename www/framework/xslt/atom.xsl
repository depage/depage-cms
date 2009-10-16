<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [ ]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://tool.untitled.net/ns/rpc" xmlns:db="http://tool.untitled.net/ns/database" xmlns:proj="http://tool.untitled.net/ns/project" xmlns:pg="http://tool.untitled.net/ns/page" xmlns:sec="http://tool.untitled.net/ns/section" xmlns:edit="http://tool.untitled.net/ns/edit" xmlns:backup="http://tool.untitled.net/ns/backup" version="1.0" extension-element-prefixes="xsl rpc db proj pg sec edit backup ">
    <!-- {{{ init-feed -->
    <xsl:template name="init-feed">
        require_once("<xsl:value-of select="$depage_path_server_root" /><xsl:value-of select="$depage_path_base" />framework/lib/lib_atom.php"); 

        $feed = new atom("<xsl:value-of select="document(concat('call:phpescape/',$baseurl))/php" />", "<xsl:value-of select="document(concat('call:phpescape/',$title))/php" />");

        $feed-<xsl:text disable-output-escaping="yes">&gt;</xsl:text>add_author("<xsl:value-of select="document(concat('call:phpescape/',$author))/php" />");
        $feed-<xsl:text disable-output-escaping="yes">&gt;</xsl:text>add_rights("<xsl:value-of select="document(concat('call:phpescape/',$rights))/php" />");
        $feed-<xsl:text disable-output-escaping="yes">&gt;</xsl:text>add_icon("<xsl:value-of select="document(concat('call:phpescape/',$logo))/php" />");
        $feed-<xsl:text disable-output-escaping="yes">&gt;</xsl:text>add_logo("<xsl:value-of select="document(concat('call:phpescape/',$favicon))/php" />");
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ output-feed -->
    <xsl:template name="output-feed">
        $feed-<xsl:text disable-output-escaping="yes">&gt;</xsl:text>header();
        echo($feed-<xsl:text disable-output-escaping="yes">&gt;</xsl:text>generate(<xsl:value-of select="$num_items" />));
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ entry -->
    <xsl:template name="entry">
        <xsl:param name="title" />
        <xsl:param name="text" />
        <xsl:param name="link" />
        <xsl:param name="date" />
        <xsl:param name="author" />

        $text = <xsl:text disable-output-escaping="yes">&lt;&lt;&lt;</xsl:text>EOT
<xsl:apply-templates select="$text" />
EOT;

        $feed-<xsl:text disable-output-escaping="yes">&gt;</xsl:text>add_entry(
            "<xsl:value-of select="document(concat('call:phpescape/',$title))/php" />", 
            <!--"<xsl:for-each select="$text"><xsl:apply-templates select="." /></xsl:for-each>",-->
            $text,
            "<xsl:value-of select="document(concat('call:phpescape/',$link))/php" />" 
            <xsl:if test="$date != ''">
                ,"<xsl:value-of select="document(concat('call:phpescape/',$date))/php" />" 
            </xsl:if>
            <xsl:if test="$author != ''">
                ,"<xsl:value-of select="document(concat('call:phpescape/',$author))/php" />"
            </xsl:if>
        );
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ pg:folder -->
    <xsl:template match="pg:folder">
        <xsl:if test="not(@nav_hidden = 'true')">
            <xsl:apply-templates />
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ pg:page -->
    <xsl:template match="pg:page">
        <xsl:if test="not(@nav_hidden = 'true')">
            <xsl:apply-templates select="document(concat('get:page','/',@db:id))//pg:page_data">
                <xsl:with-param name="url"><xsl:value-of select="$tt_lang" /><xsl:value-of select="@url" /></xsl:with-param>
            </xsl:apply-templates>
            <xsl:apply-templates />
        </xsl:if>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ edit:text_formatted -->
    <xsl:template match="edit:text_formatted">
        <xsl:apply-templates />
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline -->
    <xsl:template match="edit:text_headline">
        <h1>
            <xsl:for-each select="p">
                <xsl:apply-templates />
            </xsl:for-each>
        </h1>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:img -->
    <xsl:template match="edit:img">
        <img>
            <xsl:choose>
                <xsl:when test="@src != ''">
                    <xsl:attribute name="src">
                        <xsl:value-of select="$baseurl" />lib<xsl:value-of select="substring(@src,8)"/>
                    </xsl:attribute>
                    <xsl:attribute name="width"><xsl:value-of select="document(concat('call:fileinfo/', @src))/file/@width"/></xsl:attribute>
                    <!--xsl:attribute name="height"><xsl:value-of select="document(concat('call:fileinfo/', @src))/file/@height"/></xsl:attribute-->
                </xsl:when>
            </xsl:choose>
        </img>
        
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ p -->
    <xsl:template match="p">
        <p><xsl:apply-templates /></p>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ a -->
    <xsl:template match="a">
        <a>
            <xsl:choose>
                <xsl:when test="@href and substring(@href, 1, 8) = 'libref:/'">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseurl" />lib<xsl:value-of select="substring(@href,8)" disable-output-escaping="yes" />
                    </xsl:attribute>
                </xsl:when>
                <xsl:when test="@href and not(substring(@href, 1, 8) = 'pageref:')">
                    <xsl:attribute name="href">
                        <xsl:value-of select="@href" disable-output-escaping="yes" />
                    </xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseurl" /><xsl:value-of select="document(concat('pageref:/', @href_id, '/', $lang))/." disable-output-escaping="yes" />
                    </xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:apply-templates />
        </a>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ b -->
    <xsl:template match="b">
        <b><xsl:apply-templates /></b>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ i -->
    <xsl:template match="i">
        <i><xsl:apply-templates /></i>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ small -->
    <xsl:template match="small">
        <small><xsl:apply-templates /></small>
    </xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>

