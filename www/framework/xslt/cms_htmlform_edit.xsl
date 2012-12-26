<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" xmlns:dpg="http://www.depagecms.net/ns/depage" extension-element-prefixes="xsl rpc db proj pg sec edit backup dpg">

<xsl:output method="html" indent="no" omit-xml-declaration="yes" />
<xsl:strip-space elements="*" />

<!-- {{{ root -->
<xsl:template match="/">
    <xsl:apply-templates select="//sec:intro" />
</xsl:template>
<!-- }}} -->
<!-- {{{ edit:text_headline -->
<xsl:template match="edit:text_headline">
    <xsl:call-template name="textarea" />
</xsl:template>
<!-- }}} -->
<!-- {{{ edit:text_formatted -->
<xsl:template match="edit:text_formatted">
    <xsl:call-template name="textarea" />
</xsl:template>
<!-- }}} -->
<!-- {{{ textarea -->
<xsl:template name="textarea">
    <xsl:processing-instruction name="php">
        $form = new \depage\htmlform\htmlform("xmledit_<xsl:value-of select="@db:id" />", array(
            'label' => "save",
        ));

        $form->addHtml("&lt;h1&gt;Textarea&lt;/h1&gt;");
        $form->addHidden("dbid", array(
            'defaultValue' => "<xsl:value-of select="@db:id" />",
        )); 
        $form->addRichtext("value", array(
            'defaultValue' => "<xsl:apply-templates select="*" />",
            'cols' => 80,
            'rows' => 10,
            'label' => "<xsl:value-of select="@lang" />",
            'stylesheet' => "framework/htmlform/lib/css/depage-richtext.css",
        )); 

        $forms[] = $form;
    ?</xsl:processing-instruction>
</xsl:template>
<!-- }}} -->

<!-- {{{ p -->
<xsl:template match="p">&lt;p&gt;<xsl:apply-templates />&lt;/p&gt;
</xsl:template>
<!-- }}} -->
<!-- {{{ h1 -->
<xsl:template match="h1">&lt;h1&gt;<xsl:apply-templates />&lt;/h1&gt;
</xsl:template>
<!-- }}} -->
<!-- {{{ h2 -->
<xsl:template match="h2">&lt;h2&gt;<xsl:apply-templates />&lt;/h2&gt;
</xsl:template>
<!-- }}} -->
<!-- {{{ ol -->
<xsl:template match="ol">&lt;ol&gt;
    <xsl:apply-templates />&lt;/ol&gt;
</xsl:template>
<!-- }}} -->
<!-- {{{ ul -->
<xsl:template match="ul">&lt;ul&gt;
    <xsl:apply-templates />&lt;/ul&gt;
</xsl:template>
<!-- }}} -->
<!-- {{{ li -->
<xsl:template match="li">&lt;li&gt;<xsl:apply-templates />&lt;/li&gt;
</xsl:template>
<!-- }}} -->

<!-- {{{ br -->
<xsl:template match="br">&lt;br/&gt;
</xsl:template>
<!-- }}} -->

<!-- {{{ b -->
<xsl:template match="b|strong">&lt;strong&gt;<xsl:apply-templates />&lt;/strong&gt;</xsl:template>
<!-- }}} -->
<!-- {{{ i -->
<xsl:template match="i|em">&lt;em&gt;<xsl:apply-templates />&lt;/em&gt;</xsl:template>
<!-- }}} -->
<!-- {{{ a -->
<xsl:template match="a">&lt;a <xsl:for-each select="@*"><xsl:if test="name() != 'db:id'"> <xsl:value-of select="name()" />=\"<xsl:value-of select="."/>\" </xsl:if></xsl:for-each>&gt;<xsl:apply-templates />&lt;/a&gt;</xsl:template>
<!-- }}} -->

<!-- vim:set ft=xslt sw=4 sts=4 fdm=marker et : -->
</xsl:stylesheet>
