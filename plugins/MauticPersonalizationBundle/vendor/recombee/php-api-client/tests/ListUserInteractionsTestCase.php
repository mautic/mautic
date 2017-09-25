<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class ListUserInteractionsTestCase extends InteractionsTestCase {

    abstract protected function createRequest($user_id);

    public function testListUserInteractions() {

         //it lists user interactions
         $req = $this->createRequest('user');
         $resp = $this->client->send($req);
         $this->assertCount(1, $resp);
         $this->assertEquals('item',$resp[0]['itemId']);
         $this->assertEquals('user',$resp[0]['userId']);

    }
}

?>
