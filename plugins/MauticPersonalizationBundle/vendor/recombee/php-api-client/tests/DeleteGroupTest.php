<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteGroup;

class DeleteGroupTest extends DeleteEntityTestCase {

    protected function createRequest($group_id) {
        return new DeleteGroup($group_id);
    }
}
?>
