<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListItemDetailViews;

class ListItemDetailViewsTest extends ListItemInteractionsTestCase {

    protected function createRequest($item_id) {
        return new ListItemDetailViews($item_id);
    }
}
?>
