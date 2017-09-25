<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class AddEntityTestCase extends RecombeeTestCase {

    abstract protected function createRequest($item_id);

    public function testAddEntity() {

         //it does not fail with valid entity id
         $req = $this->createRequest('valid_id');
         $resp = $this->client->send($req);

         //it fails with invalid entity id
         $req = $this->createRequest('...not_valid...');
         try {

             $this->client->send($req);
             throw new \Exception('Exception was not thrown');
         }
         catch(Exc\ResponseException $e)
         {
            $this->assertEquals(400, $e->status_code);
         }

         //it really stores entity to the system
         $req = $this->createRequest('valid_id2');
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
