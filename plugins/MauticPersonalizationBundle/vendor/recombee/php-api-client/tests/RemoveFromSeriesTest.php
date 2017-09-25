<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\RemoveFromSeries;

class RemoveFromSeriesTest extends RemoveFromSeriesTestCase {

    protected function createRequest($series_id, $item_type, $item_id, $time) {
        return new RemoveFromSeries($series_id, $item_type, $item_id, $time);
    }
}
?>
