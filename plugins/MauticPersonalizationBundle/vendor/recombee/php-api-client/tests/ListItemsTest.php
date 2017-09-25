<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListItems;

class ListItemsTest extends ListEntitiesWithPropertiesTestCase {

    protected function createRequest($optional=array()) {
        return new ListItems($optional);
    }
}
?>
