<?php

use depage\DB\SQLParser;

class SQLParserTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->parser = new SQLParser();
    }
    // }}}

    // {{{ testIncompleteStatement
    public function testIncompleteStatement()
    {
        // incomplete statement
        $this->parser->processLine("ALTER TABLE\n");
        $this->assertEquals(array(), $this->parser->getStatements());

        // completed...
        $this->parser->processLine("test COMMENT 'version 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testCompleteStatement
    public function testCompleteStatement()
    {
        // complete statement
        $this->parser->processLine("ALTER TABLE test COMMENT 'version 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testTwoStatementsInALine
    public function testTwoStatementsInALine()
    {
        // two complete statements in a line
        $this->parser->processLine("ALTER TABLE test COMMENT 'version 0.2'; ALTER TABLE test COMMENT 'version 0.3';\n");

        $expected = array(
            "ALTER TABLE test COMMENT 'version 0.2'",
            "ALTER TABLE test COMMENT 'version 0.3'",
        );
        $this->assertEquals($expected, $this->parser->getStatements());
    }
    // }}}
    // {{{ testHashComment
    public function testHashComment()
    {
        // incomplete statement with hash comment
        $this->parser->processLine("ALTER TABLE # comment\n");
        $this->assertEquals(array(), $this->parser->getStatements());

        // completed...
        $this->parser->processLine("test COMMENT 'version 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testDoubleDashComment
    public function testDoubleDashComment()
    {
        // incomplete statement with double dash comment
        $this->parser->processLine("ALTER TABLE -- comment\n");
        $this->assertEquals(array(), $this->parser->getStatements());

        // completed...
        $this->parser->processLine("test COMMENT 'version 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testMultilineStyleComment
    public function testMultilineStyleComment()
    {
        // incomplete statement with multiline style comment
        $this->parser->processLine("ALTER TABLE /* comment */ test COMMENT 'version 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testMultilineComment
    public function testMultilineComment()
    {
        // multiline comment
        $this->parser->processLine("ALTER TABLE\n");
        $this->parser->processLine("/* comment\n");
        $this->parser->processLine("comment\n");
        $this->parser->processLine("comment\n");
        $this->parser->processLine("comment */ test\n");
        $this->parser->processLine("COMMENT 'version 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testMupltipleMultilineComment
    public function testMupltipleMultilineComment()
    {
        // multiple multiline comments
        $this->parser->processLine("ALTER /* comment\n");
        $this->parser->processLine("comment\n");
        $this->parser->processLine("comment */ TABLE /* comment\n");
        $this->parser->processLine("comment\n");
        $this->parser->processLine("comment */ test\n");
        $this->parser->processLine("COMMENT 'version 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testMupltipleMultilineCommentMulti
    public function testMupltipleMultilineCommentMulti()
    {
        // multiple multiline comments multi...
        $this->parser->processLine("ALTER /* comment\n");
        $this->parser->processLine("comment\n");
        $this->parser->processLine("comment */ TABLE /* comment */ test /* comment\n");
        $this->parser->processLine("comment\n");
        $this->parser->processLine("comment */ COMMENT\n");
        $this->parser->processLine("'version 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testSingleQuotedSemicolon
    public function testSingleQuotedSemicolon()
    {
        // complete statement with semicolon in single quoted string
        $this->parser->processLine("ALTER TABLE test COMMENT 'vers;ion 0.2';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'vers;ion 0.2'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testDoubleQuotedSemicolon
    public function testDoubleQuotedSemicolon()
    {
        // complete statement with semicolon in double quoted string
        $this->parser->processLine("ALTER TABLE test COMMENT \"vers;ion 0.2\";\n");
        $this->assertEquals(array('ALTER TABLE test COMMENT "vers;ion 0.2"'), $this->parser->getStatements());
    }
    // }}}
    // {{{ testSemicolonInHashComment
    public function testSemicolonInHashComment()
    {
        // incomplete statement with semicolon in hash comment
        $this->parser->processLine("ALTER TABLE # ;\n");
        $this->assertEquals(array(), $this->parser->getStatements());

        // ...completed
        $this->parser->processLine("test COMMENT \"version 0.2\";\n");
        $this->assertEquals(array('ALTER TABLE test COMMENT "version 0.2"'), $this->parser->getStatements());
    }
    // }}}
    // {{{ testSemicolonInDoubleDashComment
    public function testSemicolonInDoubleDashComment()
    {
        // incomplete statement with semicolon in double dash comment
        $this->parser->processLine("ALTER TABLE -- ;\n");
        $this->assertEquals(array(), $this->parser->getStatements());

        // ...completed
        $this->parser->processLine("test COMMENT \"version 0.2\";\n");
        $this->assertEquals(array('ALTER TABLE test COMMENT "version 0.2"'), $this->parser->getStatements());
    }
    // }}}
    // {{{ testSemicolonInMultilineStyleComment
    public function testSemicolonInMultilineStyleComment()
    {
         // incomplete statement with semicolon in multiline style comment
        $this->parser->processLine("ALTER TABLE /* ; */\n");
        $this->assertEquals(array(), $this->parser->getStatements());

        // ...completed
        $this->parser->processLine("test COMMENT \"version 0.2\";\n");
        $this->assertEquals(array('ALTER TABLE test COMMENT "version 0.2"'), $this->parser->getStatements());
    }
    // }}}
    // {{{ testSemicolonInMultilineComment
    public function testSemicolonInMultilineComment()
    {
         // incomplete statement with semicolon in multiline style comment
        $this->parser->processLine("ALTER TABLE /* ; \n");
        $this->assertEquals(array(), $this->parser->getStatements());

        // ...completed
        $this->parser->processLine(" */ test COMMENT \"version 0.2\";\n");
        $this->assertEquals(array('ALTER TABLE test COMMENT "version 0.2"'), $this->parser->getStatements());
    }
    // }}}
    // {{{ testMultilineSingleQuotedString
    public function testMultilineSingleQuotedString()
    {
        // multiline single quoted string
        $this->parser->processLine("ALTER TABLE test COMMENT 'version 0.2\n");
        $this->parser->processLine(" ... string ; continued ... \n");
        $this->parser->processLine(" ... end ';\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2\n ... string ; continued ... \n ... end '"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testMultilineDoubleQuotedString
    public function testMultilineDoubleQuotedString()
    {
        // multiline double quoted string
        $this->parser->processLine("ALTER TABLE test COMMENT \"version 0.2\n");
        $this->parser->processLine(" ... string ; continued ... \n");
        $this->parser->processLine(" ... end \";\n");
        $this->assertEquals(array("ALTER TABLE test COMMENT \"version 0.2\n ... string ; continued ... \n ... end \""), $this->parser->getStatements());
    }
    // }}}
    // {{{ testMultipleWhitespacesInString
    public function testMultipleWhitespacesInString()
    {
        // Multiple whitespaces in strings
        $this->parser->processLine("\"     \" '       ';\n");
        $this->assertEquals(array("\"     \" '       '"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testEscapedSingleQuotesInString
    public function testEscapedSingleQuotesInString()
    {
        // escaped single quotes in strings
        $this->parser->processLine("'str\'ing';\n");
        $this->assertEquals(array("'str\'ing'"), $this->parser->getStatements());
    }
    // }}}
    // {{{ testEscapedDoubleQuotesInString
    public function testEscapedDoubleQuotesInString()
    {
        // escaped double quotes in strings
        $this->parser->processLine('"str\"ing";' . "\n");
        $this->assertEquals(array('"str\"ing"'), $this->parser->getStatements());
    }
    // }}}

    // {{{ testReplace
    public function testReplace()
    {
        $this->parser->replace('comes in', 'goes out');
        $this->parser->processLine("statement comes in;\n");
        $this->assertEquals(array('statement goes out'), $this->parser->getStatements());
    }
    // }}}
    // {{{ testReplacementInStrings
    public function testReplacementInStrings()
    {
        $this->parser->replace('foo', 'bar');
        $this->parser->processLine("foo  ' foo '\" foo \";\n");
        $this->assertEquals(array("bar ' foo '\" foo \""), $this->parser->getStatements());
    }
    // }}}
    // {{{ testEndOfStatement
    public function testEndOfStatement()
    {
        $this->assertTrue($this->parser->isEndOfStatement());
        $this->parser->processLine("incomplete statment\n");
        $this->assertFalse($this->parser->isEndOfStatement());
        $this->parser->processLine("...completed;\n");
        $this->assertTrue($this->parser->isEndOfStatement());
        $this->parser->processLine("incomplete statment\n");
        $this->assertFalse($this->parser->isEndOfStatement());
    }
    // }}}
}
