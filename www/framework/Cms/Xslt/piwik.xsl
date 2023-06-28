<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
    <!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet
    version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:rpc="http://cms.depagecms.net/ns/rpc"
    xmlns:db="http://cms.depagecms.net/ns/database"
    xmlns:proj="http://cms.depagecms.net/ns/project"
    xmlns:pg="http://cms.depagecms.net/ns/page"
    xmlns:sec="http://cms.depagecms.net/ns/section"
    xmlns:edit="http://cms.depagecms.net/ns/edit"
    xmlns:backup="http://cms.depagecms.net/ns/backup"
    extension-element-prefixes="xsl rpc db proj pg sec edit backup ">

    <!-- {{{ Piwik -->
    <xsl:template name="piwik">
        <xsl:param name="url" select="'https://analytics.depage.net/'" />

        <xsl:if test="$var-pa-siteId != ''">
            <xsl:if test="not($depageIsLive)">
                <xsl:comment>Matomo is disabled in preview mode.</xsl:comment>
            </xsl:if>
            <script type="text/javascript">
                var _paq = _paq || [];
                <xsl:if test="$var-pa-Domain != ''">_gaq.push(['_setDomainName', '<xsl:value-of select="$var-pa-Domain" />']);</xsl:if>
                _paq.push(['trackPageView']);
                _paq.push(['enableLinkTracking']);
                _paq.push(['enableHeartBeatTimer']);
                <xsl:if test="$depageIsLive">
                (function() {
                    var u="<xsl:value-of select="$url" />";
                    _paq.push(['setTrackerUrl', u+'piwik.php']);
                    _paq.push(['setSiteId', '<xsl:value-of select="$var-pa-siteId" />']);
                    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
                    g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
                })();
                </xsl:if>
            </script>
            <xsl:if test="$depageIsLive">
                <noscript><p><img style="border:0;" alt="">
                    <xsl:attribute name="src"><xsl:value-of select="$url" />piwik.php?idsite=<xsl:value-of select="$var-pa-siteId" /></xsl:attribute>
                </img></p></noscript>
            </xsl:if>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>
