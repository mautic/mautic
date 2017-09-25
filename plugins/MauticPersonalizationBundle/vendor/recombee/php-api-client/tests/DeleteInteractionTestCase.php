<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class DeleteInteractionTestCase extends InteractionsTestCase {

    abstract protected function createRequest($user_id,$item_id,$optional=array());

    public function testDeleteInteraction() {

         //it does not fail with existing entity id
         $req = $this->createRequest('user','item',['timestamp' => 0]);
         $resp = $this->client->send($req);
         $req = $this->createRequest('user','item');
         try {

             $this->client->send($req);
             throw new \Exception('Exception was not thrown');
         }
         catch(Exc\ResponseException $e)
         {
            $this->assertEquals(404, $e->status_code);
         }

    }
}

?>
