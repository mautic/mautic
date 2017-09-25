<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\AddItem;

class AddItemTest extends AddEntityTestCase {

    protected function createRequest($item_id) {
        return new AddItem($item_id);
    }
}
?>
