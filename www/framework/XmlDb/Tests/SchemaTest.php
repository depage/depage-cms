<?php

class SchemaTest extends Depage\XmlDb\Tests\DatabaseTestCase
{
    // {{{ testUpdateSchema
    public function testUpdateSchema()
    {
        $tables = array(
            'xmldb_proj_schema_test_xmldocs',
            'xmldb_proj_schema_test_xmltree',
            'xmldb_proj_schema_test_xmldeltaupdates',
            'xmldb_proj_schema_test_xmlnodetypes',
        );

        $this->dropTables($tables);

        $cache = Depage\Cache\Cache::factory('xmldb', array('disposition' => 'uncached'));
        $xmldb = new Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_schema_test', $this->pdo, $cache);
        $xmldb->updateSchema();

        foreach ($tables as $table) {
            $this->assertTrue($this->tableExists($table));
        }

        $this->dropTables($tables);
    }
    // }}}
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
