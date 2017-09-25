<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class RemoveFromGroupTestCase extends RecombeeTestCase {

    abstract protected function createRequest($group_id,$item_type,$item_id);

    public function testRemoveFromGroup() {

         //it does not fail when removing item that is contained in the set
         $req = $this->createRequest('entity_id','item','entity_id');
         $resp = $this->client->send($req);

         //it fails when removing item that is not contained in the set
         $req = $this->createRequest('entity_id','item','not_contained');
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
