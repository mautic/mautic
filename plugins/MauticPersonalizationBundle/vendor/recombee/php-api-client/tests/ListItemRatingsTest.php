<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListItemRatings;

class ListItemRatingsTest extends ListItemInteractionsTestCase {

    protected function createRequest($item_id) {
        return new ListItemRatings($item_id);
    }
}
?>
