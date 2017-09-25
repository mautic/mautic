<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListUserViewPortions;

class ListUserViewPortionsTest extends ListUserInteractionsTestCase {

    protected function createRequest($user_id) {
        return new ListUserViewPortions($user_id);
    }
}
?>
