<?php
/**
 * @file    lib_html.php
 *
 * HTML Output Library
 *
 * This file provides functions, which generates the HTML output
 * including styles and message-boxes
 *
 *
 * copyright (c) 2002-2008 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 *
 * $Id: lib_html.php,v 1.7 2004/05/26 14:49:05 jonas Exp $
 */

if (!function_exists('die_error')) require_once('lib_global.php');

/**
 * Defines functions, which may be called without initializing an
 * instance of htmlout. 
 */
class htmlout {
    /**
     * outputs unified stylesheet for html content
     *
     * @public
     */
    function echoStyleSheet() {
        ?>
            <style type="text/css">
                <!--
                * {
                    font-family : Tahoma, Verdana, Arial, Geneva, sans-serif;
                    font-size : 11px;
                    text-decoration : none;
                    margin-top : 0px;
                    margin-bottom : 0px;
                }
                .head {
                    font-weight : bold;
                    line-height : 15px;
                    color : #000000;
                    margin-top : 7px;
                    margin-bottom : 10px;
                }
                .normal {
                    line-height : 15px;
                    color : #000000;
                    margin-bottom : 10px;
                }
                h1 {
                    padding-top: 10px;
                }
                ul {
                    list-style: none;
                    padding-left: 10px;
                    text-indent: 0px;
                    padding-bottom: 10px;
                }
                li {
                    padding-top: 5px;
                    padding-bottom: 5px;
                }
                a {
                    color: #882200;
                }
                -->
            </style>
        <?php    
    }

    /**
     * outputs a transparent spacer image
     *
     * @public
     *
     * @param    $width (int) width of spacer, optional, default is 1
     * @param    $height (int) height of spacer, optional, default is 1
     */
    function echoNullImg($width = 1, $height = 1) {
        global $conf;
        
        echo("<img src=\"{$conf->path_base}/framework/interface/pics/null.gif\" width=\"$width\" height=\"$height\">");
    }

    /**
     * outputs a message in a centered box
     *
     * @public
     *
     * @param    $head (string) title of message
     * @param    $text (string) message text
     */
    function echoMsg($head, $text) {
        ?>
            <table width="100%" height="150">
                <tr>
                    <td align="center">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr> 
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td rowspan="5" width="10" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(10); ?></td>
                            <td height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td rowspan="5" width="10" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(10); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                          <tr> 
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                          <tr> 
                            <td width="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="500" height="50" align="left" valign="middle" bgcolor="#D4D0C8">
                                <p class="head"><?php echo($head) ?></p>
                                <p class="normal"><?php echo($text) ?></p>
                            </td>
                            <td width="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                          <tr> 
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                          <tr> 
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td height="1" bgcolor="#D4D0C8"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                            <td width="1" height="1"><?php htmlout::echoNullImg(); ?></td>
                          </tr>
                        </table>
                    </td>
                </tr>
            </table>
        <?php
    }
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
