<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\SetItemValues;

class SetItemValuesTest extends SetValuesTestCase {

    protected function createRequest($item_id, $values, $optional=array()) {
        return new SetItemValues($item_id, $values, $optional);
    }
}
?>
