<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\AddGroup;

class AddGroupTest extends AddEntityTestCase {

    protected function createRequest($group_id) {
        return new AddGroup($group_id);
    }
}
?>
