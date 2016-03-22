<?php
$_SERVER['REQUEST_URI'] = 'http://www.depagecms.net/';
session_start();

// using the autoloader from the main class
require_once(__DIR__ . '/../HtmlForm.php');

// {{{ logTestClass
/**
 * Dummy test class (for element & validator tests)
 **/
class logTestClass
{
    public $error = array(
        'argument'  => '',
        'type'      => '',
    );

    public function log($argument, $type = null)
    {
        $this->error = array(
            'argument'  => $argument,
            'type'      => $type,
        );
    }
}
// }}}

// {{{ nameTestForm
/**
 * Dummy form class
 **/
class nameTestForm
{
    public function getName()
    {
        return 'formName';
    }

    public function checkElementName() {}
    public function updateInputValue() {}
    public function getNamespaces()
    {
        return array('\\Depage\\HtmlForm\\Elements');
}
}
// }}}
//
// {{{ csrfTestForm
/**
 * Dummy form class
 **/
class csrfTestForm extends Depage\HtmlForm\HtmlForm
{
    protected function getNewCsrfToken() {
        return "xxxxxxxx";
    }
}
// }}}
