<?php

namespace Depage\XmlDb\Tests;

use Depage\XmlDb\XmlDoctypes\Base;

class DoctypeHandlerBaseTest extends XmlDbTestCase
{
    // {{{ variables
    protected $xmlDb;
    protected $doc;
    protected $cache;
    protected $dth;
    protected $validParents;
    protected $availableNodes;
    // }}}

    // {{{ setUp
    protected function setUp():void
    {
        parent::setUp();

        $this->cache = \Depage\Cache\Cache::factory('xmlDb', ['disposition' => 'uncached']);

        $this->xmlDb = new \Depage\XmlDb\XmlDb($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, [
            'root',
            'child',
        ]);
        $this->doc = new DocumentTestClass($this->xmlDb, 3);

        $this->setUpHandler();
        $this->doc->setDoctypeHandler($this->dth);
    }
    // }}}
    // {{{ setUpHandler
    protected function setUpHandler()
    {
        $this->dth = new Base($this->xmlDb, $this->doc);

        $this->validParents = ['*' => ['*']];
        $this->availableNodes = [];
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
    // {{{ testIsAllowedMove
    public function testIsAllowedMove()
    {
        $this->assertTrue($this->dth->isAllowedMove(5, 7));
    }
    // }}}
}
