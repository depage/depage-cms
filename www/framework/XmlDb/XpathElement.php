<?php

namespace Depage\XmlDb;

use Depage\XmlDb\Exceptions\XmlDbException;

/**
 * A single parsed XPath element representing one node-selector in the path.
 *
 * All parsing logic (condition, position, attributes) is encapsulated in the
 * constructor so that callers (e.g. XmlDb::getNodeIdsByXpath) never need to
 * call XpathParser helpers again — they only read pre-computed properties.
 *
 * The parser itself remains SQL-agnostic; the caller is responsible for
 * assembling SQL fragments from the pre-parsed data.
 */
class XpathElement
{
    // Raw selector parts
    public readonly string $divider;
    public readonly string $namespace;
    public readonly string $name;
    public readonly string $condition;

    // Pre-computed parsed conditions (set in __construct)
    public readonly mixed $position;        // false or ['op', 'value']
    public readonly mixed $attributes;      // array of condition-rows or false
    public readonly bool $attributesValid; // true when condition matched attribute regex

    // {{{ __construct
    /**
     * @param   string  $divider       divider character
     * @param   string  $namespace     namespace prefix
     * @param   string  $name          node name
     * @param   string  $condition     condition string
     */
    public function __construct(string $divider, string $namespace, string $name, string $condition)
    {
        $this->divider      = $divider;
        $this->namespace    = $namespace;
        $this->name         = $name;
        $this->condition    = $condition;

        // Derive position from condition
        if (is_string($condition)) {
            $this->position = $this->parsePosition($condition);
        } else {
            $this->position = false;
        }

        // Derive attributes from condition
        $attributesValid = false;
        $attributes       = false;
        if ($condition !== '') {
            $cleanString = $this->removeLiteralStrings($condition, $strings);
            $validMatch = preg_match('/^[\w\d@=: -<>\*]*$/', $cleanString);
            if ((bool) $validMatch) {
                $attributesValid = true;
                try {
                    $attributes = $this->getConditionAttributes($cleanString, $strings);
                } catch (XmlDbException $e) {
                    $attributesValid = false;
                    $attributes       = false;
                }
            }
        }

        $this->attributes = $attributes;
        $this->attributesValid = $attributesValid;
    }
    // }}}

    // {{{ parsePosition
    /**
     * @param   string  $condition  condition string
     * @return  false|array  parsed position data or false
     */
    protected function parsePosition($condition): array|false
    {
        $positionArray = [];
        $matches = [];
        $pOperator = '(=|!=|<|>|<=|>=)';
        $pPosition = '([0-9]+)';

        if (preg_match("/^\s*(?:(?:position\(\))\s*$pOperator)?\s*$pPosition\s*$/", $condition, $matches)) {
            $positionArray[] = ($matches[1] == '') ? '=' : $matches[1];
            $positionArray[] = $matches[2];
            return $positionArray;
        }
        return false;
    }
    // }}}

    // {{{ getConditionAttributes
    /**
     * @param   string  $conditionString  cleaned condition string
     * @param   array   $strings          extracted literal strings
     * @return  array  parsed condition rows
     * @throws  XmlDbException  on invalid syntax
     */
    protected function getConditionAttributes($conditionString, $strings)
    {
        $conditionArray = [];
        $pAttr     = '@(\w[\w\d:]*)';
        $pOperator = '(=|!=|<|>|<=|>=)';
        $pBool     = '(and|or|AND|OR)';
        $pString   = '\$(\d*)';

        preg_match_all("/$pBool?\s*$pAttr\s*(?:$pOperator\s*$pString)?/", $conditionString, $conditions, PREG_SET_ORDER);

        $first = true;
        foreach ($conditions as $condition) {
            $bool = $condition[1] ?? null;

            if ($first === $bool) {
                throw new XmlDbException('Invalid XPath syntax');
            }
            if ($first) {
                $first = false;
            }

            $conditionArray[] = [
                $condition[2],
                $condition[3] ?? null,
                (isset($condition[4]) && $condition[4] != '') ? $strings[$condition[4]] : null,
                $bool,
            ];
        }
        return $conditionArray;
    }
    // }}}

    // {{{ removeLiteralStrings
    /**
     * @param   string  $text    input text
     * @param   array   &$strings  reference to populate with extracted literals
     * @return  string  text with string literals replaced by placeholders
     */
    protected function removeLiteralStrings($text, &$strings): string
    {
        $n     = 0;
        $newText = '';
        $strings = [];

        $p = "/([^\"']*)|(?:\"([^\"]*)\"|'([^']*)')/";
        preg_match_all($p, $text, $parts);

        for ($i = 0; $i < count($parts[0]); $i++) {
            if ($parts[1][$i] == '' && ($parts[2][$i] != '' || $parts[3][$i] != '')) {
                $strings[$n] = $parts[2][$i] . $parts[3][$i];
                $newText .= "\$$n";
                $n++;
            } else {
                $newText .= $parts[1][$i];
            }
        }
        return $newText;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
