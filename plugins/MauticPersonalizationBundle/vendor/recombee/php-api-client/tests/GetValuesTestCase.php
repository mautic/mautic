<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class GetValuesTestCase extends RecombeeTestCase {

    abstract protected function createRequest($item_id);

    public function testGetValues() {

         //it gets values
         $req = $this->createRequest('entity_id');
         $resp = $this->client->send($req);
         $this->assertEquals(42,$resp['int_property']);
         $this->assertEquals('hello',$resp['str_property']);

    }
}

?>
