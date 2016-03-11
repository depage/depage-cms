<?php

namespace Depage\XmlDb\Tests;

use Depage\XmlDb\XmlDocTypes\Base;

class DoctypeHandlerBaseTest extends DatabaseTestCase
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

        $this->setUpHandler();
    }
    // }}}
    // {{{ setUpHandler
    protected function setUpHandler()
    {
        $this->dth = new Base($this->xmlDb, $this->doc);

        $this->validParents = array('*' => array('*'));
        $this->availableNodes = array();
    }
    // }}}

    // {{{ testGetPermissions
    public function testGetPermissions()
    {
        $permissions = $this->dth->getPermissions();
        $this->assertEquals($this->validParents, $permissions->validParents);
        $this->assertEquals($this->availableNodes, $permissions->availableNodes);

        $this->assertEquals($this->validParents, $this->dth->getValidParents());
        $this->assertEquals($this->availableNodes, $this->dth->getAvailableNodes());
    }
    // }}}
    // {{{ testGetNewNodeFor
    public function testGetNewNodeFor()
    {
        $this->assertFalse($this->dth->getNewNodeFor('testNode'));
    }
    // }}}

    // {{{ testIsAllowedIn
    public function testIsAllowedIn()
    {
        $this->assertTrue($this->dth->isAllowedIn('testNode', 'targetTestNode'));
    }
    // }}}
}
