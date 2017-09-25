<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\AddSeries;

class AddSeriesTest extends AddEntityTestCase {

    protected function createRequest($series_id) {
        return new AddSeries($series_id);
    }
}
?>
