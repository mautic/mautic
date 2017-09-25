<?php

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Exceptions as Exc;
use Recombee\RecommApi\Requests as Reqs;


class BatchTest extends RecombeeTestCase {

    public function testBatch() {
        $reqs = [
                    new Reqs\ResetDatabase,
                    new Reqs\AddItemProperty('num', 'int'),
                    new Reqs\AddItemProperty('time', 'timestamp'),
                    new Reqs\SetItemValues('item1', [
                                                    'num' => 99,
                                                    '!cascadeCreate' => true]),
                    new Reqs\AddItem('item1'),
                    new Reqs\SetItemValues('item2', [
                                                    'num' => 68,
                                                    'time' => 27,
                                                    '!cascadeCreate' => true]),
                    new Reqs\ListItems,
                    new Reqs\ListItems(['filter' => "'num' < 99"]),
                    new Reqs\DeleteItem('item1'),
                    new Reqs\ListItems(['filter' => "'num' >= 99"]),
                    new Reqs\AddCartAddition('user', 'item2',  ['timestamp' => '2013-10-29T09:38:41.341Z']),
                    new Reqs\AddCartAddition('user', 'item2', ['cascadeCreate' => true]),
                    new Reqs\ItemBasedRecommendation('item2', 30),
                    new Reqs\UserBasedRecommendation('user_id', 25, ['filter' => "'num'==68",
                                                                        'allowNonexistent' => true])
                ];

        $repl = $this->client->send(new Reqs\Batch($reqs, ['distinctRecomms' => true]));

        $codes = array();
        foreach ($repl as $r) {
            array_push($codes, $r['code']);
        }

        $this->assertEquals([200, 201, 201, 200, 409, 200, 200, 200, 200, 200, 404, 200, 200, 200], $codes);
        sort($repl[6]['json']);
        $this->assertEquals(['item1', 'item2'], $repl[6]['json']);
        sort($repl[7]['json']);
        $this->assertEquals(['item2'], $repl[7]['json']);
        sort($repl[9]['json']);
        $this->assertEquals(array(), $repl[9]['json']);
        $this->assertEquals(['item2'], $repl[13]['json']);

    }


    public function testLargeBatch() {
        $NUM = 23654;
        $reqs = array();

        for($i=0; $i < $NUM; $i++)
            array_push($reqs, new Reqs\AddItem("item-{$i}"));

        $repl = $this->client->send(new Reqs\Batch($reqs));

        $this->assertCount($NUM, $repl);

        foreach ($repl as $r)
            $this->assertEquals(201, $r['code']);
    }
}

?>