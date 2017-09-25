<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListSeriesItems;

class ListSeriesItemsTest extends ListSetItemsTestCase {

    protected function createRequest($series_id) {
        return new ListSeriesItems($series_id);
    }
}
?>
