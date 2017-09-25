<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\InsertToGroup;

class InsertToGroupTest extends InsertToGroupTestCase {

    protected function createRequest($group_id, $item_type, $item_id, $optional=array()) {
        return new InsertToGroup($group_id, $item_type, $item_id, $optional);
    }
}
?>
