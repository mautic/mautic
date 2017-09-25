<?php

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests as Reqs;
use Recombee\RecommApi\Exceptions as Exc;

abstract class RecommendationTestCase extends RecombeeTestCase {

    abstract protected function createRequest($entity_id, $count, $optional = array());

    protected function setUp() {
        parent::setUp();

        $num = 1000;
        $probability_purchased = 0.007;

        $my_user_ids = array();
        $my_item_ids = array();
        for($i=0; $i < $num; $i++) {
            array_push($my_user_ids, "user-{$i}");
            array_push($my_item_ids, "item-{$i}");
        }

        $my_purchases = array();
        foreach ($my_user_ids as $user_id) {
            foreach ($my_item_ids as $item_id) {
                if(mt_rand() / mt_getrandmax() < $probability_purchased)
                    array_push($my_purchases, new Reqs\AddPurchase($user_id, $item_id));
            }
        }

        $client = new Client('client-test', 'jGGQ6ZKa8rQ1zTAyxTc0EMn55YPF7FJLUtaMLhbsGxmvwxgTwXYqmUk5xVZFw98L');

        $user_requests = array_map(function($userId) {return new Reqs\AddUser($userId);}, $my_user_ids);
        $client->send(new Reqs\Batch($user_requests));
        $client->send(new Reqs\Batch([new Reqs\AddItemProperty('answer', 'int'), new Reqs\AddItemProperty('id2', 'string'), new Reqs\AddItemProperty('empty', 'string')]));

        $item_requests = array_map(function($itemId) {return new Reqs\SetItemValues($itemId, ['answer' => 42, 'id2' => $itemId, '!cascadeCreate' => true]);}, $my_item_ids);
        $client->send(new Reqs\Batch($item_requests));
        $client->send(new Reqs\Batch($my_purchases));
    }

    public function testBasicRecomm() {
        $req = $this->createRequest('entity_id', 9);
        $resp = $this->client->send($req);
        $this->assertCount(9, $resp);
    }

    public function testRotation() {

        $req = $this->createRequest('entity_id', 9);
        $recommended1 = $this->client->send($req);
        $this->assertCount(9, $recommended1);

        $req = $this->createRequest('entity_id', 9, ['rotationTime' => 10000, 'rotationRate' => 1]);
        $recommended2 = $this->client->send($req);
        $this->assertCount(9, $recommended2);

        foreach ($recommended1 as $item_id) {
            $this->assertNotContains($item_id, $recommended2);
        }
    }

    public function testInBatch() {
        $num = 25;
        $reqs = array();
        for($i=0; $i<$num; $i++) {
            array_push($reqs, $this->createRequest("entity_{$i}", 9, ['cascadeCreate' => true]));
        }
        $recommendations = $this->client->send(new Reqs\Batch($reqs));
        foreach ($recommendations as $recommendation) {
            $this->assertCount(9, $recommendation['json']);
        }
    }

    public function testReturningProperties() {
        $req = $this->createRequest('entity_id', 9, ['returnProperties' => true, 'includedProperties'=>['answer', 'id2', 'empty']]);
        $recommended = $this->client->send($req);
        foreach ($recommended as $rec) {
            $this->assertEquals($rec['id2'], $rec['itemId']);
            $this->assertEquals(42, $rec['answer']);
            $this->assertArrayHasKey('empty', $rec);
        }

        $req = $this->createRequest('entity_id', 9, ['returnProperties' => true, 'includedProperties'=>'answer,id2']);
        $recommended = $this->client->send($req);
        foreach ($recommended as $rec) {
            $this->assertEquals($rec['id2'], $rec['itemId']);
            $this->assertEquals(42, $rec['answer']);
            $this->assertArrayNotHasKey('empty', $rec);
        }
    }
}
?>