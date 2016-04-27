<?php

/**
 * @file    framework/xmlDb/permissions.php
 *
 * depage xmlDb permissions
 *
 *
 * copyright (c) 2011 Lion Vollnhals [lion.vollnhals@googlemail.com]
 * copyright (c) 2011 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Lion Vollnhals [lion.vollnhals@googlemail.com]
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\XmlDb;

class Permissions {
    // {{{ constants
    const wildcard = "all";
    const default_element = "default";
    // }}}
    // {{{ variables
    protected $allow_element_in = array();
    protected $allow_unlink_of = array();
    // }}}

    // {{{ constructor
    public function __construct($serialized_value = null) {
        if ($serialized_value) {
            list($this->allow_element_in, $this->allow_unlink_of) = unserialize($serialized_value);
        }
    }
    // }}}
    // {{{ allow_element_in()
    public function allow_element_in($element, $target) {
        if (!isset($this->allow_element_in[$element])) {
            $this->allow_element_in[$element] = array();
        }

        if (!in_array($target, $this->allow_element_in[$element])) {
            if ($target != self::wildcard) {
                $this->allow_element_in[$element][] = $target;
            } else {
                $this->allow_element_in[$element] = array($target);
            }
        }
    }
    // }}}
    // {{{ allow_unlink_of()
    public function allow_unlink_of($element) {
        if (!in_array($element, $this->allow_unlink_of)) {
            if ($element != self::wildcard) {
                $this->allow_unlink_of[] = $element;
            } else {
                $this->allow_unlink_of[] = array($element);
            }
        }
    }
    // }}}
    // {{{ is_element_allowed_in()
    public function is_element_allowed_in($element, $target) {
        return isset($this->allow_element_in[$element]) &&
            (in_array($target, $this->allow_element_in[$element]) || in_array(self::wildcard, $this->allow_element_in[$element]));
    }
    // }}}
    // {{{ is_element_allowed_in_any()
    public function is_element_allowed_in_any($element) {
        return isset($this->allow_element_in[$element]);
    }
    // }}}
    // {{{ is_unlink_allowed_of()
    public function is_unlink_allowed_of($element) {
        return in_array($element, $this->allow_unlink_of) || in_array(self::wildcard, $this->allow_unlink_of);
    }
    // }}}
    // {{{ valid_children()
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

        // resolve wildcard
        if (isset($valid_children_for[self::wildcard])) {
            if (!isset($valid_children_for[self::default_element])) {
                $valid_children_for[self::default_element] = array();
            }

            $known_elements = array_unique(array_merge(array_keys($valid_children_for), array_keys($this->allow_element_in)));
            foreach ($known_elements as $element) {
                if (!isset($valid_children_for[$element])) {
                    $valid_children_for[$element] = array();
                }
                $valid_children_for[$element] = array_unique(array_merge($valid_children_for[$element], $valid_children_for[self::wildcard]));
            }

            unset($valid_children_for[self::wildcard]);
        }

        return $valid_children_for;
    }
    // }}}
    // {{{ known_elements()
    public function known_elements() {
        $known_elements = array_merge(array_keys($this->allow_element_in), $this->allow_unlink_of);
        foreach ($this->allow_element_in as $element => $targets) {
            $known_elements = array_merge($known_elements, $targets);
        }

        foreach ($known_elements as $key => $value) {
            if ($value == self::wildcard) {
                unset($known_elements[$key]);
            }
        }

        return array_unique($known_elements);
    }
    // }}}
    // {{{ __toString()
    public function __toString() {
        return serialize(array($this->allow_element_in, $this->allow_unlink_of));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
