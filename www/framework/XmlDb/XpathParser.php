<?php

namespace Depage\XmlDb;

use Depage\XmlDb\Exceptions\XmlDbException;

/**
 * Parses XPath strings into XpathElement objects.
 *
 * The consumer (e.g. XmlDb::getNodeIdsByXpath) receives pre-parsed objects
 * carrying all condition data, avoiding repeated parser calls inside the
 * element iteration loop.  SQL assembly is done in the caller so the
 * XpathParser remains SQL-independent and potentially reusable.
 */
class XpathParser
{
    // {{{ parseXpathElements
    /**
     * @param   string  $xpath  XPath string to parse
     * @return  XpathElement[]  array of parsed XpathElement objects
     */
    public function parseXpathElements(string $xpath): array
    {
        $pName       = '(?:([^\/\[\]]*):)?([^\/\[\]]+)';
        $pCondition = '(?:\[(.*?)\])?';
        preg_match_all("/(\/+)$pName$pCondition/", $xpath, $raw, PREG_SET_ORDER);

        $elements = [];
        foreach ($raw as $match) {
            $elements[] = new XpathElement(
                $match[1] ?? '',    // divider
                $match[2] ?? '',    // namespace
                $match[3] ?? '',    // name
                $match[4] ?? ''     // condition
            );
        }
        return $elements;
    }
    // }}}

    // {{{ parsePosition
    /**
     * @param   string  $condition  condition string from xpath
     * @return  false|array  array with ['operator', 'value'] or false if no match
     */
    public function parsePosition($condition): array|false
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

    // {{{ parseAttributes
    /**
     * @param   string  $condition  condition string to parse
     * @return  array|false  array of condition rows or false if invalid
     */
    public function parseAttributes($condition): false|array
    {
        $cond_array = false;
        $temp_condition = $this->removeLiteralStrings($condition, $strings);

        if (preg_match('/^[\w\d@=: -<>\*]*$/', $temp_condition)) {
            $cond_array = $this->getConditionAttributes($temp_condition, $strings);
        }
        return $cond_array;
    }
    // }}}

    // {{{ translateName
    /**
     * @param   string  $ns  namespace
     * @param   string  $name  node name
     * @return  string  translated name with wildcards replaced by SQL LIKE wildcards
     */
    public function translateName($ns, $name): string
    {
        $colon = (strlen($ns) && strlen($name)) ? ':' : '';
        return str_replace('*', '%', "$ns$colon$name");
    }
    // }}}

    // {{{ getConditionOperator
    /**
     * @param   string  $ns  namespace
     * @param   string  $name  node name
     * @return  string  'LIKE' if wildcards detected, '=' otherwise
     */
    public function getConditionOperator($ns, $name): string
    {
        if (str_contains($ns, "*") || str_contains($name, "*")) {
            return 'LIKE';
        } else {
            return '=';
        }
    }
    // }}}

    // {{{ getConditionAttributes
    /**
     * @param   string  $conditionString  cleaned condition string
     * @param   array   $strings         reference array for literal strings
     * @return  array  array of condition rows
     */
    public function getConditionAttributes($conditionString, $strings): array
    {
        $conditionArray = [];
        $pAttr     = '@(\w[\w\d:]*)';
        $pOperator = '(=|!=|<|>|<=|>=)';
        $pBool     = '([^@\s]+)';
        $pString   = '\$(\d*)';

        $matched = preg_match_all("/$pBool?\s*$pAttr\s*(?:$pOperator\s*$pString)?/", $conditionString, $conditions, PREG_SET_ORDER);

        $first = true;
        foreach ($conditions as $condition) {
            $bool = $condition[1] ? strtolower($condition[1]) : null;
            if ($first && $bool) {
                throw new XmlDbException('Invalid XPath syntax');
            }
            if (!in_array($bool, ['and', 'or', null])) {
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
     * @param   string  $text       input text
     * @param   array   $strings   reference array to populate with extracted literal strings
     * @return  string  text with literals replaced by placeholders
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

    // {{{ cleanOperator
    /**
     * @param   string  $operator  xpath operator string
     * @return  string  cleaned/validated operator
     * @throws  XmlDbException  if operator is invalid
     */
    public function cleanOperator($operator): string
    {
        $operator = strtolower(trim($operator));
        $cleaned = '';
        $operators = ['=', '!=', '<=', '>=', '<', '>', 'and', 'or'];

        if (in_array($operator, $operators)) {
            $cleaned = $operator;
        } else {
            throw new XmlDbException("Invalid XPath operator \"$operator\"");
        }
        return $cleaned;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
