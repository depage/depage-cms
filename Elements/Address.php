<?php
/**
 * @file    address.php
 * @brief   address fieldset element
 *
 * @author Ben Wallis <benedict_wallis@yahoo.co.uk>
 **/

namespace Depage\HtmlForm\Elements;

/**
 * @brief Default address fieldset.
 *
 * Class to get user address information. It generates a fieldset
 * that consists of
 *
 * - a state
 * - a country select
 *
 * Usage
 * -----
 *
 * @code
    <?php
        $form = new Depage\HtmlForm\HtmlForm('myform');

        // add a creditcard fieldset
        $form->addAddress('address', array(
            'label' => 'Address',
        ));

        // process form
        $form->process();

        // Display the form.
        echo ($form);
    @endcode
 **/
class Address extends Fieldset
{
    // {{{ __construct()
    /**
     * @brief   multiple class constructor
     *
     * @param  string $name       element name
     * @param  array  $parameters element parameters, HTML attributes, validator specs etc.
     * @param  object $form       parent form object
     * @return void
     **/
    public function __construct($name, $parameters, $form)
    {
        parent::__construct($name, $parameters, $form);

        $this->props = [
            'Address1',
            'Address2',
            'Zip',
            'City',
            'State',
            'Country',
        ];

        if (isset($parameters['props'])) {
            $this->props = $parameters['props'];
        }
        foreach ($this->props as $prop) {
            if (isset($parameters["default$prop"])) {
                $this->defaults["default$prop"] = $parameters["default$prop"];
            }
        }

        $this->defaults['priorityCountries'] = isset($parameters['priorityCountries'])
            ? $parameters['priorityCountries']
            : array();

        $this->prefix = isset($parameters['prefix'])
            ? rtrim($parameters['prefix'], '_') . '_'
            : '';
    }
    // }}}

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

        $this->defaults['required']             = false;
        $this->defaults['labelAddress1']        = _("Address 1");
        $this->defaults['labelAddress2']        = _("Address 2");
        $this->defaults['labelCity']            = _("City");
        $this->defaults['labelState']           = _("State");
        $this->defaults['labelZip']             = _("Zip");
        $this->defaults['labelCountry']         = _("Country");
    }
    // }}}

    // {{{ addChildElements()
    /**
     * @brief   adds address-inputs to fieldset
     *
     * @return void
     **/
    public function addChildElements()
    {
        parent::addChildElements();

        foreach ($this->props as $prop) {
            if (isset($this->defaults["default$prop"])) {
                $labelVar = "label$prop";

                if ($prop == "State") {
                    $this->addState($this->prefix . "state", array(
                        'label' => $this->labelState,
                        'defaultValue' => $this->defaults['defaultState'],
                        'required' => $this->required,
                    ));
                } else if ($prop == "Country") {
                    $this->addCountry($this->prefix . "country", array(
                        'label' => $this->labelCountry,
                        'priorityCountries' => $this->defaults['priorityCountries'],
                        'defaultValue' => $this->defaults['defaultCountry'],
                        'required' => $this->required,
                    ));
                } else {
                    $this->addText($this->prefix . strtolower($prop), array(
                        'label' => $this->$labelVar,
                        'defaultValue' => $this->defaults["default$prop"],
                        'required' => $this->required,
                    ));
                }
            }
        }
    }
    // }}}

    // {{{ validate()
    /**
     * @brief   Validate the address data
     *
     * @return bool validation result
     **/
    public function validate()
    {
        if (parent::validate() && isset($this->defaultCountry) && isset($this->defaultState)) {
            // check selected state matches the country
            $country_states = state::getStates();
            $country = $this->getElement("{$this->name}_country");
            $state = $this->getElement("{$this->name}_state");
            $this->valid = isset($country_states[$country->getValue()][$state->getValue()]);
            if (!$this->valid) {
                $state->errorMessage = _("State and Country do not match");
                $state->valid = false;
            }

            // TODO validate zip code
        }

        return $this->valid;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
