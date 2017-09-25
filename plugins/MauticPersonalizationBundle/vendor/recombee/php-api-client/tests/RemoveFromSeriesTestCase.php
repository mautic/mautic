<?php

/*
 * This file is auto-generated, do not edit
 */

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;

abstract class RemoveFromSeriesTestCase extends RecombeeTestCase {

    abstract protected function createRequest($series_id,$item_type,$item_id,$time);

    public function testRemoveFromSeries() {

         //it fails when removing item which have different time
         $req = $this->createRequest('entity_id','item','entity_id',0);
         try {

             $this->client->send($req);
             throw new \Exception('Exception was not thrown');
         }
         catch(Exc\ResponseException $e)
         {
            $this->assertEquals(404, $e->status_code);
         }

         //it does not fail when removing item that is contained in the set
         $req = $this->createRequest('entity_id','item','entity_id',1);
         $resp = $this->client->send($req);

         //it fails when removing item that is not contained in the set
         $req = $this->createRequest('entity_id','item','not_contained',1);
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
