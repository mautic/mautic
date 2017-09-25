<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\GetUserValues;

class GetUserValuesTest extends GetValuesTestCase {

    protected function createRequest($user_id) {
        return new GetUserValues($user_id);
    }
}
?>
