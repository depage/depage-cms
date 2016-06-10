<?php
    $phost = parse_url("http://" . $_SERVER["HTTP_HOST"]);

    $params = [
        "nsrpc" => "rpc",
        "nsrpcuri" => "http://cms.depagecms.net/ns/rpc",
        "phost" => $phost['host'],
        "pport" => 19123,
        "puse" => "false",
        "standalone" => $this->standalone,
        "project" => $this->project,
        "page" => $this->page,
        "userid" => $this->sid,
    ];

    $params = http_build_query($params);

    $flashfile = "framework/Cms/lib/main.swf?" . $params;

    /*
?>
    <object type="application/x-shockwave-flash" width="100%" height="100%" id="flash" data="<?php self::t($flashfile) ?>" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; right: 0; bottom: 0;">
        <param name="movie" value="<?php self::t($flashfile) ?>" />
        <param name="AllowScriptAccess" value="always">
        <param name="quality" value="best" />
        <param name="bgcolor" value="#ffffff" />
        <param name="wmode" value="transparent" />
    </object>
<?php
     */

?>
    <script language="JavaScript" type="text/javascript">
    <!--
        document.write('<object type="application/x-shockwave-flash" width="100%" height="100%" id="flash" data="<?php self::t($flashfile) ?>" style="position: absolute; width: 100%; height: 100%; top: 0; left: 0; right: 0; bottom: 0;"><param name="movie" value="<?php self::t($flashfile) ?>" /><param name="AllowScriptAccess" value="always" /><param name="quality" value="best" /><param name="wmode" value="transparent" /><param name="bgcolor" value="#ffffff" /></object>');
    //-->
    </script>
    <noscript>
        Javascript must be active.
    </noscript>
<?php

    /* vim:set ft=php sw=4 sts=4 fdm=marker et : */
