<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteItemProperty;

class DeleteItemPropertyTest extends DeletePropertyTestCase {

    protected function createRequest($property_name) {
        return new DeleteItemProperty($property_name);
    }
}
?>
