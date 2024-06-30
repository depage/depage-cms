<?php
/**
 * @file    BackgroundTasks.php
 *
 * description
 *
 * copyright (c) 2017 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms;

use \Depage\Html\Html;
use \Depage\Notifications\Notification;

/**
 * @brief BackgroundTasks
 * Class BackgroundTasks
 */
class BackgroundTasks
{
    protected $pdo;
    protected $baseUrl;
    protected $lang;
    protected $htmlOptions;

    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo
     * @return void
     **/
    public function __construct($pdo, $baseUrl)
    {
        $this->pdo = $pdo;
        $this->baseUrl = $baseUrl;
        // @todo get this dynamically from user?
        $this->lang = "en";
        $this->htmlOptions = [
            'template_path' => __DIR__ . "/Tpl/",
            'clean' => "space",
        ];
    }
    // }}}
    // {{{ schedule()
    /**
     * @brief schedule
     *
     * @param mixed
     * @return void
     **/
    public function schedule()
    {
        $this->scheduleNotifications();
    }
    // }}}

    // {{{ scheduleNotifications()
    /**
     * @brief scheduleNotifications
     *
     * @return void
     **/
    public function scheduleNotifications()
    {
        $nm = Notification::loadByTag($this->pdo, "mail.%");
        foreach($nm as $n) {
            if (!empty($n->uid)) {
                $to = \Depage\Auth\User::loadById($this->pdo, $n->uid)->email;

                $url = parse_url(DEPAGE_BASE);

                $subject = $url['host'] . " . " . $n->title;
                $text = "";
                $text .= sprintf(_("You received a new notification from %s:"), $url['host']) . "\n\n";
                $text .= $n->message . "\n\n";

                if (!empty($n->options["link"])) {
                    $text .= $n->options["link"] . "\n\n";
                }

                $text .= "--\n";
                $text .= _("Your faithful servant on") . "\n";
                $text .= DEPAGE_BASE . "\n";

                $mail = new \Depage\Mail\Mail("notifications@depage.net");
                $mail
                    ->setSubject($subject)
                    ->setText($text)
                    ->send($to);
            }
        }
        foreach ($nm as $n) {
            $n->delete();
        }
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
