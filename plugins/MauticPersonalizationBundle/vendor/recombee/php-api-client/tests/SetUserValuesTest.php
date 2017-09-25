<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\SetUserValues;

class SetUserValuesTest extends SetValuesTestCase {

    protected function createRequest($user_id, $values, $optional=array()) {
        return new SetUserValues($user_id, $values, $optional);
    }
}
?>
