<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
    <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:rpc="http://cms.depagecms.net/ns/rpc"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:dp="http://cms.depagecms.net/ns/depage"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:sec="http://cms.depagecms.net/ns/section"
    xmlns:edit="http://cms.depagecms.net/ns/edit"
    xmlns:backup="http://cms.depagecms.net/ns/backup"
    extension-element-prefixes="xsl rpc db proj pg sec edit backup ">

    <!-- {{{ Analytics src -->
    <xsl:template name="analytics-config">
        <xsl:param name="matomoUrl" select="'https://analytics.depage.net/'" />
        <xsl:param name="privacyPolicyLink" select="''" />

        <script type="text/javascript">
            (function(w) { var c = w.depageAnalyticsConfig || {};
            c.depageIsLive = <xsl:value-of select="dp:jsEscape(boolean($depageIsLive = 'true'))" />;
            c.privacyPolicyLink = <xsl:value-of select="dp:jsEscape($privacyPolicyLink)" />;
            <xsl:if test="$var-pa-siteId != ''">
                c.matomo = { url: <xsl:value-of select="dp:jsEscape($matomoUrl)" />, siteId: <xsl:value-of select="dp:jsEscape($var-pa-siteId)" />, domain: <xsl:value-of select="dp:jsEscape($var-pa-Domain)" /> };
            </xsl:if>
            <xsl:if test="$var-ga-Account != ''">
                c.ga = { account: <xsl:value-of select="dp:jsEscape($var-ga-Account)" />, domain: <xsl:value-of select="dp:jsEscape($var-ga-Domain)" /> };
            </xsl:if>
            <xsl:if test="$var-pinterest-tagId != ''">
                c.pinterest = { tagId: <xsl:value-of select="dp:jsEscape($var-pinterest-tagId)" /> };
            </xsl:if>
            w.depageAnalyticsConfig = c; })(window);
        </script>
    </xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
