<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\AddUserProperty;

class AddUserPropertyTest extends AddPropertyTestCase {

    protected function createRequest($property_name, $type) {
        return new AddUserProperty($property_name, $type);
    }
}
?>
