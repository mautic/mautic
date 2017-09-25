<?php
namespace Recombee\RecommApi\Tests;
use Recombee\RecommApi\Requests\ListItemBookmarks;

class ListItemBookmarksTest extends ListItemInteractionsTestCase {

    protected function createRequest($item_id) {
        return new ListItemBookmarks($item_id);
    }
}
?>
