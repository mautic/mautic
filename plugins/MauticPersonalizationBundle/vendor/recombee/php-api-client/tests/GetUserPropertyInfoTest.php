<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\GetUserPropertyInfo;

class GetUserPropertyInfoTest extends GetPropertyInfoTestCase {

    protected function createRequest($property_name) {
        return new GetUserPropertyInfo($property_name);
    }
}
?>
