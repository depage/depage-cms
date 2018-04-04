<?php

use PHPUnit\Framework\TestCase;
use Depage\HtmlForm\HtmlForm;
use Depage\HtmlForm\Elements\Fieldset;

/**
 * General tests for the fieldset element.
 **/
class fieldsetTest extends TestCase
{
    // {{{ setUp()
    public function setUp()
    {
        $this->form = new nameTestForm('formName');
        $this->fieldset = new Fieldset('fieldsetName', array(), $this->form);
    }
    // }}}

    // {{{ testAddFieldset()
    /**
     * Tests __call method and adding container subelements
     **/
    public function testAddFieldset()
    {
        $fieldset2  = $this->fieldset->addFieldset('secondFieldsetName');
        // include fieldsets true (getElements(true))
        $elements   = $this->fieldset->getElements(true);

        $this->assertEquals($elements[0], $fieldset2);
    }
    // }}}

    // {{{ testAddText()
    /**
     * Tests __call method and adding input subelements
     **/
    public function testAddText()
    {
        $text       = $this->fieldset->addText('textName');
        $elements   = $this->fieldset->getElements();

        $this->assertEquals($text, $elements[0]);
    }
    // }}}

    // {{{ testClearValue()
    /**
     * Tests clearValue method
     **/
    public function testClearValue()
    {
        $text       = $this->fieldset->addText('textName');

        $text->setValue("test");
        $this->assertEquals($text->getValue(), "test");

        $this->fieldset->clearValue();
        $this->assertNull($text->getValue());

    }
    // }}}
}
