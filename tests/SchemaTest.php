<?php

use depage\DB\Schema;

class SchemaTest extends PHPUnit_Framework_TestCase {
    public function testST() {
        $this->schema = new Schema(' ', '');
    }
}
