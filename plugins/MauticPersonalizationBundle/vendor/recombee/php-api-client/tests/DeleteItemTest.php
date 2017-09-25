<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteItem;

class DeleteItemTest extends DeleteEntityTestCase {

    protected function createRequest($item_id) {
        return new DeleteItem($item_id);
    }
}
?>
