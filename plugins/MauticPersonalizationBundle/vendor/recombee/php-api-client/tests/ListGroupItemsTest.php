<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListGroupItems;

class ListGroupItemsTest extends ListSetItemsTestCase {

    protected function createRequest($group_id) {
        return new ListGroupItems($group_id);
    }
}
?>
