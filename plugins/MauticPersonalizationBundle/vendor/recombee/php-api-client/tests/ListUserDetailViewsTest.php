<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListUserDetailViews;

class ListUserDetailViewsTest extends ListUserInteractionsTestCase {

    protected function createRequest($user_id) {
        return new ListUserDetailViews($user_id);
    }
}
?>
