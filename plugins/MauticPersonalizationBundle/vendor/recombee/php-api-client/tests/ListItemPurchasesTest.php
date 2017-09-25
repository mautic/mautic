<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListItemPurchases;

class ListItemPurchasesTest extends ListItemInteractionsTestCase {

    protected function createRequest($item_id) {
        return new ListItemPurchases($item_id);
    }
}
?>
