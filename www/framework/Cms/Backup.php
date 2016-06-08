<?php

/**
 * @file    framework/Cms/Backup.php
 *
 * depage cms backup module
 *
 *
 * copyright (c) 2016 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

/**
 * brief Backup
 * Class Backup
 */
class Backup
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $
     * @return void
     **/
    public function __construct($pdo, $project)
    {
        $this->xmldb = $project->getXmlDb();
    }
    // }}}
    // {{{ restoreFromFile()
    /**
     * @brief restoreFromFile
     *
     * @param string $file
     * @return void
     **/
    public function restoreFromFile($file)
    {

    }
    // }}}
    // {{{ backupToFile()
    /**
     * @brief backupToFile
     *
     * @param string $file
     * @return void
     **/
    public function backupToFile($file)
    {
        $pharName = $file . ".phar";
        unlink($file);
        unlink($pharName);

        $archive = new \Phar($pharName);

        // added xmldb documents
        $documents = $this->xmldb->getDocuments();

        foreach ($documents as $doc) {
            $info = $doc->getDocInfo();

            $infoXml = new \SimpleXMLElement("<document></document>");
            foreach(get_object_vars($info) as $key => $val) {
                if (is_object($val)) {
                    $val = $val->format("r");
                }
                $infoXml->addChild($key, $val);
            }
            $archive->addFromString("xmldb/d{$info->id}/_meta.xml", $infoXml->saveXml());
            $archive->addFromString("xmldb/d{$info->id}/data.xml", $doc->getXml());
        }

        $archive->convertToData(\Phar::ZIP);

        unlink($pharName);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
