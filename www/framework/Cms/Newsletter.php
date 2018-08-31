<?php
/**
 * @file    framework/cms/cms_project.php
 *
 * depage cms project module
 *
 *
 * copyright (c) 2016 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

class Newsletter
{
    /**
     * @brief document
     **/
    public $document = null;

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $
     * @return void
     **/
    public function __construct($pdo, $project, $name)
    {
        $this->project = $project;
        $this->name = $name;
        $this->pdo = $pdo;

        $this->tableSubscribers = $this->pdo->prefix . "_proj_" . $this->project->name . "_newsletter_subscribers";
        $this->tableSent = $this->pdo->prefix . "_proj_" . $this->project->name . "_newsletter_sent";

        $configFile = "projects/" . $this->project->name . "/xslt/newsletter/config.php";
        if (file_exists($configFile)) {
            $this->conf = (object) include($configFile);
        } else {
            $this->conf = (object) [
                "from" => "",
            ];
        }
    }
    // }}}
    // {{{ loadAll()
    /**
     * @brief loadAll
     *
     * @return void
     **/
    static public function loadAll($pdo, $project)
    {
        $newsletters = [];
        $xmldb = $project->getXmlDb();

        $docs = $xmldb->getDocuments("", "Depage\\Cms\\XmlDocTypes\\Newsletter");
        $self = get_called_class();

        foreach ($docs as $doc) {
            $newsletter = new $self($pdo, $project, $doc->getDocInfo()->name);
            $newsletter->setDocument($doc);
            $newsletters[] = $newsletter;
        }
        // @todo sort by document date
        usort($newsletters, function($a, $b) {
            return $a->lastchange < $b->lastchange;
            //return strcmp($a->getTitle(), $b->getTitle());
        });

        return $newsletters;
    }
    // }}}
    // {{{ loadReleased()
    /**
     * @brief loadReleased
     *
     * @param mixed
     * @return void
     **/
    public static function loadReleased($pdo, $project)
    {
        $all = self::loadAll($pdo, $project);
        $newsletters = [];

        foreach ($all as $newsletter) {
            $versions = $newsletter->document->getHistory()->getVersions(true);
            if (count($versions) > 0) {
                $newsletters[] = $newsletter;
            }
        }

        return $newsletters;
    }
    // }}}
    // {{{ loadByName()
    /**
     * @brief loadByName
     *
     * @param mixed $
     * @return void
     **/
    public static function loadByName($pdo, $project, $name)
    {
        $xmldb = $project->getXmlDb();

        $docs = $xmldb->getDocuments($name, "Depage\\Cms\\XmlDocTypes\\Newsletter");
        $self = get_called_class();

        foreach ($docs as $doc) {
            $newsletter = new $self($pdo, $project, $name);
            $newsletter->setDocument($doc);

            return $newsletter;
        }

        throw new \Exception("Newsletter not found");
    }
    // }}}
    // {{{ create()
    /**
     * @brief create
     *
     * @param mixed $
     * @return void
     **/
    public static function create($pdo, $project)
    {
        $xmldb = $project->getXmlDb();

        $doc = $xmldb->createDoc('Depage\Cms\XmlDocTypes\Newsletter');

        $self = get_called_class();
        $newsletter = new $self($pdo, $project, "");
        $newsletter->setDocument($doc);

        $xml = new \DOMDocument();
        $xml->load(__DIR__ . "/XmlDocTypes/NewsletterXml/newsletter.xml");

        $node = $xml->getElementsByTagNameNS("http://cms.depagecms.net/ns/page", "title")->item(0);
        $node->setAttribute("value", $node->getAttribute("value") . " " . date("m/Y"));

        $node = $xml->getElementsByTagNameNS("http://cms.depagecms.net/ns/page", "linkdesc")->item(0);
        $node->setAttribute("value", $node->getAttribute("value") . " " . date("m/Y"));

        $doc->save($xml);

        return $newsletter;
    }
    // }}}

    // {{{ setDocument()
    /**
     * @brief setDocument
     *
     * @param mixed $
     * @return void
     **/
    protected function setDocument($doc)
    {
        $this->document = $doc;
        $docinfo = $doc->getDocInfo();
        $this->name = $docinfo->name;
        $this->id = $docinfo->id;
        $this->lastchange = $docinfo->lastchange;
    }
    // }}}

    // {{{ getNewsletterCanditates()
    /**
     * @brief getCandidates
     *
     * @param mixed
     * @return void
     **/
    public function getCandidates($xpath = "//sec:news")
    {
        $candidates = [];
        $xmldb = $this->project->getXmlDb();
        $pages = $this->project->getPages();
        $usedPages = [];

        $allNewsletters = self::loadAll($this->pdo, $this->project);
        foreach ($allNewsletters as $n) {
            if ($n->name != $this->name) {
                $ids = $n->document->getNodeIdsByXpath("//sec:news");
                foreach ($ids as $id) {
                    $usedPages[$n->document->getAttribute($id, "db:docref")] = true;
                }
            }
        }

        foreach ($pages as $page) {
            if ($page->released == true) {
                $ids = $xmldb->getNodeIdsByXpath($xpath, $page->id);
                if (count($ids) > 0) {
                    $page->alreadyUsed = isset($usedPages[$page->name]);
                    $candidates[$page->name] = $page;
                }
            }
        }

        return $candidates;
    }
    // }}}
    // {{{ setNewsletterPages()
    /**
     * @brief setNewsletterPages
     *
     * @param mixed $pages = []
     * @return void
     **/
    public function setNewsletterPages($pages = [], $xml = null)
    {
        if (is_null($xml)) {
            $xml = $this->getXml();
        } else if ($xml->nodeType == \XML_ELEMENT_NODE) {
            $xml = $xml->ownerDocument;
        }
        $xpath = new \DOMXPath($xml);
        $xpResult = $xpath->query("//sec:news");

        foreach ($xpResult as $node) {
            $node->parentNode->removeChild($node);
        }

        foreach ($pages as $page) {
            $newNode = $xml->createElement("sec:news");
            $newNode->setAttribute("db:docref", $page->name);

            $xml->documentElement->appendChild($newNode);
        }

        $this->document->save($xml);
    }
    // }}}

    // {{{ getBaseUrl()
    /**
     * @brief getBaseUrl
     *
     * @param mixed
     * @return void
     **/
    public function getBaseUrl()
    {
        return "project/" . $this->project->name . "/newsletter/" . $this->name . "/";
    }
    // }}}
    // {{{ getPreviewUrl()
    /**
     * @brief getPreviewUrl
     *
     * @return void
     **/
    public function getPreviewUrl($previewType, $lang = "de")
    {
        return DEPAGE_BASE . "project/{$this->project->name}/preview/newsletter/$previewType/$lang/{$this->name}.html";
    }
    // }}}
    // {{{ getXml()
    /**
     * @brief getXml
     *
     * @return void
     **/
    public function getXml()
    {
        return $this->document->getXml();
    }
    // }}}
    // {{{ getSubject()
    /**
     * @brief getSubject
     *
     * @param mixed $lang
     * @return void
     **/
    public function getSubject($lang)
    {
        if (!empty($lang)) {
            $nodes = $this->document->getNodeIdsByXpath("//pg:meta/pg:linkdesc[@lang = '$lang']");
        } else {
            $nodes = $this->document->getNodeIdsByXpath("//pg:meta/pg:linkdesc");
        }

        if (count($nodes) > 0) {
            return $this->document->getAttribute($nodes[0], "value");
        } else {
            return "";
        }
    }
    // }}}
    // {{{ getTitle()
    /**
     * @brief getTitle
     *
     * @param mixed $lang
     * @return void
     **/
    public function getTitle($lang = null)
    {
        if (!empty($lang)) {
            $nodes = $this->document->getNodeIdsByXpath("//pg:meta/pg:title[@lang = '$lang']");
        } else {
            $nodes = $this->document->getNodeIdsByXpath("//pg:meta/pg:title");
        }

        if (count($nodes) > 0) {
            return $this->document->getAttribute($nodes[0], "value");
        } else {
            return "";
        }
    }
    // }}}
    // {{{ getSubscriberCategories()
    /**
     * @brief getSubscriberCategories
     *
     * @param mixed
     * @return void
     **/
    public function getSubscriberCategories()
    {
        $query = $this->pdo->prepare(
            "SELECT category as name, COUNT(*) as count
            FROM
                {$this->tableSubscribers} AS subscribers
            GROUP BY
                category
            ORDER BY
                category ASC
            "
        );

        $query->execute();
        $categories = $query->fetchAll(\PDO::FETCH_OBJ);

        return $categories;
    }
    // }}}
    // {{{ getSubscribers()
    /**
     * @brief getSubscribers
     *
     * @param mixed $category
     * @return void
     **/
    public function getSubscribers($category)
    {
        $query = $this->pdo->prepare(
            "SELECT lang, email
            FROM
                {$this->tableSubscribers} AS subscribers
            WHERE
                category = :category
            "
        );

        $query->execute([
            'category' => $category,
        ]);
        $subscribers = $query->fetchAll(\PDO::FETCH_COLUMN | \PDO::FETCH_GROUP);

        return $subscribers;
    }
    // }}}
    // {{{ getTrackingHash()
    /**
     * @brief getTrackingHash
     *
     * @param mixed $
     * @return void
     **/
    public function getTrackingHash($to, $lang = "")
    {
        return sha1($this->name . "::" . $to . "::" . $lang);
    }
    // }}}

    // {{{ transform()
    /**
     * @brief transform
     *
     * @param mixed $
     * @return void
     **/
    public function transform($previewType, $lang)
    {
        $this->project->setPreviewType($previewType);
        $transformer = \Depage\Transformer\Transformer::factory($previewType, $this->project->getXmlGetter(), $this->project->name, "newsletter");

        if ($previewType == "live") {
            $targets = $this->project->getPublishingTargets();
            list($publishId) = array_keys($targets);
            $transformer->baseUrl = $this->project->getBaseUrl($publishId);
        } else {
            $transformer->baseUrl = DEPAGE_BASE . "project/{$this->project->name}/preview/html/live/";
        }
        $transformer->useAbsolutePaths = true;

        $html = $transformer->transformDoc("", $this->name, $lang);

        return $html;
    }
    // }}}
    // {{{ release()
    /**
     * @brief release
     *
     * @param mixed $userId
     * @return void
     **/
    public function release($userId)
    {
        $this->project->releaseDocument($this->name, $userId);
    }
    // }}}
    // {{{ sendToSubscribers()
    /**
     * @brief sendToSubscribers
     *
     * @param string $category
     * @return void
     **/
    public function sendToSubscribers($category)
    {
        // @todo add these inside of task
        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->name, $this->project->name);

        $subscribers = $this->getSubscribers($category);

        foreach ($subscribers as $lang => $emails) {
            $mail = new \Depage\Mail\Mail($this->conf->from);
            $mail->setSubject($this->getSubject($lang))
                ->setHtmlText($this->transform("live", $lang));
            // @todo add header List-Unsubscribe:

            $initId = $task->addSubtask("initializing mail", "\$mail = %s; \$newsletter = %s;", [
                $mail,
                $this,
            ]);

            foreach ($emails as $to) {
                $this->sendLater($task, $initId, $to, $lang);
            }
        }

        $task->begin();
    }
    // }}}
    // {{{ sendTo()
    /**
     * @brief sendTo
     *
     * @param string $to
     * @param string $lang
     * @return void
     **/
    public function sendTo($to, $lang)
    {
        $html = $this->transform("live", $lang);

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, $this->name, $this->project->name);

        $mail = new \Depage\Mail\Mail($this->conf->from);
        $mail->setSubject($this->getSubject($lang))
            ->setHtmlText($this->transform("live", $lang));
        // @todo add header List-Unsubscribe:

        $initId = $task->addSubtask("initializing mail", "\$mail = %s; \$newsletter = %s;", [
            $mail,
            $this,
        ]);

        $recipients = array_unique(explode(",", $to));

        foreach($recipients as $i => $to) {
            $this->sendLater($task, $initId, $to, $lang);
        }

        $task->begin();
    }
    // }}}
    // {{{ sendLater()
    /**
     * @brief sendLater
     *
     * @param mixed $
     * @return void
     **/
    protected function sendLater($task, $initId, $to, $lang)
    {
        $task->addSubtask("sending mail", "\$newsletter->send(\$mail, %s, %s);", [
            $to,
            $lang,
        ], $initId);
    }
    // }}}
    // {{{ send()
    /**
     * @brief send
     *
     * @param mixed $
     * @return void
     **/
    public function send($mail, $to, $lang)
    {
        $hash = $this->getTrackingHash($to, $lang);
        $trackingUrl = DEPAGE_BASE . "track/" . $this->project->name . "/newsletter/" . $this->name . "/" . $hash . "/footer.png";

        if ($mail->send($to, $trackingUrl)) {
            $query = $this->pdo->prepare(
                "INSERT
                INTO
                    {$this->tableSent}
                SET
                    id=:hash,
                    newsletter_id=:id,
                    email=:to,
                    lang=:lang
                ON DUPLICATE KEY UPDATE
                    id=VALUES(id), sendAt=NOW()
                "
            );

            $query->execute([
                'hash' => $hash,
                'id' => $this->id,
                'to' => $to,
                'lang' => $lang,
            ]);
        }
    }
    // }}}

    // {{{ subscribe()
    /**
     * @brief subscribe
     *
     * @param mixed $email, $firstname, $lastname, $description, $category
     * @return void
     **/
    public function subscribe($email, $firstname = "", $lastname = "", $description = "", $lang = "en", $category = "Default")
    {
        $this->clearUnconfirmed();

        list($validation, $validatedAt, $subscribedAt) = $this->getValidationFor($email);
        if ($validation === false) {
            $validation = sha1($email . uniqid(dechex(mt_rand(256, 4095))));
        }
        $this->unsubscribe($email, $lang, $category);

        $query = $this->pdo->prepare(
            "INSERT
            INTO
                {$this->tableSubscribers}
            SET
                email=:email,
                firstname=:firstname,
                lastname=:lastname,
                description=:description,
                category=:category,
                lang=:lang,
                validation=:validation,
                validatedAt=:validatedAt,
                subscribedAt=:subscribedAt
            "
        );
        $success = $query->execute([
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'description' => $description,
            'lang' => $lang,
            'category' => $category,
            'validation' => $validation,
            'validatedAt' => $validatedAt,
            'subscribedAt' => $subscribedAt,
        ]);

        if ($success) {
            return $validation;
        }

        return false;
    }
    // }}}
    // {{{ isSubscriber()
    /**
     * @brief isSubscriber
     *
     * @param mixed $email
     * @return void
     **/
    public function isSubscriber($email, $lang, $category)
    {
        $query = $this->pdo->prepare(
            "SELECT COUNT(*) AS n FROM
                {$this->tableSubscribers}
            WHERE
                email=:email AND
                lang=:lang AND
                category=:category AND
                validation IS NULL
            "
        );

        $success = $query->execute([
            'email' => $email,
            'lang' => $lang,
            'category' => $category,
        ]);

        return $query->fetchObject()->n > 0;
    }
    // }}}
    // {{{ getValidationFor()
    /**
     * @brief getValidationFor
     *
     * @param mixed $email
     * @return void
     **/
    protected function getValidationFor($email)
    {
        $query = $this->pdo->prepare(
            "SELECT validation, validatedAt, subscribedAt FROM
                {$this->tableSubscribers}
            WHERE
                email=:email
            "
        );
        $success = $query->execute([
            'email' => $email,
        ]);

        if ($r = $query->fetchObject()) {
            return [$r->validation, $r->validatedAt, $r->subscribedAt];
        }

        return [false, null, null];
    }
    // }}}
    // {{{ confirm()
    /**
     * @brief confirm
     *
     * @param mixed $param
     * @return void
     **/
    public function confirm($validation)
    {
        $query = $this->pdo->prepare(
            "SELECT
                email,
                firstname,
                lastname,
                description,
                lang
            FROM
                {$this->tableSubscribers}
            WHERE
                validation=:validation
            "
        );
        $success = $query->execute([
            'validation' => $validation,
        ]);

        if ($subscriber = $query->fetchObject()) {
            $query = $this->pdo->prepare(
                "UPDATE
                    {$this->tableSubscribers}
                SET
                    validation=NULL,
                    validatedAt=NOW()
                WHERE
                    validation=:validation
                "
            );
            $success = $query->execute([
                'validation' => $validation,
            ]);

            return $subscriber;
        }

        return $success;
    }
    // }}}
    // {{{ unsubscribe()
    /**
     * @brief unsubscribe
     *
     * @param mixed $
     * @return void
     **/
    public function unsubscribe($email, $lang = "en", $category = "Default")
    {
        $query = $this->pdo->prepare(
            "DELETE
            FROM
                {$this->tableSubscribers}
            WHERE
                email=:email AND
                lang=:lang AND
                category=:category
            "
        );
        $success = $query->execute([
            'email' => $email,
            'lang' => $lang,
            'category' => $category,
        ]);

        return $query->rowCount() > 0;

    }
    // }}}
    // {{{ clearUnconfirmed()
    /**
     * @brief clearUnconfirmed
     *
     * @param mixed
     * @return void
     **/
    protected function clearUnconfirmed()
    {
        $query = $this->pdo->prepare(
            "DELETE FROM {$this->tableSubscribers}
            WHERE
                validation IS NOT NULL AND
                subscribedAt < ADDDATE(NOW(),INTERVAL -2 WEEK)            "
        );

        return $query->execute();
    }
    // }}}

    // {{{ track()
    /**
     * @brief track
     *
     * @param mixed $
     * @return void
     **/
    public function track($hash)
    {
        $query = $this->pdo->prepare(
            "UPDATE
                {$this->tableSent}
            SET
                readAt=NOW()
            WHERE
                id=:hash
            "
        );

        $query->execute([
            'hash' => $hash,
        ]);
    }
    // }}}

    // {{{ __sleep()
    /**
     * allows Depage\Db\Pdo-object to be serialized
     */
    public function __sleep()
    {
        return array(
            'project',
            'pdo',
            'tableSubscribers',
            'tableSent',
            'conf',
            'name',
            'document',
            'id',
        );
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */
