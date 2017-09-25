<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class GetPropertyInfoTestCase extends RecombeeTestCase {

    abstract protected function createRequest($property_name);

    public function testGetPropertyInfo() {

         //it does not fail with existing properties
         $req = $this->createRequest('int_property');
         $resp = $this->client->send($req);
         $this->assertEquals('int',$resp['type']);
         $req = $this->createRequest('str_property');
         $resp = $this->client->send($req);
         $this->assertEquals('string',$resp['type']);

    }
}

?>
