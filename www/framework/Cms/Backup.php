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
    public function __construct($pdo, \Depage\Cms\Project $project)
    {
        $this->xmldb = $project->getXmlDb();
        $this->backupPath = "projects/{$project->name}/backups/";
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
        $this->xmldb->clearTables();
        $this->xmldb->updateSchema();

        $archive = new \ZipArchive();
        $archive->open($file);

        $documents = [];

        for ($i = 0; $i < $archive->numFiles; $i++) {
            preg_match("/xmldb\/d(\d+)\/_meta.xml/", $archive->statIndex($i)['name'], $matches);
            if (isset($matches[1])) {
                $documents[] = $matches[1];
            }
        }
        sort($documents);

        foreach ($documents as $docId) {
            $infoXml = new \SimpleXMLElement($archive->getFromName("xmldb/d{$docId}/_meta.xml"));
            $dataXml = new \Depage\Xml\Document();
            $dataXml->loadXml($archive->getFromName("xmldb/d{$docId}/data.xml"));

            $doc = $this->xmldb->createDoc($infoXml->type, (string) $infoXml->name);
            $doc->save($dataXml);

            // @todo restore history?
        }

        $archive->close();
    }
    // }}}
    // {{{ backupToFile()
    /**
     * @brief backupToFile
     *
     * @param string $file
     * @return void
     **/
    public function backupToFile($file, $saveHistory = false, $saveLib = false)
    {
        unlink($file);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        $archive = new \ZipArchive();
        $archive->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

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

            // @todo add history?
        }

        $archive->close();
    }
    // }}}
    // {{{ getBackupInfo()
    /**
     * @brief getBackupInfo
     *
     * @param mixed
     * @return void
     **/
    public function getBackupInfo($file)
    {
        $archive = new \ZipArchive();
        $archive->open($file);

        $files = [];
        $hashStr = "";

        for ($i = 0; $i < $archive->numFiles; $i++) {
            $info = $archive->statIndex($i);
            $files[] = $info['name'];

            // calculate hash from string to see if content has changed besided file modification times
            $hashStr .= $info['name'] . " " . dechex($info['crc']) . " " . dechex($info['size']) . " / ";
        }
        sort($files);

        $hash = sha1($hashStr);

        $archive->close();

        return [
            'files' => $files,
            'hash' => $hash,
        ];
    }
    // }}}
    // {{{ makeAutoBackup()
    /**
     * @brief makeAutoBackup
     *
     * @param mixed $path
     * @return void
     **/
    public function makeAutoBackup($path = null)
    {
        $path = $this->getBackupPath($path);

        $this->backupToFile("{$path}/backup_latest.zip");

        $info = $this->getBackupInfo("{$path}/backup_latest.zip");
        $files = glob("{$path}/backup_*_{$info['hash']}.zip");
        $date = date("YmdHis");

        if (count($files) == 0) {
            link("{$path}/backup_latest.zip", "{$path}/backup_{$date}_{$info['hash']}.zip");
        }
    }
    // }}}
    // {{{ getAutoBackups()
    /**
     * @brief getAutoBackups
     *
     * @param mixed $path
     * @return void
     **/
    public function getAutoBackups($path = null)
    {
        $backups = [];
        try {
            $path = $this->getBackupPath($path);

            $files = array_reverse(glob("{$path}/backup_*_*.zip"));

            foreach ($files as $file) {
                preg_match("/\/backup_(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})_([a-f0-9]+)\.zip/", $file, $m);
                $backups[] = (object) [
                    "file" => $file,
                    "date" => new \DateTime("{$m[1]}-{$m[2]}-{$m[3]} {$m[4]}:{$m[5]}:{$m[6]}"),
                    "hash" => $m[7],
                ];
            }
        } catch (\Exception $e) {
        }

        return $backups;
    }
    // }}}
    // {{{ trimAutoBackups()
    /**
     * @brief trimAutoBackups
     *
     * @param mixed $path = null
     * @return void
     **/
    public function trimAutoBackups($path = null)
    {
        $path = $this->getBackupPath($path);
    }
    // }}}
    // {{{ getBackupPath()
    /**
     * @brief getBackupPath
     *
     * @param mixed $path = null
     * @return void
     **/
    protected function getBackupPath($path = null)
    {
        if (is_null($path)) {
            $path = $this->backupPath;
        }
        $path = rtrim($path, "/");
        if (!is_dir($path)) {
            throw new \Exception("$path is not a directory");
        }
        if (!is_writable($path)) {
            throw new \Exception("$path is not a writable");
        }

        return $path;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
