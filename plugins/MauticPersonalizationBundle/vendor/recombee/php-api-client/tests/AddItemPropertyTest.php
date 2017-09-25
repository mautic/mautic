<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\AddItemProperty;

class AddItemPropertyTest extends AddPropertyTestCase {

    protected function createRequest($property_name, $type) {
        return new AddItemProperty($property_name, $type);
    }
}
?>
