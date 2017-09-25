<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class AddPropertyTestCase extends RecombeeTestCase {

    abstract protected function createRequest($property_name,$type);

    public function testAddProperty() {

         //it does not fail with valid name and type
         $req = $this->createRequest('number','int');
         $resp = $this->client->send($req);
         $req = $this->createRequest('str','string');
         $resp = $this->client->send($req);

         //it fails with invalid type
         $req = $this->createRequest('prop','integer');
         try {

             $this->client->send($req);
             throw new \Exception('Exception was not thrown');
         }
         catch(Exc\ResponseException $e)
         {
            $this->assertEquals(400, $e->status_code);
         }

         //it really stores property to the system
         $req = $this->createRequest('number2','int');
         $resp = $this->client->send($req);
         try {

             $this->client->send($req);
             throw new \Exception('Exception was not thrown');
         }
         catch(Exc\ResponseException $e)
         {
            $this->assertEquals(409, $e->status_code);
         }

    }
}

?>
