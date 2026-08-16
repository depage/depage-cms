<?php

namespace Depage\XmlDb\Tests;

use Depage\XmlDb\XpathParser;

class XpathParserTest extends \PHPUnit\Framework\TestCase
{
    protected $parser;

    // {{{ setUp
    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new XpathParser();
    }
    // }}}

    // {{{ testParseXpathElementsSimple
    public function testParseXpathElementsSimple()
    {
        $result = $this->parser->parseXpathElements('/pg:page');
        // [0]=[full_match, divider, ns, name]
        $this->assertEquals('/', $result[0]->divider);
        $this->assertEquals('pg', $result[0]->namespace);
        $this->assertEquals('page', $result[0]->name);
    }
    // }}}

    // {{{ testParseXpathElementsNoNamespace
    public function testParseXpathElementsNoNamespace()
    {
        $result = $this->parser->parseXpathElements('/folder');
        $this->assertEquals('/', $result[0]->divider);
        $this->assertEquals('', $result[0]->namespace);
        $this->assertEquals('folder', $result[0]->name);
    }
    // }}}

    // {{{ testParseXpathElementsMultipleLevels
    public function testParseXpathElementsMultipleLevels()
    {
        $result = $this->parser->parseXpathElements('/dpg:pages/pg:page/pg:folder');

        $this->assertEquals('/', $result[0]->divider);
        $this->assertEquals('dpg', $result[0]->namespace);
        $this->assertEquals('pages', $result[0]->name);
        $this->assertEquals('', $result[0]->condition);

        $this->assertEquals('/', $result[1]->divider);
        $this->assertEquals('pg', $result[1]->namespace);
        $this->assertEquals('page', $result[1]->name);
        $this->assertEquals('', $result[1]->condition);

        $this->assertEquals('/', $result[2]->divider);
        $this->assertEquals('pg', $result[2]->namespace);
        $this->assertEquals('folder', $result[2]->name);
        $this->assertEquals('', $result[2]->condition);
    }
    // }}}

    // {{{ testParseXpathElementsDoubleSlash
    public function testParseXpathElementsDoubleSlash()
    {
        $result = $this->parser->parseXpathElements('//pg:page');
        $this->assertEquals('//', $result[0]->divider);
        $this->assertEquals('pg', $result[0]->namespace);
        $this->assertEquals('page', $result[0]->name);
        $this->assertEquals('', $result[0]->condition);
    }
    // }}}

    // {{{ testParseXpathElementsWildcardAll
    public function testParseXpathElementsWildcardAll()
    {
        $result = $this->parser->parseXpathElements('//*');
        $this->assertEquals('//', $result[0]->divider);
        $this->assertEquals('', $result[0]->namespace);
        $this->assertEquals('*', $result[0]->name);
        $this->assertEquals('', $result[0]->condition);
    }
    // }}}

    // {{{ testParseXpathElementsWildcardNamespace
    public function testParseXpathElementsWildcardNamespace()
    {
        $result = $this->parser->parseXpathElements('//*:node');
        $this->assertEquals('//', $result[0]->divider);
        $this->assertEquals('*', $result[0]->namespace);
        $this->assertEquals('node', $result[0]->name);
        $this->assertEquals('', $result[0]->condition);
    }
    // }}}

    // {{{ testParseXpathElementsWildcardName
    public function testParseXpathElementsWildcardName()
    {
        $result = $this->parser->parseXpathElements('/ns:*');
        $this->assertEquals('/', $result[0]->divider);
        $this->assertEquals('ns', $result[0]->namespace);
        $this->assertEquals('*', $result[0]->name);
    }
    // }}}

    // {{{ testParseXpathElementsWithPredicate
    public function testParseXpathElementsWithPredicate()
    {
        $result = $this->parser->parseXpathElements('/pg:page[@attr]');
        $this->assertEquals('@attr', $result[0]->condition);
    }
    // }}}

    // {{{ testParseXpathElementsWithPredicateAndValue
    public function testParseXpathElementsWithPredicateAndValue()
    {
        $result = $this->parser->parseXpathElements("/pg:page[@attr = 'val']");
        $this->assertEquals("@attr = 'val'", $result[0]->condition);
    }
    // }}}

    // {{{ testParseXpathElementsPosition
    public function testParseXpathElementsPosition()
    {
        $result = $this->parser->parseXpathElements('/pg:page[2]');
        $this->assertEquals('2', $result[0]->condition);
    }
    // }}}

    // {{{ testParseXpathElementsComplexPath
    public function testParseXpathElementsComplexPath()
    {
        $result = $this->parser->parseXpathElements('/a/b/c');
        $this->assertEquals('a', $result[0]->name);
        $this->assertEquals('b', $result[1]->name);
        $this->assertEquals('c', $result[2]->name);
    }
    // }}}

    // {{{ testParseXpathElementsMultipleDividers
    public function testParseXpathElementsMultipleDividers()
    {
        $result = $this->parser->parseXpathElements('///pg:page');
        $this->assertEquals('///', $result[0]->divider);
        $this->assertEquals('pg', $result[0]->namespace);
        $this->assertEquals('page', $result[0]->name);
    }
    // }}}

    // {{{ testParseXpathElementsAttributeAndPosition
    public function testParseXpathElementsAttributeAndPosition()
    {
        $result = $this->parser->parseXpathElements('/pg:page[@attr][2]');
        $this->assertEquals('@attr', $result[0]->condition);
    }
    // }}}

    // {{{ testParsePositionSimpleInteger
    public function testParsePositionSimpleInteger()
    {
        $result = $this->parser->parsePosition('2');
        $this->assertIsArray($result);
        $this->assertEquals('=', $result[0]);
        $this->assertEquals('2', $result[1]);
    }
    // }}}

    // {{{ testParsePositionWithPositionFunction
    public function testParsePositionWithPositionFunction()
    {
        $result = $this->parser->parsePosition('position() = 1');
        $this->assertIsArray($result);
        $this->assertEquals('=', $result[0]);
        $this->assertEquals('1', $result[1]);
    }
    // }}}

    // {{{ testParsePositionNotEquals
    public function testParsePositionNotEquals()
    {
        $result = $this->parser->parsePosition('position() != 3');
        $this->assertIsArray($result);
        $this->assertEquals('!=', $result[0]);
        $this->assertEquals('3', $result[1]);
    }
    // }}}

    // {{{ testParsePositionLessThan
    public function testParsePositionLessThan()
    {
        $result = $this->parser->parsePosition('position() < 4');
        $this->assertIsArray($result);
        $this->assertEquals('<', $result[0]);
        $this->assertEquals('4', $result[1]);
    }
    // }}}

    // {{{ testParsePositionGreaterThan
    public function testParsePositionGreaterThan()
    {
        $result = $this->parser->parsePosition('position() > 5');
        $this->assertIsArray($result);
        $this->assertEquals('>', $result[0]);
        $this->assertEquals('5', $result[1]);
    }
    // }}}

    // {{{ testParsePositionLessThanOrEqual
    public function testParsePositionLessThanOrEqual()
    {
        $result = $this->parser->parsePosition('position() <= 10');
        $this->assertIsArray($result);
        $this->assertEquals('<=', $result[0]);
        $this->assertEquals('10', $result[1]);
    }
    // }}}

    // {{{ testParsePositionGreaterThanOrEqual
    public function testParsePositionGreaterThanOrEqual()
    {
        $result = $this->parser->parsePosition('position() >= 10');
        $this->assertIsArray($result);
        $this->assertEquals('>=', $result[0]);
        $this->assertEquals('10', $result[1]);
    }
    // }}}

    // {{{ testParsePositionInvalid
    public function testParsePositionInvalid()
    {
        $result = $this->parser->parsePosition('invalid');
        $this->assertFalse($result);
    }
    // }}}

    // {{{ testParsePositionInvalidExpression
    public function testParsePositionInvalidExpression()
    {
        $result = $this->parser->parsePosition('position() == 1');
        $this->assertFalse($result);
    }
    // }}}

    // {{{ testParsePositionWithWhitespace
    public function testParsePositionWithWhitespace()
    {
        $result1 = $this->parser->parsePosition('position() = 2');
        $this->assertIsArray($result1);
        $result2 = $this->parser->parsePosition('position()= 2');
        $this->assertIsArray($result2);
        $result3 = $this->parser->parsePosition('position() =2');
        $this->assertIsArray($result3);
        $result4 = $this->parser->parsePosition('position()=2');
        $this->assertIsArray($result4);
    }
    // }}}

    // {{{ testParsePositionLargeNumber
    public function testParsePositionLargeNumber()
    {
        $result = $this->parser->parsePosition('position() = 9999');
        $this->assertIsArray($result);
        $this->assertEquals('=', $result[0]);
        $this->assertEquals('9999', $result[1]);
    }
    // }}}

    // {{{ testTranslateNameWithNamespaceAndName
    public function testTranslateNameWithNamespaceAndName()
    {
        $result = $this->parser->translateName('pg', 'page');
        $this->assertEquals('pg:page', $result);
    }
    // }}}

    // {{{ testTranslateNameEmptyNamespace
    public function testTranslateNameEmptyNamespace()
    {
        $result = $this->parser->translateName('', 'page');
        $this->assertEquals('page', $result);
    }
    // }}}

    // {{{ testTranslateNameEmptyName
    public function testTranslateNameEmptyName()
    {
        $result = $this->parser->translateName('pg', '');
        $this->assertEquals('pg', $result);
    }
    // }}}

    // {{{ testTranslateNameBothEmpty
    public function testTranslateNameBothEmpty()
    {
        $result = $this->parser->translateName('', '');
        $this->assertEquals('', $result);
    }
    // }}}

    // {{{ testTranslateNameWildcardName
    public function testTranslateNameWildcardName()
    {
        $result = $this->parser->translateName('pg', '*');
        $this->assertEquals('pg:%', $result);
    }
    // }}}

    // {{{ testTranslateNameWildcardNamespace
    public function testTranslateNameWildcardNamespace()
    {
        $result = $this->parser->translateName('*', 'page');
        $this->assertEquals('%:page', $result);
    }
    // }}}

    // {{{ testTranslateNameBothWildcards
    public function testTranslateNameBothWildcards()
    {
        $result = $this->parser->translateName('*', '*');
        $this->assertEquals('%:%', $result);
    }
    // }}}

    // {{{ testGetConditionOperatorWithWildcardInNs
    public function testGetConditionOperatorWithWildcardInNs()
    {
        $result = $this->parser->getConditionOperator('*', 'page');
        $this->assertEquals('LIKE', $result);
    }
    // }}}

    // {{{ testGetConditionOperatorWithWildcardInName
    public function testGetConditionOperatorWithWildcardInName()
    {
        $result = $this->parser->getConditionOperator('pg', '*');
        $this->assertEquals('LIKE', $result);
    }
    // }}}

    // {{{ testGetConditionOperatorWithoutWildcard
    public function testGetConditionOperatorWithoutWildcard()
    {
        $result = $this->parser->getConditionOperator('pg', 'page');
        $this->assertEquals('=', $result);
    }
    // }}}

    // {{{ testGetConditionOperatorEmptyStrings
    public function testGetConditionOperatorEmptyStrings()
    {
        $result = $this->parser->getConditionOperator('', '');
        $this->assertEquals('=', $result);
    }
    // }}}

    // {{{ testGetConditionOperatorBothWildcards
    public function testGetConditionOperatorBothWildcards()
    {
        $result = $this->parser->getConditionOperator('*', '*');
        $this->assertEquals('LIKE', $result);
    }
    // }}}

    // {{{ testCleanOperatorValidEquals
    public function testCleanOperatorValidEquals()
    {
        $this->assertEquals('=', $this->parser->cleanOperator('='));
    }
    // }}}

    // {{{ testCleanOperatorValidNotEquals
    public function testCleanOperatorValidNotEquals()
    {
        $this->assertEquals('!=', $this->parser->cleanOperator('!='));
    }
    // }}}

    // {{{ testCleanOperatorValidLessThan
    public function testCleanOperatorValidLessThan()
    {
        $this->assertEquals('<', $this->parser->cleanOperator('<'));
    }
    // }}}

    // {{{ testCleanOperatorValidGreaterThan
    public function testCleanOperatorValidGreaterThan()
    {
        $this->assertEquals('>', $this->parser->cleanOperator('>'));
    }
    // }}}

    // {{{ testCleanOperatorValidLessThanOrEqual
    public function testCleanOperatorValidLessThanOrEqual()
    {
        $this->assertEquals('<=', $this->parser->cleanOperator('<='));
    }
    // }}}

    // {{{ testCleanOperatorValidGreaterThanOrEqual
    public function testCleanOperatorValidGreaterThanOrEqual()
    {
        $this->assertEquals('>=', $this->parser->cleanOperator('>='));
    }
    // }}}

    // {{{ testCleanOperatorValidAndLowercase
    public function testCleanOperatorValidAndLowercase()
    {
        $this->assertEquals('and', $this->parser->cleanOperator('and'));
    }
    // }}}

    // {{{ testCleanOperatorValidAndUppercase
    public function testCleanOperatorValidAndUppercase()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->parser->cleanOperator('AAA');
    }
    // }}}

    // {{{ testCleanOperatorValidOrLowercase
    public function testCleanOperatorValidOrLowercase()
    {
        $this->assertEquals('or', $this->parser->cleanOperator('or'));
    }
    // }}}

    // {{{ testCleanOperatorThrowsForInvalidChar
    public function testCleanOperatorThrowsForInvalidChar()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->expectExceptionMessage("Invalid XPath operator \"'\"");
        $this->parser->cleanOperator('\'');
    }
    // }}}

    // {{{ testCleanOperatorThrowsForInvalidString
    public function testCleanOperatorThrowsForInvalidString()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $this->parser->cleanOperator('invalid');
    }
    // }}}

    // {{{ testParseAttributesSimpleAttribute
    public function testParseAttributesSimpleAttribute()
    {
        $result = $this->parser->parseAttributes('@multilang');
        $this->assertIsArray($result);
        $this->assertEquals('multilang', $result[0][0]);
        $this->assertNull($result[0][1]);
        $this->assertNull($result[0][2]);
    }
    // }}}

    // {{{ testParseAttributesAttributeWithValue
    public function testParseAttributesAttributeWithValue()
    {
        $result = $this->parser->parseAttributes('"P5.1.2"');
        $this->assertIsArray($result);
        // parseAttributes with just a plain string without @attr pattern returns empty
    }
    // }}}

    // {{{ testParseAttributesAttributeWithNotEquals
    public function testParseAttributesSimpleAttributeWithValue()
    {
        $result = $this->parser->parseAttributes("@name='P5.1.2'");
        $this->assertIsArray($result);
        $this->assertEquals('name', $result[0][0]);
    }
    // }}}

    // {{{ testParseAttributesSingleConditionWithAnd
    public function testParseAttributesSingleConditionWithAnd()
    {
        $result = $this->parser->parseAttributes('@a = "x" and @b = "y"');
        $this->assertIsArray($result);
        $this->assertEquals('and', $result[1][3]);
        $this->assertEquals('a', $result[0][0]);
        $this->assertEquals('b', $result[1][0]);
    }
    // }}}

    // {{{ testParseAttributesSingleConditionWithOr
    public function testParseAttributesSingleConditionWithOr()
    {
        $result = $this->parser->parseAttributes('@a = "x" or @b = "y"');
        $this->assertIsArray($result);
        $this->assertEquals('or', $result[1][3]);
    }
    // }}}

    // {{{ testParseAttributesEmptyString
    public function testParseAttributesEmptyString()
    {
        $result = $this->parser->parseAttributes('');
        $this->assertIsArray($result);
    }
    // }}}

    // {{{ testParseAttributesInvalidCharacters
    public function testParseAttributesInvalidCharacters()
    {
        $result = $this->parser->parseAttributes('invalid chars!');
        $this->assertIsArray($result);
    }
    // }}}

    // {{{ testParseAttributesBoolAtStartThrows
    public function testParseAttributesBoolAtStartThrows()
    {
        $this->expectException(\Depage\XmlDb\Exceptions\XmlDbException::class);
        $result = $this->parser->getConditionAttributes("and @a = \"val\"", []);
    }
    // }}}

    // {{{ testParseAttributesMultipleConditionsAllValid
    public function testParseAttributesMultipleConditionsAllValid()
    {
        $result = $this->parser->parseAttributes("@a = \"x\" and @b = \"y\" and @c = \"z\"");
        $this->assertIsArray($result);
        // First condition has no preceding bool
        $this->assertEquals('', $result[0][3]);
        $this->assertEquals('and', $result[1][3]);
        $this->assertEquals('and', $result[2][3]);
    }
    // }}}

    // {{{ testParseAttributesMixedAndOr
    public function testParseAttributesMixedAndOr()
    {
        $result = $this->parser->parseAttributes("@a = \"x\" or @b = \"y\" and @c = \"z\"");
        $this->assertIsArray($result);
        // First condition has no preceding bool
        $this->assertEquals('', $result[0][3]);
        $this->assertEquals('or', $result[1][3]);
        $this->assertEquals('and', $result[2][3]);
    }
    // }}}

    // {{{ testParseAttributeWithUpperAnd
    public function testParseAttributeWithUpperAnd()
    {
        $result = $this->parser->parseAttributes('@a = "x" AND @b = "y"');
        $this->assertIsArray($result);
        $this->assertEquals('and', $result[1][3]);
    }
    // }}}

    // {{{ testParseAttributeWithUpperOr
    public function testParseAttributeWithUpperOr()
    {
        $result = $this->parser->parseAttributes('@a = "x" OR @b = "y"');
        $this->assertIsArray($result);
        $this->assertEquals('or', $result[1][3]);
    }
    // }}}

    // {{{ testParseAttributeWithNamespaceColon
    public function testParseAttributeWithNamespaceColon()
    {
        $result = $this->parser->parseAttributes('@db:id = "16"');
        $this->assertIsArray($result);
        $this->assertEquals('db:id', $result[0][0]);
        $this->assertEquals('=', $result[0][1]);
        $this->assertEquals('16', $result[0][2]);
    }
    // }}}

    // {{{ testParseAttributeWithSpaces
    public function testParseAttributeWithSpaces()
    {
        $result = $this->parser->parseAttributes('@name = "value"');
        $this->assertIsArray($result);
        $this->assertEquals('name', $result[0][0]);
    }
    // }}}

    // {{{ testParseAttributesWithPositionCondition
    public function testParseAttributesWithPositionCondition()
    {
        $result = $this->parser->parseAttributes('@pos > "value"');
        $this->assertIsArray($result);
        $this->assertEquals('pos', $result[0][0]);
        $this->assertEquals('>', $result[0][1]);
    }
    // }}}

    // {{{ testParseAttributesWithPositionLessThan
    public function testParseAttributesWithPositionLessThan()
    {
        $result = $this->parser->parseAttributes('@pos < "value"');
        $this->assertIsArray($result);
        $this->assertEquals('pos', $result[0][0]);
        $this->assertEquals('<', $result[0][1]);
    }
    // }}}

    // {{{ testParseAttributesWithPositionLessThanOrEqual
    public function testParseAttributesWithPositionLessThanOrEqual()
    {
        $result = $this->parser->parseAttributes('@pos <= "value"');
        $this->assertIsArray($result);
        $this->assertEquals('pos', $result[0][0]);
        $this->assertEquals('<=', $result[0][1]);
    }
    // }}}

    // {{{ testParseAttributesWithPositionGreaterThanOrEqual
    public function testParseAttributesWithPositionGreaterThanOrEqual()
    {
        $result = $this->parser->parseAttributes('@pos >= "value"');
        $this->assertIsArray($result);
        $this->assertEquals('pos', $result[0][0]);
        $this->assertEquals('>=', $result[0][1]);
    }
    // }}}

    // {{{ testParseAttributesSingleAttributeNoValue
    public function testParseAttributesSingleAttributeNoValue()
    {
        $result = $this->parser->parseAttributes('@hidden');
        $this->assertIsArray($result);
        $this->assertEquals('hidden', $result[0][0]);
        $this->assertNull($result[0][1]);
        $this->assertNull($result[0][2]);
    }
    // }}}

    // {{{ testCleanOperatorPreservesValidSpaces
    public function testCleanOperatorPreservesValidSpaces()
    {
        $this->assertEquals('=', $this->parser->cleanOperator('='));
        $this->assertEquals('!=', $this->parser->cleanOperator('!='));
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
