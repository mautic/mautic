<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListUserRatings;

class ListUserRatingsTest extends ListUserInteractionsTestCase {

    protected function createRequest($user_id) {
        return new ListUserRatings($user_id);
    }
}
?>
