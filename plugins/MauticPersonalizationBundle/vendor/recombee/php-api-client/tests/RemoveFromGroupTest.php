<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\RemoveFromGroup;

class RemoveFromGroupTest extends RemoveFromGroupTestCase {

    protected function createRequest($group_id, $item_type, $item_id) {
        return new RemoveFromGroup($group_id, $item_type, $item_id);
    }
}
?>
