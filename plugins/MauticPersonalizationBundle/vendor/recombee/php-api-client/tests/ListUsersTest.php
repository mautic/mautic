<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListUsers;

class ListUsersTest extends ListEntitiesWithPropertiesTestCase {

    protected function createRequest($optional=array()) {
        return new ListUsers($optional);
    }
}
?>
