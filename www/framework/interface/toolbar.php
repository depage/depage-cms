<?php
/**
 * @file    home.php
 *
 * index file
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */
 
    define('IS_IN_CONTOOL', true);

    require_once('../lib/lib_global.php');
    require_once('lib_auth.php');
    require_once('lib_html.php');
    require_once('lib_files.php');
    require_once('lib_tpl_xslt.php');
    require_once('lib_pocket_server.php');
    require_once('lib_tasks.php');

    $project->user->auth_http();

    $html = new html();

    $html->head();
    $settings = $conf->getScheme($conf->interface_scheme); 
    ?>
    <body bgcolor="<?php echo($settings['color_face']); ?>">
        <div class="toolbar">
            <h2><a>depage::cms</a></h2>
            <p>
                <a id="button_home" href="javascript:top.go_home()" target="content">home</a>
                <a id="button_reload" href="javascript:top.content.location.reload()"><span><img src="<?php echo("{$conf->path_base}/framework/interface/pics/icon_reload.gif"); ?>"></span>reload</a>
                <a id="button_edit" href="javascript:top.edit_page()"><span><img src="<?php echo("{$conf->path_base}/framework/interface/pics/icon_edit.gif"); ?>"></span>edit page</a>
            </p>
            <p class="right">
                <a id="button_logout" href="javascript:top.logout()">logout</a>
            </p>
        </div>
    </body>
<?php
    $html->end();
?>
