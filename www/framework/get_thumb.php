<?php
/**
 * @file    get_thumb.php
 *
 * Get Thumbnail
 *
 * Gets thumbnail from file in project library.
 * Files were cached in db for faster access.
 *
 *
 * copyright (c) 2002-2009 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

// {{{ define and require
define('IS_IN_CONTOOL', true);

require_once('lib/lib_global.php');
require_once('lib_auth.php');
require_once('lib_project.php');
// }}}

    $project->user->auth_http();

// {{{ getImage()
/**
 * gets image object from file
 *
 * @param    $sourceImgPath (string) path to img
 * @param    $sourceImgType (int) type of image, using image
 *            type definitions of gd library
 *
 * @return    $sourceImg (gd_image_object) image as image object
 */ 
function getImage($sourceImgPath, $sourceImgType) {
    global $conf;
    
    ignore_user_abort();
    
    if ($sourceImgType == 1 && function_exists('imagecreatefromgif')) {
        //GIF
        $sourceImg = imagecreatefromgif($sourceImgPath);
    } else if ($sourceImgType == 2) {
        //JPEG
        $sourceImg = imagecreatefromjpeg($sourceImgPath);
    } else if ($sourceImgType == 3) {
        //PNG
        $sourceImg = imagecreatefrompng($sourceImgPath);
    } else if (($sourceImgType == 1 || $sourceImgType > 4) && $conf->path_imageMagick != "" && file_exists($conf->path_imageMagick . "/convert" . ($_ENV["OS"] == "Windows_NT" ? ".exe" : ""))) {
        $sourceImgTmpPath = tempnam("", "img");
        if ($sourceImgType == 1) {
            //GIF
            exec($conf->path_imageMagick . "/convert -geometry \"" . $conf->thumb_width . "x" . $conf->thumb_height . ">\" GIF:" . $sourceImgPath . "[0] PNG:" . $sourceImgTmpPath);
        } else if ($sourceImgType == 5) {
            //PSD
            exec($conf->path_imageMagick . "/convert -geometry \"" . $conf->thumb_width . "x" . $conf->thumb_height . ">\" PSD:" . $sourceImgPath . " PNG:" . $sourceImgTmpPath);
        } else if ($sourceImgType == 6) {
            //BMP
            exec($conf->path_imageMagick . "/convert -geometry \"" . $conf->thumb_width . "x" . $conf->thumb_height . ">\" BMP:" . $sourceImgPath . " PNG:" . $sourceImgTmpPath);
        } else if ($sourceImgType == 7 || $sourceImgType == 8) {
            //TIF
            exec($conf->path_imageMagick . "/convert -geometry \"" . $conf->thumb_width . "x" . $conf->thumb_height . ">\" TIFF:" . $sourceImgPath . " PNG:" . $sourceImgTmpPath);
        }
        if (file_exists($sourceImgTmpPath)) {
            $sourceImg = imagecreatefrompng($sourceImgTmpPath);
            unlink($sourceImgTmpPath);
        }
    }
    
    return $sourceImg;
}
// }}}
// {{{ imgMakeThumb()
/**
 * generates image thumbnail of given image
 *
 * @param    $sourceImgPath (string) path to source image
 *
 * @return    $thumb (string) image thumbnail in jpeg format
 */ 
