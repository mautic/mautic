<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class ListSetItemsTestCase extends RecombeeTestCase {

    abstract protected function createRequest($series_id);

    public function testListSetItems() {

         //it lists set items
         $req = $this->createRequest('entity_id');
         $resp = $this->client->send($req);
         $this->assertCount(1, $resp);
         $this->assertEquals('entity_id',$resp[0]['itemId']);
         $this->assertEquals('item',$resp[0]['itemType']);

    }
}

?>
