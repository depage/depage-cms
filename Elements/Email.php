<?php
/**
 * @file    elements/email.php
 * @brief   email input element
 *
 * @author Frank Hellenkamp <jonas@depage.net>
 * @author Sebastian Reinhold <sebastian@bitbernd.de>
 **/

namespace Depage\HtmlForm\Elements;

/**
 * @brief HTML email input type.
 *
 * Class for the HTML5 input-type "email".
 *
 * Usage
 * -----
 *
 * @code
    <?php
        $form = new Depage\HtmlForm\HtmlForm('myform');

        // add a required email field
        $form->addEmail('email', array(
            'label' => 'Email address',
            'required' => true,
        ));

        // process form
        $form->process();

        // Display the form.
        echo ($form);
    @endcode
 **/
class Email extends Text
{
    // {{{ setDefaults()
    /**
     * @brief   collects initial values across subclasses.
     *
     * The constructor loops through these and creates settable class
     * attributes at runtime. It's a compact mechanism for initialising
     * a lot of variables.
     *
     * @return void
     **/
    protected function setDefaults()
    {
        parent::setDefaults();

        $this->defaults['errorMessage'] = _('Please enter a valid email address');

        // @todo add option to test mail domain name (dns)
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
