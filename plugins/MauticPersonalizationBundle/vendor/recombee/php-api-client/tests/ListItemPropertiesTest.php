<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListItemProperties;

class ListItemPropertiesTest extends ListPropertiesTestCase {

    protected function createRequest() {
        return new ListItemProperties();
    }
}
?>
