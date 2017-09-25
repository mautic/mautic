<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\DeleteSeries;

class DeleteSeriesTest extends DeleteEntityTestCase {

    protected function createRequest($series_id) {
        return new DeleteSeries($series_id);
    }
}
?>