function imgMakeThumb($sourceImgPath = '') {
    global $conf;
    
    $sourceImgInfo = @getimagesize($sourceImgPath);
    $sourceImgType = $sourceImgInfo[2];
    
    if ($sourceImgInfo == null || $sourceImgType == 4 || $sourceImgType > 8) {
        $sourceImgPath = $conf->thumb_nopreviewimage;
        $sourceImgInfo = @getimagesize($sourceImgPath);
        $sourceImgType = $sourceImgInfo[2];
    }

    $sourceImg = getImage($sourceImgPath, $sourceImgType);
    $sourceImgWidth = imagesx($sourceImg);
    $sourceImgHeight = imagesy($sourceImg);
    
    if ($sourceImgWidth > $conf->thumb_width || $sourceImgHeight > $conf->thumb_height) {
        if ($sourceImgWidth / $sourceImgHeight > $conf->thumb_width / $conf->thumb_height) {
            $destWidth = $conf->thumb_width;
            $destHeight = $conf->thumb_width * ($sourceImgHeight / $sourceImgWidth);
        } else {
            $destWidth = $conf->thumb_height * ($sourceImgWidth / $sourceImgHeight);
            $destHeight = $conf->thumb_height;
        }
    } else {
        $destWidth = $sourceImgWidth;
        $destHeight = $sourceImgHeight;
    }
    
    $destImg = imagecreatetruecolor($destWidth, $destHeight);
    
    $transLen = 8;
    $transColor = array();
    $transColor[0] = imagecolorallocate ($destImg, 255, 255, 255);
    $transColor[1] = imagecolorallocate ($destImg, 230, 230, 230);
    for ($i = 0; $i * $transLen < $destWidth; $i++) {
        for ($j = 0; $j * $transLen < $destHeight; $j++) {
            imagefilledrectangle($destImg, $i * $transLen, $j * $transLen, ($i + 1) * $transLen, ($j + 1) * $transLen, $transColor[$j % 2 == 0 ? $i % 2 : ($i % 2 == 0 ? 1 : 0)]);
        }
    }
    
    imagecopyresampled ($destImg, $sourceImg, 0, 0, 0, 0, $destWidth, $destHeight, $sourceImgWidth, $sourceImgHeight);
    
    ob_start();
    imagejpeg($destImg, '', $conf->thumb_quality);
    $thumb = ob_get_contents();
    ob_end_clean();
    
    return $thumb;
}
// }}}
// {{{ imgGetThumb()
/**
 * outputs thumbnail image or error swf-file if image does not
 * exists or cannot be read.
 *
 * @param    $project_name (string) name of project
 * @param    $sourceImgPath (string) path to sourceimage,
 *            relative to project file library
 * @param    $sourceImg (string) name of image
 */ 
function imgGetThumb($project_name = '', $sourceImgPath = '', $sourceImg = '') {
    global $conf, $project;
    global $xml_db;

    $project->_set_project($project_name);
    $db_table_mediathumbs = "{$conf->db_prefix}_{$project_name}_mediathumbs";
    $imageWholePath = $project->get_project_path($project_name) . '/lib' . $sourceImgPath . $sourceImg;
    
    if ($sourceImgPath != '' && $sourceImg != '' && file_exists($imageWholePath)) {
        if (($sourceImgInfo = @getimagesize($imageWholePath) != null) && $sourceImgInfo[2] < 9 && $sourceImgInfo[2] != 4) {
            $filesize = filesize($imageWholePath);
            $filemtime = filemtime($imageWholePath);
            
            $projectId = $xml_db->get_doc_id_by_name($project_name);
            
            $result = db_query(
                "SELECT * 
                FROM $db_table_mediathumbs 
                WHERE projectId=\"$projectId\" and path=\"" . mysql_real_escape_string($sourceImgPath) . "\" and filename=\"" . mysql_real_escape_string($sourceImg) . "\""
            );
            if (mysql_num_rows($result) > 0) {
                $row = mysql_fetch_assoc($result);
                if ($row['size'] != $filesize || $row['mtime'] != $filemtime) {
                    $thumb = imgMakeThumb($imageWholePath);
                    db_query(
                        "UPDATE $db_table_mediathumbs 
                        SET mtime=\"$filemtime\", size=\"$filesize\", thumb=\"" . mysql_real_escape_string($thumb) . "\" 
                        WHERE projectId=\"$projectId\" and path=\"" . mysql_real_escape_string($sourceImgPath) . "\" and filename=\"" . mysql_real_escape_string($sourceImg) . "\""
                    );
                    echo($thumb);
                } else {
                    echo($row['thumb']);
                }
            } else {
                $thumb = imgMakeThumb($imageWholePath);
                db_query(
                    "INSERT INTO $db_table_mediathumbs 
                    SET projectId=\"$projectId\", path=\"" . mysql_real_escape_string($sourceImgPath) . "\", filename=\"" . mysql_real_escape_string($sourceImg) . "\", mtime=\"$filemtime\", size=\"$filesize\", thumb=\"" . mysql_real_escape_string($thumb) . "\""
                );
                
                header ('Content-type: image/jpeg');
                echo($thumb);
            }
        } else {
            header('Content-type: application/x-shockwave-flash');
            echo(file_get_contents($conf->file_thumbs));
        }
    } else {
        header('Content-type: application/x-shockwave-flash');
        echo(file_get_contents($conf->file_notfound));
    }
}
// }}}

/**
 * main
 */ 
$sid = $_GET['sid'];
$wid = $_GET['wid'];
$sourceImgPath = $_GET['imagePath'];
$sourceImg = $_GET['image'];

set_time_limit(120);

$data = array();
$project_name = $project->user->get_project_by_sid($sid);

imgGetThumb($project_name, $sourceImgPath, $sourceImg);

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
?>
