<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\GetItemPropertyInfo;

class GetItemPropertyInfoTest extends GetPropertyInfoTestCase {

    protected function createRequest($property_name) {
        return new GetItemPropertyInfo($property_name);
    }
}
?>
