<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\InsertToSeries;

class InsertToSeriesTest extends InsertToSeriesTestCase {

    protected function createRequest($series_id, $item_type, $item_id, $time, $optional=array()) {
        return new InsertToSeries($series_id, $item_type, $item_id, $time, $optional);
    }
}
?>
