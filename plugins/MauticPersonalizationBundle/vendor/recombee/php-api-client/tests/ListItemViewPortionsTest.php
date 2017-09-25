<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListItemViewPortions;

class ListItemViewPortionsTest extends ListItemInteractionsTestCase {

    protected function createRequest($item_id) {
        return new ListItemViewPortions($item_id);
    }
}
?>
