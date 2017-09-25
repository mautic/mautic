<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListSeries;

class ListSeriesTest extends ListEntitiesTestCase {

    protected function createRequest() {
        return new ListSeries();
    }
}
?>
