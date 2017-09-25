<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListUserProperties;

class ListUserPropertiesTest extends ListPropertiesTestCase {

    protected function createRequest() {
        return new ListUserProperties();
    }
}
?>
