<?php

namespace Depage\Php;

/**
 * brief Info
 * Class Info
 */
class Info
{
    // {{{ parseInfo()
    /**
     * @brief parseInfo
     *
     * @param mixed
     * @return void
     **/
    protected function parseInfo($what = \INFO_ALL)
    {
        ob_start();
        phpinfo($what);

        $info = preg_replace(
            [
                '#^.*<body>(.*)</body>.*$#ms',
                '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',
                "#\r?\n#",
                "#</(h1|h2|h3|tr)>#",
                '# +<#',
                "#[ \t]+#",
                '#&nbsp;#',
                '#  +#',
                '# class=".*?"#',
                '%&#039;%',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
                .'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                "# +#",
                '#<tr>#',
                '#</tr>#',
            ], [
                '$1',
                '',
                '',
                '',
                '</$1>' . "\n",
                '<',
                ' ',
                ' ',
                ' ',
                '',
                ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'."\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" . '<tr><td>Zend Egg</td><td>$1</td></tr>',
                ' ',
                '%S%',
                '%E%',
            ],
            ob_get_clean()
        );

        $sections = explode('<h2>', strip_tags($info, '<h2><th><td>'));
        unset($sections[0]);

        $info = array();
        foreach($sections as $section) {
            $n = substr($section, 0, strpos($section, '</h2>'));
            preg_match_all(
                '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                $section, $matches, PREG_SET_ORDER
            );
            foreach($matches as $m) {
                $value = !isset($m[3]) || $m[2] == $m[3] ? $m[2] : array_slice($m, 2);
                $info[$n][$m[1]] = str_replace(",", ", ", $value);
            }
        }

        unset($info['PHP Variables']);

        return $info;
    }
    // }}}
    // {{{ getInfo()
    /**
     * @brief getInfo
     *
     * @param mixed
     * @return void
     **/
    public function getInfo()
    {
        $phpinfo = $this->parseInfo();

        $info = [
            'PHP' => $phpinfo['Core'],
            //'extensions' => get_loaded_extensions(),
            //'ini' => ini_get_all(),
        ];
        unset($phpinfo['Core']);

        $info += $phpinfo;

        return $info;
    }
    // }}}
}


/* vim:set ft=php sts=4 fdm=marker et : */
