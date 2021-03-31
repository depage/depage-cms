<?php
/**
 * @file    FileLibrary.php
 *
 * description
 *
 * copyright (c) 2021 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

/**
 * @brief FileLibrary
 * Class FileLibrary
 */
class FileLibrary
{
    /**
     * @brief pdo
     **/
    protected $pdo = null;

    /**
     * @brief root
     **/
    protected $project = null;

    /**
     * @brieftableFiles
     **/
    protected $tableFiles = "";

    /**
     * @brief rootPath
     **/
    protected $rootPath = "";

    /**
     * @brief idByPath
     **/
    protected $idByPath = [];

    /**
     * @brief path
     **/
    protected $pathById = [];

    // {{{ __construct()
    /**
     * @brief__construct
     *
     * @param mixed
     * @return void
     **/
    public function __construct($pdo, $project)
    {
        $this->pdo = $pdo;
        $this->project = $project;
        $this->tableFiles = "{$pdo->prefix}_proj_{$project->name}_library_files";

        $this->rootPath = $this->project->getProjectPath() . "lib/";
        $this->fs = \Depage\Fs\Fs::factory($this->rootPath);
        $this->mediainfo = new \Depage\Media\MediaInfo();
    }
    // }}}

    // {{{ syncLibraryTree()
    /**
     * @brief syncLibraryTree
     *
     * @param mixed
     * @return void
     **/
    public function syncLibraryTree($path)
    {
        $doc = $this->getFilesDoc();
        $xml = $doc->getXml();

        $this->syncFolder($doc, $xml->documentElement, "/");

        if (!empty($path)) {
            return $this->getFolderIdByPath($path);
        }

        return false;
    }
    // }}}
    // {{{ syncFolder()
    /**
     * @brief syncFolder
     *
     * @param mixed $path, $folderNode
     * @return void
     **/
    protected function syncFolder($doc, $folderNode, $path = "")
    {
        $pattern = trim($path . "/*", '/');
        $dirs = $this->fs->lsDir($pattern);
        array_walk($dirs, function(&$dir) {
            $dir = pathinfo($dir, \PATHINFO_FILENAME);

        });
        $dirsById = [];
        $nodesById = [];

        // check if folder exists
        foreach($folderNode->childNodes as $node) {
            $name = $node->getAttribute("name");
            $id = $doc->getNodeId($node);
            $index = array_search($name, $dirs);

            if ($index === false) {
                // folder does not exist anymore
                $doc->deleteNode($doc->getNodeId($node));
            } else {
                // folder exists
                array_splice($dirs, $index, 1);
                $dirsById[$id] = $name;
                $nodesById[$id] = $node;
            }
        }

        // add unindexed folders
        foreach($dirs as $dir) {
            $parentId = $doc->getNodeId($folderNode);
            $node = $folderNode->ownerDocument->createElementNS ("http://cms.depagecms.net/ns/project", "proj:folder");
            $id = $doc->addNode($node, $parentId, -1, $dir);
            $node->setAttribute("name", $dir);
            $node->setAttributeNS("http://cms.depagecms.net/ns/database", "db:id", $id);

            $dirsById[$id] = $dir;
            $nodesById[$id] = $node;

            $folderNode->appendChild($node);
        }

        // index next folder level
        foreach($dirsById as $id => $dir) {
            $this->syncFolder($doc, $nodesById[$id], $path . '/' . $dir);
        }
    }
    // }}}
    // {{{ syncFiles()
    /**
     * @brief syncFiles
     *
     * @param mixed $path
     * @return void
     **/
    public function syncFiles($path):int
    {
        $folderId = $this->getFolderIdByPath($path);
        $oldFiles = $this->getFilesInFolder($folderId);

        $files = $this->fs->lsFiles(trim($path . "/*", '/'));

        foreach ($files as $file) {
            $fpath = $this->rootPath . $file;
            $fname = basename($file);
            $fsize = filesize($fpath);
            $fdate = filemtime($fpath);
            if (!isset($oldFiles[$fname])) {
                $this->updateFileInfo(null, $folderId, $fname, $fpath);
            } else if ($oldFiles[$fname]->filesize != $fsize || $oldFiles[$fname]->lastmod->getTimestamp() != $fdate) {
                $this->updateFileInfo($oldFiles[$fname]->id, $folderId, $fname, $fpath);
            }
            if (isset($oldFiles[$fname])) {
                unset($oldFiles[$fname]);
            }
        }
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->tableFiles}
            WHERE id=:id"
        );
        foreach ($oldFiles as $file => $info) {
            $query->execute([
                'id' => $info->id,
            ]);
        }

        return $folderId;
    }
    // }}}
    // {{{ updateFileInfo()
    /**
     * @brief updateFileInfo
     *
     * @param mixed $id, $file
     * @return void
     **/
    protected function updateFileInfo($id, $folderId, $file, $fullpath)
    {
        $info = $this->mediainfo->getInfo($fullpath);
        if (!$info->exists) {
            //return false;
        }
        $query = $this->pdo->prepare(
            "INSERT INTO {$this->tableFiles}
            SET
                id=:id,
                folder=:folderId,
                filename=:file,
                mime=:mime,
                hash=:hash,
                filesize=:fsize,
                lastmod=:fdate,
                width=:width,
                height=:height,
                displayAspectRatio=:dar,
                duration=:duration,
                artist=:artist,
                title=:title,
                album=:album,
                copyright=:copyright,
                description=:description,
                keywords=:keywords
            ON DUPLICATE KEY UPDATE
                folder=VALUES(folder),
                filename=VALUES(filename),
                mime=VALUES(mime),
                hash=VALUES(hash),
                filesize=VALUES(filesize),
                lastmod=VALUES(lastmod),
                width=VALUES(width),
                height=VALUES(height),
                displayAspectRatio=VALUES(displayAspectRatio),
                duration=VALUES(duration),
                artist=VALUES(artist),
                title=VALUES(title),
                album=VALUES(album),
                copyright=VALUES(copyright),
                description=VALUES(description),
                keywords=VALUES(keywords)
            "
        );
        $f = new FileInfo();
        $f->id = $id;
        $f->folder = $folderId;
        $f->filename = $file;
        $f->mime = $info['mime'];
        $f->hash = hash_file("sha256", $fullpath);
        $f->filesize = $info['filesize'];
        $f->lastmod = $info['date'];
        $f->width = $info['width'] ?? null;
        $f->height = $info['height'] ?? null;
        $f->displayApsectRatio = $info['displayAspectRatio'] ?? null;
        $f->duration = $info['duration'] ?? null;
        $f->artist = $info['tag_artist'] ?? "";
        $f->album = $info['tag_album'] ?? "";
        $f->title = $info['tag_title'] ?? "";
        $f->copyright = $info['copyright'] ?? "";
        $f->description = $info['description'] ?? "";
        $f->keywords = $info['keywords'] ?? "";

        $query->execute([
            'id' => $f->id,
            'folderId' => $f->folder,
            'file' => $f->filename,
            'mime' => $f->mime,
            'hash' => $f->hash,
            'fsize' => $f->filesize,
            'fdate' => $f->lastmod->format('Y-m-d H:i:s'),
            'width' => $f->width,
            'height' => $f->height,
            'dar' => $f->displayAspectRatio,
            'duration' => $f->duration,
            'artist' => $f->artist,
            'album' => $f->album,
            'title' => $f->title,
            'copyright' => $f->copyright,
            'description' => $f->description,
            'keywords' => $f->keywords,
        ]);

        return $f;
    }
    // }}}

    // {{{ getFolderIdByPath()
    /**
     * @brief getFolderIdByPath
     *
     * @param mixed $path
     * @return void
     **/
    public function getFolderIdByPath(string $path):int
    {
        // cache results in instance
        if (isset($this->idByPath[$path])) {
            return $this->idByPath[$path];
        }

        $doc = $this->getFilesDoc();
        $xml = $doc->getXml();

        if (!empty($path)) {
            $path = trim($path, '/');
            $dirs = explode('/', $path);
            $xpath = new \DOMXPath($xml);
            $xpath->registerNamespace("proj", "http://cms.depagecms.net/ns/project");

            $query = "/proj:library";
            foreach ($dirs as $dir) {
                $query .= "/proj:folder[@name = '" . htmlentities($dir) . "']";
            }
            $query .= "/@db:id";

            $result = $xpath->evaluate($query);

            if ($result->length == 1) {
                $this->idByPath[$path] = $result->item(0)->nodeValue;
                return $this->idByPath[$path];
            }
        }

        return false;
    }
    // }}}
    // {{{ getPathByFolderId()
    /**
     * @brief getPathByFolderId
     *
     * @param mixed thByFo
     * @return void
     **/
    public function getPathByFolderId(int $id):string
    {
        // cache results in instance
        if (isset($this->pathById[$path])) {
            return $this->pathById[$path];
        }

        $doc = $this->getFilesDoc();
        $xml = $doc->getXml();

        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace("proj", "http://cms.depagecms.net/ns/project");
        $xpath->registerNamespace("db", "http://cms.depagecms.net/ns/database");

        $query = "//proj:folder[@db:id = '$id']/@url";

        $result = $xpath->evaluate($query);

        if ($result->length == 1) {
            $this->pathById[$path] = $result->item(0)->nodeValue;

            return $this->pathById[$path];
        }

        return false;
    }
    // }}}
    // {{{ getFileInfoByLibref()
    /**
     * @brief getFileInfoByLibref
     *
     * @param mixed $libref
     * @return void
     **/
    public function getFileInfoByLibref($libref)
    {
        $fullpath = str_replace("libref://", "", $libref);

        return $this->getFileInfoByPath($fullpath);
    }
    // }}}
    // {{{ getFileInfoByPath()
    /**
     * @brief getFileInfoByPath
     *
     * @param mixed
     * @return void
     **/
    public function getFileInfoByPath($fullpath)
    {
        $filename = basename($fullpath);
        $path = dirname($fullpath) . "/";
        $folderId = $this->getFolderIdByPath($path);

        $query = $this->pdo->prepare(
            "SELECT f.* FROM {$this->tableFiles} AS f
            WHERE
                folder=:folderId AND
                filename=:filename
            ORDER BY filename ASC"
        );

        $query->execute([
            'folderId' => $folderId,
            'filename' => $filename,
        ]);

        $info = $query->fetchObject("Depage\\Cms\\FileInfo");

        if (!$info) {
            $info = $this->updateFileInfo(null, $folderId, $filename, $this->rootPath . $fullpath);
        } else {
            $date = new \DateTime($info->lastmod);
            $info->lastmod = $date;
        }
        if ($info) {
            $info->ext = pathinfo($info->filename, \PATHINFO_EXTENSION);
            $info->fullname = trim($path . $info->filename, '/');
        }

        return $info;
    }
    // }}}
    // {{{ getFileInfoById()
    /**
     * @brief getFileInfoById
     *
     * @param mixed $
     * @return void
     **/
    public function getFileInfoById($id)
    {

    }
    // }}}
    // {{{ getFilesInFolder()
    /**
     * @brief getFilesInFolder
     *
     * @param mixed int $id
     * @return void
     **/
    public function getFilesInFolder(int $folderId):array
    {
        $files = [];
        $path = $this->getPathByFolderId($folderId);

        $query = $this->pdo->prepare(
            "SELECT f.* FROM {$this->tableFiles} AS f
            WHERE folder=:folderId
            ORDER BY filename ASC",
        [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);

        $query->execute([
            'folderId' => $folderId,
        ]);

        while ($file = $query->fetchObject("Depage\\Cms\\FileInfo")) {
            $date = new \DateTime($file->lastmod);
            $file->lastmod = $date;
            $file->ext = pathinfo($file->filename, \PATHINFO_EXTENSION);
            $file->fullname = trim($path . $file->filename, '/');

            $files[$file->filename] = $file;
        }

        return $files;
    }
    // }}}

    // {{{ getFilesDoc()
    /**
     * @brief getDoc
     *
     * @param mixed
     * @return void
     **/
    protected function getFilesDoc()
    {
        $xmldb = $this->project->getXmlDb();
        $doc = $xmldb->getDoc("files");
        if (!$doc) {
            $doc = $xmldb->createDoc('Depage\Cms\XmlDocTypes\Library', "files");

            $xml = new \Depage\Xml\Document();
            $xml->load(__DIR__ . "/../XmlDocTypes/LibraryXml/library.xml");

            $nodeId = $doc->save($xml);
        }

        return $doc;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
