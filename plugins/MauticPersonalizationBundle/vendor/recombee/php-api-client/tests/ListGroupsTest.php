<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListGroups;

class ListGroupsTest extends ListEntitiesTestCase {

    protected function createRequest() {
        return new ListGroups();
    }
}
?>
