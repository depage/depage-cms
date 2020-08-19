<?php
/**
 * @file    Project.php
 *
 * description
 *
 * copyright (c) 2020 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Api;

/**
 * @brief Project
 * Class Project
 */
class Project extends Json
{
    protected $autoEnforceAuth = false;

    // {{{ pageId()
    /**
     * @brief pageId
     *
     * @return object
     **/
    public function pageId()
    {
        $url = filter_input(INPUT_POST, 'url', FILTER_SANITIZE_STRING);

        $retVal = [
            'success' => false,
        ];
        if (!empty($url)) {
            $xmlGetter = $this->project->getXmlGetter();

            $transformer = \Depage\Transformer\Transformer::factory("dev", $xmlGetter, $this->project->name, "html");
            $transformer->routeHtmlThroughPhp = true;
            list($retVal['pageId'],, $retVal['urlPath']) = $transformer->getPageIdFor($url);

            $retVal['success'] = true;
        }

        return $retVal;
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :

