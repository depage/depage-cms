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
    const wildcard = "all";
    const default_element = "default";

    protected $allow_element_in = array();
    protected $allow_unlink_of = array();

    public function __construct($serialized_value = null) {
        if ($serialized_value) {
            list($this->allow_element_in, $this->allow_unlink_of) = unserialize($serialized_value);
        }
    }

    public function allow_element_in($element, $target) {
        if (!isset($this->allow_element_in[$element])) {
            $this->allow_element_in[$element] = array();
        }

        if (!in_array($target, $this->allow_element_in[$element])) {
            if ($target !== self::wildcard) {
                $this->allow_element_in[$element][] = $target;
            } else {
                $this->allow_element_in[$element] = array($target);
            }
        }
    }

    public function allow_unlink_of($element) {
        if (!in_array($element, $this->allow_unlink_of)) {
            if ($element !== self::wildcard) {
                $this->allow_unlink_of[] = $element;
            } else {
                $this->allow_unlink_of[] = array($element);
            }
        }
    }

    public function is_element_allowed_in($element, $target) {
        return isset($this->allow_element_in[$element]) && 
            (in_array($target, $this->allow_element_in[$element]) || in_array(self::wildcard, $this->allow_element_in[$element]));
    }

    public function is_element_allowed_in_any($element) {
        return isset($this->allow_element_in[$element]);
    }

    public function is_unlink_allowed_of($element) {
        return in_array($element, $this->allow_unlink_of) || in_array(self::wildcard, $this->allow_unlink_of);
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

        if (!isset($valid_children_for[self::default_element])) {
            $valid_children_for[self::default_element] = array();
        }

        // resolve wildcard
        if (isset($valid_children_for[self::wildcard])) {
            $known_elements = array_unique(array_keys($valid_children_for), array_keys($this->allow_element_in));
            foreach ($known_elements as $element) {
                $valid_children_for[$element] = array_unique($valid_children_for[$element] + $valid_children_for[self::wildcard]);
            }

            unset($valid_children_for[self::wildcard]);
        }

        return $valid_children_for;
    }

    public function __toString() {
        return serialize(array($this->allow_element_in, $this->allow_unlink_of));
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
