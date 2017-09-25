<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteUserProperty;

class DeleteUserPropertyTest extends DeletePropertyTestCase {

    protected function createRequest($property_name) {
        return new DeleteUserProperty($property_name);
    }
}
?>
