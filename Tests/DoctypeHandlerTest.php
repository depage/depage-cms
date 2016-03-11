<?php

namespace Depage\XmlDb\Tests;

use Depage\XmlDb\XmlDocTypes\Base;

class DoctypeHandlerTest extends DatabaseTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    // }}}
    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $this->cache = \Depage\Cache\Cache::factory('xmlDb', array('disposition' => 'uncached'));

        $this->xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, array(
            'root',
            'child',
        ));

        $this->doc = new DocumentTestClass($this->xmlDb, 3);
        $this->dth = new Base($this->xmlDb, $this->doc);
    }
    // }}}

    // {{{ testGetPermissions
    public function testGetPermissions()
    {
        $validParents = array('*' => array('*'));
        $availableNodes = array();

        $permissions = $this->dth->getPermissions();
        $this->assertEquals($validParents, $permissions->validParents);
        $this->assertEquals($availableNodes, $permissions->availableNodes);

        $this->assertEquals($validParents, $this->dth->getValidParents());
        $this->assertEquals($availableNodes, $this->dth->getAvailableNodes());
    }
    // }}}
}
