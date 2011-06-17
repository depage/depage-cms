<?php

/**
 * @file    framework/xmldb/permissions.php
 *
 * depage cms jstree module
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 */

namespace depage\xmldb; 

class permissions {
    protected $allow_element_in = array();
    protected $allow_unlink_of = array();

    public function __construct($allow_element_in = array(), $allow_unlink_of = array()) {
        $this->allow_element_in = $allow_element_in;
        $this->allow_unlink_of = $allow_unlink_of;
    }

    public function allow_element_in($element, $target) {
        if (!isset($this->allow_element_in[$element])) {
            $this->allow_element_in[$element] = array();
        }

        if (!in_array($target, $this->allow_element_in[$element])) {
            $this->allow_element_in[$element][] = $target;
        }
    }

    public function allow_unlink_of($element) {
        if (!in_array($element, $this->allow_unlink_of)) {
            $this->allow_unlink_of[] = $element;
        }
    }

    public function is_element_allowed_in($element, $target) {
        return isset($this->allow_element_in[$element]) && in_array($target, $this->allow_element_in[$element]);
    }

    public function is_element_allowed_in_any($element) {
        return isset($this->allow_element_in[$element]);
    }

    public function is_unlink_allowed_of($element) {
        return in_array($element, $this->allow_unlink_of);
    }

    public function valid_children() {
        $valid_children_for = array();
        
        foreach ($this->allow_element_in as $element => $targets) {
            foreach ($targets as $target) {
                if (!isset($valid_children_for[$target])) {
                    $valid_children_for[$target] = array();
                }

                $valid_children_for[$target][] = $element;
            }
        }

        return $valid_children_for;
    }

    public function __toString() {
        return serialize($this);
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
