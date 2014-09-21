<?php

class SchemaParsingTest extends PHPUnit_Framework_TestCase
{
    // {{{ setUp
    public function setUp()
    {
        $this->schema = new SchemaTestClass('');
    }
    // }}}

    // {{{ testIncompleteStatement
    public function testIncompleteStatement()
    {
        // incomplete statement
        $this->schema->commit("ALTER TABLE", 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // completed...
        $this->schema->commit("test COMMENT 'version 0.2';", 2);
        $this->assertEquals("2:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testCompleteStatement
    public function testCompleteStatement()
    {
        // complete statement
        $this->schema->commit("ALTER TABLE test COMMENT 'version 0.2';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testTwoStatementsInALine
    public function testTwoStatementsInALine()
    {
        // two complete statements in a line
        $this->schema->commit("ALTER TABLE test COMMENT 'version 0.2'; ALTER TABLE test COMMENT 'version 0.3';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.3'", $this->schema->committedStatements[1]);
    }
    // }}}
    // {{{ testHashComment
    public function testHashComment()
    {
        // incomplete statement with hash comment
        $this->schema->commit('ALTER TABLE # comment', 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // completed...
        $this->schema->commit("test COMMENT 'version 0.2';", 2);
        $this->assertEquals("2:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testDoubleDashComment
    public function testDoubleDashComment()
    {
        // incomplete statement with double dash comment
        $this->schema->commit('ALTER TABLE -- comment', 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // completed...
        $this->schema->commit("test COMMENT 'version 0.2';", 2);
        $this->assertEquals("2:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testMultilineStyleComment
    public function testMultilineStyleComment()
    {
        // incomplete statement with multiline style comment
        $this->schema->commit("ALTER TABLE /* comment */ test COMMENT 'version 0.2';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testMultilineComment
    public function testMultilineComment()
    {
        // multiline comment
        $this->schema->commit("ALTER TABLE", 1);
        $this->schema->commit("/* comment", 2);
        $this->schema->commit("comment", 3);
        $this->schema->commit("comment", 4);
        $this->schema->commit("comment */ test", 5);
        $this->schema->commit("COMMENT 'version 0.2';", 6);
        $this->assertEquals("6:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testMupltipleMultilineComment
    public function testMupltipleMultilineComment()
    {
        // multiple multiline comments
        $this->schema->commit("ALTER /* comment", 1);
        $this->schema->commit("comment", 2);
        $this->schema->commit("comment */ TABLE /* comment", 3);
        $this->schema->commit("comment", 4);
        $this->schema->commit("comment */ test", 5);
        $this->schema->commit("COMMENT 'version 0.2';", 6);
        $this->assertEquals("6:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testMupltipleMultilineCommentMulti
    public function testMupltipleMultilineCommentMulti()
    {
        // multiple multiline comments multi...
        $this->schema->commit("ALTER /* comment", 1);
        $this->schema->commit("comment", 2);
        $this->schema->commit("comment */ TABLE /* comment */ test /* comment", 3);
        $this->schema->commit("comment", 4);
        $this->schema->commit("comment */ COMMENT", 5);
        $this->schema->commit("'version 0.2';", 6);
        $this->assertEquals("6:ALTER TABLE test COMMENT 'version 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testSingleQuotedSemicolon
    public function testSingleQuotedSemicolon()
    {
        // complete statement with semicolon in single quoted string
        $this->schema->commit("ALTER TABLE test COMMENT 'vers;ion 0.2';", 1);
        $this->assertEquals("1:ALTER TABLE test COMMENT 'vers;ion 0.2'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testDoubleQuotedSemicolon
    public function testDoubleQuotedSemicolon()
    {
        // complete statement with semicolon in double quoted string
        $this->schema->commit('ALTER TABLE test COMMENT "vers;ion 0.2";', 1);
        $this->assertEquals('1:ALTER TABLE test COMMENT "vers;ion 0.2"', $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testSemicolonInHashComment
    public function testSemicolonInHashComment()
    {
        // incomplete statement with semicolon in hash comment
        $this->schema->commit('ALTER TABLE # ;', 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // ...completed
        $this->schema->commit('test COMMENT "version 0.2";', 2);
        $this->assertEquals('2:ALTER TABLE test COMMENT "version 0.2"', $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testSemicolonInDoubleDashComment
    public function testSemicolonInDoubleDashComment()
    {
        // incomplete statement with semicolon in double dash comment
        $this->schema->commit('ALTER TABLE -- ;', 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // ...completed
        $this->schema->commit('test COMMENT "version 0.2";', 2);
        $this->assertEquals('2:ALTER TABLE test COMMENT "version 0.2"', $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testSemicolonInMultilineStyleComment
    public function testSemicolonInMultilineStyleComment()
    {
         // incomplete statement with semicolon in multiline style comment
        $this->schema->commit('ALTER TABLE /* ; */', 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // ...completed
        $this->schema->commit('test COMMENT "version 0.2";', 2);
        $this->assertEquals('2:ALTER TABLE test COMMENT "version 0.2"', $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testSemicolonInMultilineComment
    public function testSemicolonInMultilineComment()
    {
         // incomplete statement with semicolon in multiline style comment
        $this->schema->commit('ALTER TABLE /* ; ', 1);
        $this->assertEquals(array(), $this->schema->committedStatements);

        // ...completed
        $this->schema->commit(' */ test COMMENT "version 0.2";', 2);
        $this->assertEquals('2:ALTER TABLE test COMMENT "version 0.2"', $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testMultilineSingleQuotedString
    public function testMultilineSingleQuotedString()
    {
        // multiline single quoted string
        $this->schema->commit("ALTER TABLE test COMMENT 'version 0.2", 1);
        $this->schema->commit(" ... string ; continued ... ", 2);
        $this->schema->commit(" ... end ';", 3);
        $this->assertEquals("3:ALTER TABLE test COMMENT 'version 0.2\n ... string ; continued ... \n ... end '", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testMultilineDoubleQuotedString
    public function testMultilineDoubleQuotedString()
    {
        // multiline double quoted string
        $this->schema->commit('ALTER TABLE test COMMENT "version 0.2', 1);
        $this->schema->commit(' ... string ; continued ... ', 2);
        $this->schema->commit(' ... end ";', 3);
        $this->assertEquals("3:ALTER TABLE test COMMENT \"version 0.2\n ... string ; continued ... \n ... end \"", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testMultipleWhitespacesInString
    public function testMultipleWhitespacesInString()
    {
        // Multiple whitespaces in strings
        $this->schema->commit('"     " \'       \';', 1);
        $this->assertEquals('1:"     " \'       \'', $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testEscapedSingleQuotesInString
    public function testEscapedSingleQuotesInString()
    {
        // escaped single quotes in strings
        $this->schema->commit("'str\'ing';", 1);
        $this->assertEquals("1:'str\'ing'", $this->schema->committedStatements[0]);
    }
    // }}}
    // {{{ testEscapedDoubleQuotesInString
    public function testEscapedDoubleQuotesInString()
    {
        // escaped double quotes in strings
        $this->schema->commit('"str\"ing";', 1);
        $this->assertEquals('1:"str\"ing"', $this->schema->committedStatements[0]);
    }
    // }}}
}
