<?php
/**
 * depage::cms
 * U P L O A D
 *
 * php-script:
 * (c) 2002-2007 Frank Hellenkamp [jonas@depagecms.net]
 */
    set_time_limit(200);
    ignore_user_abort();
    
    define("IS_IN_CONTOOL", true);
        
    require_once('../lib/lib_global.php');
    require_once('lib_html.php');
    require_once('lib_auth.php');
    require_once('lib_files.php');
    require_once('lib_tpl_xslt.php');
    require_once('lib_pocket_server.php');
        
    $settings = $conf->getScheme($conf->interface_scheme);
    $lang = $conf->getTexts($conf->interface_language, 'inhtml', false);
    
    $user = new ttUser();
    $data = array();
    
    if (isset($_GET['sid'])) {
        $sid = $_GET['sid'];
        $wid = $_GET['wid'];
        $path = $_GET['path'];
        $type = "choose";
    } else {
        $sid = $_POST['sid'];
        $wid = $_POST['wid'];
        $path = $_POST['path'];
        $type = "uploaded";
    }
    
    if (!($project_name = $user->is_valid_user($sid, $wid, $_SERVER['REMOTE_ADDR']))) {
        die_error("you are not allowed to to this!");
    }
    
    if ($type == "uploaded") {
        $file_access = fs::factory('local');
        for ($i = 0; $i < count($_FILES['file']['error']); $i++) {
            if ($_FILES['file']['error'][$i] == 0) {
                // @todo add error handling for files thar are still there
                $fname = tpl_engine_xslt::glp_encode($_FILES['file']['name'][$i]);
                $fpath = $project->get_project_path($project_name) . "/lib" . $path;
                move_uploaded_file($_FILES['file']['tmp_name'][$i], $fpath . $fname);
                $file_access->ch_mod($fpath . $fname);
            }
        }
        clearstatcache();
        
        tell_clients_to_update($project_name, $sid, 'fileProps', array($path));
        send_updates();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title><?php echo(str_replace("%app_name%", $conf->app_name, $lang["inhtml_dialog_upload_title"])); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <?php htmlout::echoStyleSheet(); ?>
    </head>
    <body bgcolor="<?php echo($settings['color_face']); ?>">            
        <table width="300" height="300" border="0">
            <tr height="20">
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr height="40">
                <td width="40" valign="top"><img src="pics/icon_upload.gif" width="40" height="40"></td>
                <td width="260" valign="top"><?php echo($lang["inhtml_dialog_upload_uploaded"]); ?></td>
            </tr>
        </table>
    </body>
        <script language="JavaScript" type="text/JavaScript">
        <!--
            window.setTimeout("self.close()", 3000);
            //self.close();
        //-->
        </script>
    </body>    
</html>    
<?php
    } else {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title><?php echo(str_replace("%app_name%", $conf->app_name, $lang["inhtml_dialog_upload_title"])); ?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <script language="JavaScript" type="text/JavaScript">
        <!--
            function add_file_chooser() {
                if (document.getElementById("file" + chooserNum).value != "" && chooserNum < 9) {
                    chooserNum++;
                    document.getElementById("chooser" + chooserNum).style.visibility = "visible";
                }
                setTimeout("add_file_chooser()", 200);
            }
            
            function add_first_chooser() {
                add_file_chooser();
            }
            
            chooserNum = 1;
        //-->
        </script>
        <?php htmlout::echoStyleSheet(); ?>
    </head>
    <body bgcolor="<?php echo($settings['color_face']); ?>" onLoad="add_first_chooser();">            
        <form action="upload.php" method="POST" name="file_upload" enctype="multipart/form-data">
            <table width="300" height="300" border="0">
                <tr height="20">
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr height="40">
                    <td width="40" valign="top"><img src="pics/icon_upload.gif" width="40" height="40"></td>
                    <td width="260" valign="top"><?php echo(str_replace("%path%", $path, $lang["inhtml_dialog_upload_text"])); echo($_GET['PATH']); ?></td>
                </tr>
                <tr>
                    <td height="200">&nbsp;</td>
                    <td valign="top">
                        <div id="chooser1" style="visibility:visible;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file1"></p>
                        </div>
                        <div id="chooser2" style="visibility:hidden;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file2"></p>
                        </div>
                        <div id="chooser3" style="visibility:hidden;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file3" onBlur="add_file_chooser()" onClick="add_sile_chooser"></p>
                        </div>
                        <div id="chooser4" style="visibility:hidden;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file4" onBlur="add_file_chooser()" onClick="add_sile_chooser"></p>
                        </div>
                        <div id="chooser5" style="visibility:hidden;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file5" onBlur="add_file_chooser()" onClick="add_sile_chooser"></p>
                        </div>
                        <div id="chooser6" style="visibility:hidden;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file6" onBlur="add_file_chooser()" onClick="add_sile_chooser"></p>
                        </div>
                        <div id="chooser7" style="visibility:hidden;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file7" onBlur="add_file_chooser()" onClick="add_sile_chooser"></p>
                        </div>
                        <div id="chooser8" style="visibility:hidden;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file8" onBlur="add_file_chooser()" onClick="add_sile_chooser"></p>
                        </div>
                        <div id="chooser9" style="visibility:hidden;">
                            <p style="margin-bottom:3 px"><input type="file" name="file[]" id="file9" onBlur="add_file_chooser()" onClick="add_sile_chooser"></p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td height="40">&nbsp;<input type="hidden" name="sid" value="<?php echo($sid); ?>"><input type="hidden" name="wid" value="<?php echo($wid); ?>"><input type="hidden" name="path" value="<?php echo($path); ?>"></input></td>
                    <td valign="top"><input type="submit" value="<?php echo($lang["inhtml_dialog_upload_button"]); ?>"></td>
                </tr>        
            </table>
        </form>
    </body>
</html>
<?php
    }                
?>
