<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListItemCartAdditions;

class ListItemCartAdditionsTest extends ListItemInteractionsTestCase {

    protected function createRequest($item_id) {
        return new ListItemCartAdditions($item_id);
    }
}
?>
