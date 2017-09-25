<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class ListPropertiesTestCase extends RecombeeTestCase {

    abstract protected function createRequest();

    public function testListProperties() {

         //it lists properties
         $req = $this->createRequest();
         $resp = $this->client->send($req);
         $this->assertCount(2, $resp);

    }
}

?>
