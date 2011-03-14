<?php 

namespace depage\htmlform\elements;

use depage\htmlform\abstracts;

/** 
 * HTML-single-choice input type i.e. radio and select.
 **/
class single extends abstracts\input {
    /** 
     * Contains list of selectable options.
     **/
    protected $list = array();

    /**
     * @param $name input elements' name
     * @param $parameters array of input element parameters, HTML attributes, validator specs etc.
     * @param $formName name of the parent HTML form. Used to identify the element once it's rendered.
     **/
    public function __construct($name, $parameters, $formName) {
        parent::__construct($name, $parameters, $formName);

        // single-choice-elements have values of type string
        $this->defaultValue = (isset($parameters['defaultValue']))  ? $parameters['defaultValue']   : "";
        $this->list   = (isset($parameters['list']))    ? $parameters['list']     : array();
        $this->skin         = (isset($parameters['skin']))          ? $parameters['skin']           : "radio";
    }

    /**
     * Renders HTML - option list part of select element.
     * Works recursively in case of optgroups.
     *
     * @param $optionsArray array of list elements and subgroups
     * @param $value value that is to be marked as selecteѕ
     * @return (string) options-part of the HTML-select-element
     **/
    private function renderOptions($optionsArray, $value) {
        $options = '';
        foreach($optionsArray as $index => $option) {
            if (is_array($option)) {
                $options .= "<optgroup label=\"$index\">" . $this->renderOptions($option, $value) . "</optgroup>";
            } else {
                $selected = ($index === $value) ? ' selected' : '';
                $options .= "<option value=\"$index\"$selected>$option</option>";
            }
        }
        return $options;
    }

    /**
     * Renders element to HTML.
     *
     * @return string of HTML rendered element
     **/
    public function __toString() {
        $options        = '';
        $value          = $this->htmlValue();
        $classes        = $this->htmlClasses();
        $requiredChar   = $this->htmlRequiredChar();
        $attributes     = $this->htmlInputAttributes();


        if ($this->skin === "select") {

            // render HTML select

            $options = $this->renderOptions($this->list, $value);
            return "<p id=\"$this->formName-$this->name\" class=\"$classes\">" .
                "<label" . $this->htmlLabelAttributes() . ">" .
                    "<span class=\"label\">$this->label$requiredChar</span>" .
                    "<select name=\"$this->name\"$attributes>$options</select>" .
                "</label>" .
            $this->htmlErrorMessage() .
            "</p>\n";
        } else {

            // render HTML checkbox

            foreach($this->list as $index => $option) {
                $selected = ($index === $value) ? " checked=\"yes\"" : '';
                $options .= "<span>" .
                    "<label" . $this->htmlLabelAttributes() . ">" .
                        "<input type=\"radio\" name=\"$this->name\"$attributes value=\"$index\"$selected>" .
                        "<span>$option</span>" .
                    "</label>" .
                "</span>";
            }
            return "<p id=\"$this->formName-$this->name\" class=\"$classes\">" .
                "<span class=\"label\">$this->label$requiredChar</span>" .
                "<span>$options</span>" .
                $this->htmlErrorMessage() .
            "</p>\n";
        }
    }

    /**
     * Converts value to element specific type.
     **/
    protected function typeCastValue() {
        $this->value = (string) $this->value;
    }
}
