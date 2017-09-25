<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class ListItemInteractionsTestCase extends InteractionsTestCase {

    abstract protected function createRequest($item_id);

    public function testListItemInteractions() {

         //it lists interactions
         $req = $this->createRequest('item');
         $resp = $this->client->send($req);
         $this->assertCount(1, $resp);
         $this->assertEquals('item',$resp[0]['itemId']);
         $this->assertEquals('user',$resp[0]['userId']);

    }
}

?>
