<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class ListEntitiesTestCase extends RecombeeTestCase {

    abstract protected function createRequest();

    public function testListEntities() {

         //it lists entities
         $req = $this->createRequest();
         $resp = $this->client->send($req);
         $this->assertEquals(['entity_id'],$resp);

    }
}

?>
