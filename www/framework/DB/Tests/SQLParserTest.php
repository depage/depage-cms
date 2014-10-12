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
        $this->assertEquals(array(), $this->parser->parse("ALTER TABLE\n"));

        // completed...
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->parse("test COMMENT 'version 0.2';\n"));
    }
    // }}}
    // {{{ testCompleteStatement
    public function testCompleteStatement()
    {
        // complete statement
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->parse("ALTER TABLE test COMMENT 'version 0.2';\n"));
    }
    // }}}
    // {{{ testTwoStatementsInALine
    public function testTwoStatementsInALine()
    {
        // two complete statements in a line
        $expected = array(
            "ALTER TABLE test COMMENT 'version 0.2'",
            "ALTER TABLE test COMMENT 'version 0.3'",
        );
        $this->assertEquals($expected, $this->parser->parse("ALTER TABLE test COMMENT 'version 0.2'; ALTER TABLE test COMMENT 'version 0.3';\n"));
    }
    // }}}
    // {{{ testHashComment
    public function testHashComment()
    {
        // incomplete statement with hash comment
        $this->assertEquals(array(), $this->parser->parse("ALTER TABLE # comment\n"));

        // completed...
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->parse("test COMMENT 'version 0.2';\n"));
    }
    // }}}
    // {{{ testDoubleDashComment
    public function testDoubleDashComment()
    {
        // incomplete statement with double dash comment
        $this->assertEquals(array(), $this->parser->parse("ALTER TABLE -- comment\n"));

        // completed...
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->parse("test COMMENT 'version 0.2';\n"));
    }
    // }}}
    // {{{ testMultilineStyleComment
    public function testMultilineStyleComment()
    {
        // incomplete statement with multiline style comment
        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->parse("ALTER TABLE /* comment */ test COMMENT 'version 0.2';\n"));
    }
    // }}}
    // {{{ testMultilineComment
    public function testMultilineComment()
    {
        // multiline comment
        $this->parser->parse("ALTER TABLE\n");
        $this->parser->parse("/* comment\n");
        $this->parser->parse("comment\n");
        $this->parser->parse("comment\n");
        $this->parser->parse("comment */ test\n");

        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->parse("COMMENT 'version 0.2';\n"));
    }
    // }}}
    // {{{ testMupltipleMultilineComment
    public function testMupltipleMultilineComment()
    {
        // multiple multiline comments
        $this->parser->parse("ALTER /* comment\n");
        $this->parser->parse("comment\n");
        $this->parser->parse("comment */ TABLE /* comment\n");
        $this->parser->parse("comment\n");
        $this->parser->parse("comment */ test\n");

        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->parse("COMMENT 'version 0.2';\n"));
    }
    // }}}
    // {{{ testMupltipleMultilineCommentMulti
    public function testMupltipleMultilineCommentMulti()
    {
        // multiple multiline comments multi...
        $this->parser->parse("ALTER /* comment\n");
        $this->parser->parse("comment\n");
        $this->parser->parse("comment */ TABLE /* comment */ test /* comment\n");
        $this->parser->parse("comment\n");
        $this->parser->parse("comment */ COMMENT\n");

        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2'"), $this->parser->parse("'version 0.2';\n"));
    }
    // }}}
    // {{{ testSingleQuotedSemicolon
    public function testSingleQuotedSemicolon()
    {
        // complete statement with semicolon in single quoted string
        $this->assertEquals(array("ALTER TABLE test COMMENT 'vers;ion 0.2'"), $this->parser->parse("ALTER TABLE test COMMENT 'vers;ion 0.2';\n"));
    }
    // }}}
    // {{{ testDoubleQuotedSemicolon
    public function testDoubleQuotedSemicolon()
    {
        // complete statement with semicolon in double quoted string
        $this->assertEquals(array('ALTER TABLE test COMMENT "vers;ion 0.2"'), $this->parser->parse("ALTER TABLE test COMMENT \"vers;ion 0.2\";\n"));
    }
    // }}}
    // {{{ testSemicolonInHashComment
    public function testSemicolonInHashComment()
    {
        // incomplete statement with semicolon in hash comment
        $this->assertEquals(array(), $this->parser->parse("ALTER TABLE # ;\n"));

        // ...completed
        $this->assertEquals(array('ALTER TABLE test COMMENT "version 0.2"'), $this->parser->parse("test COMMENT \"version 0.2\";\n"));
    }
    // }}}
    // {{{ testSemicolonInDoubleDashComment
    public function testSemicolonInDoubleDashComment()
    {
        // incomplete statement with semicolon in double dash comment
        $this->assertEquals(array(), $this->parser->parse("ALTER TABLE -- ;\n"));

        // ...completed
        $this->assertEquals(array('ALTER TABLE test COMMENT "version 0.2"'), $this->parser->parse("test COMMENT \"version 0.2\";\n"));
    }
    // }}}
    // {{{ testSemicolonInMultilineStyleComment
    public function testSemicolonInMultilineStyleComment()
    {
         // incomplete statement with semicolon in multiline style comment
        $this->assertEquals(array(), $this->parser->parse("ALTER TABLE /* ; */\n"));

        // ...completed
        $this->assertEquals(array('ALTER TABLE test COMMENT "version 0.2"'), $this->parser->parse("test COMMENT \"version 0.2\";\n"));
    }
    // }}}
    // {{{ testSemicolonInMultilineComment
    public function testSemicolonInMultilineComment()
    {
         // incomplete statement with semicolon in multiline style comment
        $this->assertEquals(array(), $this->parser->parse("ALTER TABLE /* ; \n"));

        // ...completed
        $this->assertEquals(array('ALTER TABLE test COMMENT "version 0.2"'), $this->parser->parse(" */ test COMMENT \"version 0.2\";\n"));
    }
    // }}}
    // {{{ testMultilineSingleQuotedString
    public function testMultilineSingleQuotedString()
    {
        // multiline single quoted string
        $this->parser->parse("ALTER TABLE test COMMENT 'version 0.2\n");
        $this->parser->parse(" ... string ; continued ... \n");

        $this->assertEquals(array("ALTER TABLE test COMMENT 'version 0.2\n ... string ; continued ... \n ... end '"), $this->parser->parse(" ... end ';\n"));
    }
    // }}}
    // {{{ testMultilineDoubleQuotedString
    public function testMultilineDoubleQuotedString()
    {
        // multiline double quoted string
        $this->parser->parse("ALTER TABLE test COMMENT \"version 0.2\n");
        $this->parser->parse(" ... string ; continued ... \n");

        $this->assertEquals(array("ALTER TABLE test COMMENT \"version 0.2\n ... string ; continued ... \n ... end \""), $this->parser->parse(" ... end \";\n"));
    }
    // }}}
    // {{{ testMultipleWhitespacesInString
    public function testMultipleWhitespacesInString()
    {
        // Multiple whitespaces in strings
        $this->assertEquals(array("\"     \" '       '"), $this->parser->parse("\"     \" '       ';\n"));
    }
    // }}}
    // {{{ testEscapedSingleQuotesInString
    public function testEscapedSingleQuotesInString()
    {
        // escaped single quotes in strings
        $this->assertEquals(array("'str\'ing'"), $this->parser->parse("'str\'ing';\n"));
    }
    // }}}
    // {{{ testEscapedDoubleQuotesInString
    public function testEscapedDoubleQuotesInString()
    {
        // escaped double quotes in strings
        $this->assertEquals(array('"str\"ing"'), $this->parser->parse('"str\"ing";' . "\n"));
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
