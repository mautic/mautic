<?php

namespace Recombee\RecommApi\Tests;

use Recombee\RecommApi\Client;
use Recombee\RecommApi\Requests as Reqs;

class RecombeeTestCase extends \PHPUnit\Framework\TestCase
{
    protected $client;

    protected function setUp() {
        
        $this->client = new Client('client-test', 'jGGQ6ZKa8rQ1zTAyxTc0EMn55YPF7FJLUtaMLhbsGxmvwxgTwXYqmUk5xVZFw98L');
        $requests = new Reqs\Batch([
            new Reqs\ResetDatabase,
            new Reqs\AddItem('entity_id'),
            new Reqs\AddUser('entity_id'),
            new Reqs\AddSeries('entity_id'),
            new Reqs\AddGroup('entity_id'),
            new Reqs\InsertToGroup('entity_id', 'item', 'entity_id'),
            new Reqs\InsertToSeries('entity_id', 'item', 'entity_id', 1),
            new Reqs\AddItemProperty('int_property', 'int'),
            new Reqs\AddItemProperty('str_property', 'string'),
            new Reqs\SetItemValues('entity_id', ['int_property' => 42, 'str_property' => 'hello']),
            new Reqs\AddUserProperty('int_property', 'int'),
            new Reqs\AddUserProperty('str_property', 'string'),
            new Reqs\SetUserValues('entity_id', ['int_property' => 42, 'str_property' => 'hello'])

        ]);

        $this->client->send($requests);
    }
 }


?>