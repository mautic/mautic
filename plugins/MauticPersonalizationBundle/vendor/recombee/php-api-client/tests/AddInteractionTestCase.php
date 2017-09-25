<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class AddInteractionTestCase extends RecombeeTestCase {

    abstract protected function createRequest($user_id,$item_id,$optional=array());

    public function testAddInteraction() {

         //it does not fail with cascadeCreate
         $req = $this->createRequest('u_id','i_id',['cascadeCreate' => true]);
         $resp = $this->client->send($req);

         //it does not fail with existing item and user
         $req = $this->createRequest('entity_id','entity_id');
         $resp = $this->client->send($req);

         //it does not fail with valid timestamp
         $req = $this->createRequest('entity_id','entity_id',['timestamp' => '2013-10-29T09:38:41.341Z']);
         $resp = $this->client->send($req);

         //it fails with nonexisting item id
         $req = $this->createRequest('entity_id','nonex_id');
         try {

             $this->client->send($req);
             throw new \Exception('Exception was not thrown');
         }
         catch(Exc\ResponseException $e)
         {
            $this->assertEquals(404, $e->status_code);
         }

         //it fails with nonexisting user id
         $req = $this->createRequest('nonex_id','entity_id');
         try {

             $this->client->send($req);
             throw new \Exception('Exception was not thrown');
         }
         catch(Exc\ResponseException $e)
         {
            $this->assertEquals(404, $e->status_code);
         }

         //it fails with invalid time
         $req = $this->createRequest('entity_id','entity_id',['timestamp' => -15]);
         try {

             $this->client->send($req);
             throw new \Exception('Exception was not thrown');
         }
         catch(Exc\ResponseException $e)
         {
            $this->assertEquals(400, $e->status_code);
         }

         //it really stores interaction to the system
         $req = $this->createRequest('u_id2','i_id2',['cascadeCreate' => true,'timestamp' => 5]);
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
