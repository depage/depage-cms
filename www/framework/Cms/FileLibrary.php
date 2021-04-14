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

    // {{{ syncLibrary()
    /**
     * @brief syncLibrary
     *
     * @param mixed
     * @return void
     **/
    public function syncLibrary()
    {
        $this->syncLibraryTree();
        $dirs = $this->fs->lsDir("*");

        while (count($dirs) > 0) {
            $dir = array_pop($dirs);
            $folderId = $this->syncFiles($dir);

            $dirs = array_merge($dirs, $this->fs->lsDir($dir . "/*"));
        }

        return true;
    }
    // }}}
    // {{{ syncLibraryTree()
    /**
     * @brief syncLibraryTree
     *
     * @param mixed
     * @return void
     **/
    public function syncLibraryTree($path = "")
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
        if (!$info['exists']) {
            return false;
        }
        $path = $this->getPathByFolderId($folderId);
        $pathKeywords = trim(str_replace(["/", " ", "-", "_"], ",", $path), ",");

        $query = $this->pdo->prepare(
            "INSERT INTO {$this->tableFiles}
            SET
                id=:id,
                folder=:folderId,
                filename=:filename,
                filenamehash=:filenamehash,
                mime=:mime,
                hash=:hash,
                filesize=:fsize,
                lastmod=:fdate,
                width=:width,
                height=:height,
                centerX=:centerX,
                centerY=:centerY,
                displayAspectRatio=:dar,
                duration=:duration,
                artist=:artist,
                title=:title,
                album=:album,
                copyright=:copyright,
                description=:description,
                keywords=:keywords,
                customKeywords=:customKeywords
            ON DUPLICATE KEY UPDATE
                folder=VALUES(folder),
                filename=VALUES(filename),
                filenamehash=VALUES(filenamehash),
                mime=VALUES(mime),
                hash=VALUES(hash),
                filesize=VALUES(filesize),
                lastmod=VALUES(lastmod),
                width=VALUES(width),
                height=VALUES(height),
                centerX=VALUES(centerX),
                centerY=VALUES(centerY),
                displayAspectRatio=VALUES(displayAspectRatio),
                duration=VALUES(duration),
                artist=VALUES(artist),
                title=VALUES(title),
                album=VALUES(album),
                copyright=VALUES(copyright),
                description=VALUES(description),
                keywords=VALUES(keywords),
                customKeywords=VALUES(customKeywords)
            "
        );
        $f = new FileInfo();
        $f->id = $id;
        $f->folder = $folderId;
        $f->filename = $file;
        $f->filenamehash = hash("sha1", $file);
        $f->mime = $info['mime'];
        $f->hash = hash_file("sha256", $fullpath);
        $f->filesize = $info['filesize'];
        $f->lastmod = $info['date'];
        $f->width = $info['width'] ?? null;
        $f->height = $info['height'] ?? null;
        $f->centerX = !empty($info['width']) ? 50 : null;
        $f->centerY = !empty($info['height']) ? 50 : null;
        $f->displayAspectRatio = $info['displayAspectRatio'] ?? null;
        $f->duration = $info['duration'] ?? null;
        $f->artist = $info['tag_artist'] ?? "";
        $f->album = $info['tag_album'] ?? "";
        $f->title = $info['tag_title'] ?? "";
        $f->copyright = $info['copyright'] ?? "";
        $f->description = $info['description'] ?? "";
        $f->keywords = $info['keywords'] ?? "";
        $f->customKeywords = $pathKeywords;

        $query->execute([
            'id' => $f->id,
            'folderId' => $f->folder,
            'filename' => $f->filename,
            'filenamehash' => $f->filenamehash,
            'mime' => $f->mime,
            'hash' => $f->hash,
            'fsize' => $f->filesize,
            'fdate' => $f->lastmod->format('Y-m-d H:i:s'),
            'width' => $f->width,
            'height' => $f->height,
            'centerX' => $f->centerX,
            'centerY' => $f->centerY,
            'dar' => $f->displayAspectRatio,
            'duration' => $f->duration,
            'artist' => $f->artist,
            'album' => $f->album,
            'title' => $f->title,
            'copyright' => $f->copyright,
            'description' => $f->description,
            'keywords' => $f->keywords,
            'customKeywords' => $f->customKeywords,
        ]);
        if (is_null($id)) {
            $f->id = $this->pdo->lastInsertId();
        }

        $f->init($path);

        return $f;
    }
    // }}}
    // {{{ deleteDataForFolder()
    /**
     * @brief deleteDataForFolder
     *
     * @param mixed $folderId
     * @return void
     **/
    public function deleteDataForFolder(int $folderId)
    {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->tableFiles}
            WHERE folder=:folderId"
        );

        $query->execute([
            'folderId' => $folderId,
        ]);
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

        if (empty($path)) {
            return false;
        }

        $path = trim($path, '/');
        $dirs = explode('/', $path);
        $xpath = new \DOMXPath($xml);

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
        if (isset($this->pathById[$id])) {
            return $this->pathById[$id];
        }

        $doc = $this->getFilesDoc();
        $xml = $doc->getXml();

        $xpath = new \DOMXPath($xml);

        $query = "//proj:folder[@db:id = '$id']/@url";

        $result = $xpath->evaluate($query);

        if ($result->length == 1) {
            $this->pathById[$id] = $result->item(0)->nodeValue;

            return $this->pathById[$id];
        }

        return false;
    }
    // }}}

    // {{{ toLibid()
    /**
     * @brieftoLibid
     *
     * @param mixed $libref
     * @return void
     **/
    public function toLibid($libref)
    {
        if (preg_match("|libid://(\d+)/([^/]*)|", $libref, $m)) {
            return $libref;
        }
        if (!preg_match("|libref://([^/]*)|", $libref, $m)) {
            return false;
        }
        $info = $this->getFileInfoByLibref($libref);

        if (!$info) return false;

        return $info->libid;
    }
    // }}}
    // {{{ toLibref()
    /**
     * @brieftoLibref
     *
     * @param mixed $libid
     * @return void
     **/
    public function toLibref($libid)
    {
        if (preg_match("|libref://([^/]*)|", $libid)) {
            return $libid;
        }
        if (!preg_match("|libid://(\d+)/([^/]*)|", $libid, $m)) {
            return false;
        }

        $id = (int) $m[1];
        $hash = $m[2];


        $info = $this->getFileInfoById($id);

        if (!$info) {
            $info = $this->getFileInfoByHash($hash);
        }
        if (!$info) return false;

        return $info->libref;
    }
    // }}}

    // {{{ getFileInfoByRef()
    /**
     * @brief getFileInfoByLibref
     *
     * @param mixed $libref
     * @return void
     **/
    public function getFileInfoByRef($ref)
    {
        $ref = $this->toLibid($ref);

        if (!preg_match("|libid://(\d+)/([^/]*)|", $ref, $m)) {
            return false;
        }

        $id = (int) $m[1];
        $hash = $m[2];

        $info = $this->getFileInfoById($id);

        if (!$info) {
            $info = $this->getFileInfoByHash($hash);
        }

        return $info;
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

        if (!$folderId) {
            return false;
        }

        $query = $this->pdo->prepare(
            "SELECT f.* FROM {$this->tableFiles} AS f
            WHERE
                folder=:folderId AND
                filenamehash=SHA1(:filename)"
        );

        $query->execute([
            'folderId' => $folderId,
            'filename' => $filename,
        ]);

        $info = $query->fetchObject("Depage\\Cms\\FileInfo");

        if ($info) {
            $info->init($path);
        } else {
            $info = $this->updateFileInfo(null, $folderId, $filename, $this->rootPath . $fullpath);
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
        $query = $this->pdo->prepare(
            "SELECT f.* FROM {$this->tableFiles} AS f
            WHERE
                id=:id"
        );

        $query->execute([
            'id' => $id,
        ]);

        $info = $query->fetchObject("Depage\\Cms\\FileInfo");

        if (!$info) {
            return false;
        }

        $path = $this->getPathByFolderId($info->folder);
        $info->init($path);

        return $info;
    }
    // }}}
    // {{{ getFileInfoByHash()
    /**
     * @brief getFileInfoByHash
     *
     * @param mixed $
     * @return void
     **/
    public function getFileInfoByHash($hash)
    {
        $query = $this->pdo->prepare(
            "SELECT f.* FROM {$this->tableFiles} AS f
            WHERE
                hash=:hash
            ORDER BY folder DESC
            LIMIT 1"
        );

        $query->execute([
            'hash' => $hash,
        ]);

        $info = $query->fetchObject("Depage\\Cms\\FileInfo");

        if (!$info) {
            return false;
        }

        $path = $this->getPathByFolderId($info->folder);
        $info->init($path);

        return $info;
    }
    // }}}

    // {{{ setImageCenter()
    /**
     * @brief setImageCenter
     *
     * @param mixed $fileId, $centerX, $centerY
     * @return void
     **/
    public function setImageCenter(int $fileId, int $centerX, int $centerY)
    {
        $centerX = min(100, max(0, $centerX));
        $centerY = min(100, max(0, $centerY));

        $query = $this->pdo->prepare(
            "UPDATE {$this->tableFiles}
            SET centerX=:centerX, centerY=:centerY
            WHERE id=:fileId"
        );

        return $query->execute([
            'fileId' => $fileId,
            'centerX' => $centerX,
            'centerY' => $centerY,
        ]);
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
            $file->init($path);

            $files[$file->filename] = $file;
        }

        return $files;
    }
    // }}}

    // {{{ searchFiles()
    /**
     * @brief getFilesInFolder
     *
     * @param mixed int $id
     * @return void
     **/
    public function searchFiles(string $search, string $searchType = "filename", string $mime = "*", int $limit = 1000):array
    {
        $files = [];
        $where = [];
        $params = [
            'limit' => $limit,
        ];
        $searchFields = ['filename'];
        if ($searchType == "all") {
            $searchFields += [
                'artist',
                'album',
                'title',
                'copyright',
                'description',
                'keywords',
                'customKeywords',
            ];
        }

        $queries = explode(" ", trim($search));

        foreach ($queries as $i => $q) {
            $q = \Depage\Entity\PdoEntity::escapeLike($q, '|');
            $w = [];

            foreach ($searchFields as $j => $f) {
                $w[] = "{$f} LIKE :{$f}{$i} ESCAPE '|'";
                $params["{$f}{$i}"] = "%$q%";
            }

            $where[] = "(" . implode(" OR ", $w) . ")";
        }

        $mimeQuery = "";
        if ($mime != "*") {
            $where[] = "mime LIKE :mime";
            $params['mime'] = str_replace("*", "%", $mime);
        }

        $metadataQuery = "";
        if ($searchType == "fulltext") {
            $where[] = "MATCH(artist, album, title, copyright, description, keywords, customKeywords) AGAINST (:metadata IN NATURAL LANGUAGE MODE)";

            $params['metadata'] = $search;
        }

        if (!empty($where)) {
            $where = implode(" AND ", $where);
        } else {
            $where = "";
        };


        $query = $this->pdo->prepare(
            "SELECT f.* FROM {$this->tableFiles} AS f
            WHERE {$where}
            ORDER BY filename ASC
            LIMIT :limit",
        [\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);

        $query->execute($params);

        while ($file = $query->fetchObject("Depage\\Cms\\FileInfo")) {
            $path = $this->getPathByFolderId($file->folder);

            if (strpos($path, "/cache/") === 0) continue;

            $file->init($path);

            $files[] = $file;
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
