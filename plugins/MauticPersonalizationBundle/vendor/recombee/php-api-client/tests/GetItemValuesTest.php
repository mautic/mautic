<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\GetItemValues;

class GetItemValuesTest extends GetValuesTestCase {

    protected function createRequest($item_id) {
        return new GetItemValues($item_id);
    }
}
?>
